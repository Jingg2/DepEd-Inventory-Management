<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_supply_excel.php
require_once __DIR__ . '/../model/supplyModel.php';

// Parameters
$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$supplies = [];

// Load supply data
$model = new SupplyModel();
$currentMonthStr = date('Y-m');

if ($startDate && $endDate) {
    // Specific Date Range: Use the new range-based calculation
    $supplies = $model->getMonthlyReportData(null, $startDate, $endDate);
    $selectedMonth = $startDate . '_to_' . $endDate; // For filename
} elseif ($selectedMonth === $currentMonthStr) {
    // Current month (Live): ALWAYS use the dynamic rewind calculation
    // This ignores the cached 'requisition'/'issuance' columns in 'supply' table
    $supplies = $model->getMonthlyReportData($selectedMonth);
} else {
    // Historical month: Prioritize Snapshot for performance/permanence
    require_once __DIR__ . '/../model/snapshotModel.php';
    $snapshotModel = new SnapshotModel();
    if ($snapshotModel->snapshotExists($selectedMonth)) {
        $supplies = $snapshotModel->getSnapshotData($selectedMonth);
    } else {
        // Fallback: Calculate live using rewind logic
        $supplies = $model->getMonthlyReportData($selectedMonth);
    }
}

// Map calculated or snapshot columns to report variables
foreach ($supplies as &$supply) {
    // AUTHORITATIVE ANCHOR: The physical quantity is the ground truth
    $supply['reported_bal'] = (float)($supply['quantity'] ?? 0);
    $supply['acq']  = (float)($supply['requisition'] ?? 0);
    $supply['iss']  = (float)($supply['issuance'] ?? 0);
    
    // EXPLICIT BACK-TRACE LOGIC: Prev = Ending + Issuance - Acquisition
    $supply['prev'] = $supply['reported_bal'] + $supply['iss'] - $supply['acq'];
}
unset($supply);

// Grouping
$groupedSupplies = [];
foreach ($supplies as $supply) {
    $cat = strtoupper(trim($supply['category'] ?? 'UNCATEGORIZED'));
    if ($cat === 'OFFICE' || $cat === 'OFFICE SUPPLY') $cat = 'OFFICE SUPPLIES';
    $groupedSupplies[$cat][] = $supply;
}
ksort($groupedSupplies);
if (isset($groupedSupplies['OFFICE SUPPLIES'])) {
    $temp = $groupedSupplies['OFFICE SUPPLIES'];
    unset($groupedSupplies['OFFICE SUPPLIES']);
    $groupedSupplies = ['OFFICE SUPPLIES' => $temp] + $groupedSupplies;
}

// Logging
require_once __DIR__ . '/../model/SystemLogModel.php';
(new SystemLogModel())->log("EXPORT_INVENTORY", "Exported DepEd Format Monthly Inventory for $selectedMonth");

// Excel Headers
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=Monthly_Inventory_' . $selectedMonth . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';
echo '<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%; font-family:Arial, sans-serif;">';

// Define Widths
echo '<colgroup>
    <col width="60">
    <col width="120">
    <col width="50">
    <col width="350">
    <col width="90">
    <col width="90">
    <col width="90">
    <col width="90">
    <col width="100"> <!-- Unit Cost -->
    <col width="120"> <!-- Total Cost Balance -->
</colgroup>';

// 1. Header (Centered, No Borders)
echo '<tr><td colspan="11" align="center" style="font-weight:bold; font-size:10pt; border:none;">REPUBLIC OF THE PHILIPPINES</td></tr>';
echo '<tr><td colspan="11" align="center" style="font-weight:bold; font-size:10pt; border:none;">DEPARTMENT OF EDUCATION</td></tr>';
echo '<tr><td colspan="11" align="center" style="font-weight:bold; font-size:10pt; border:none;">REGION VII, CENTRAL VISAYAS</td></tr>';
echo '<tr><td colspan="11" align="center" style="font-weight:bold; font-size:10pt; border:none;">Buac, Cayang, Bogo City, Cebu</td></tr>';
echo '<tr><td colspan="11" style="border:none;">&nbsp;</td></tr>';

$displayMonth = date('F t, Y', strtotime($selectedMonth . '-01'));
echo '<tr><td colspan="11" align="center" style="font-weight:bold; font-size:12pt; border:none;">MONTHLY INVENTORY REPORT</td></tr>';
echo '<tr><td colspan="11" align="center" style="font-weight:bold; font-size:11pt; border:none;">AS OF ' . strtoupper($displayMonth) . '</td></tr>';
echo '<tr><td colspan="11" style="border:none;">&nbsp;</td></tr>';

// 2. Table Headers
echo '<tr style="font-weight:bold; font-size:9pt; background-color:#ffffff; height:30pt;">
    <th rowspan="2" align="center" style="border:1px solid black; width:60px;">RECORDING</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:120px;">STOCK NO.</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:50px;">UNIT</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:350px;">ITEM/ DESCRIPTION</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:90px;">PREVIOUS MONTH BALANCE</th>
    <th colspan="2" align="center" style="border:1px solid black;">ACQUISITION FOR THE MONTH</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:90px;">ISSUANCE FOR THE MONTH</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:90px;">BALANCE FOR THE MONTH</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:100px;">UNIT COST</th>
    <th rowspan="2" align="center" style="border:1px solid black; width:120px;">TOTAL COST (BALANCE)</th>
</tr>';
echo '<tr style="font-weight:bold; font-size:9pt; background-color:#ffffff;">
    <th align="center" style="border:1px solid black;">Qty</th>
    <th align="center" style="border:1px solid black;">Amount</th>
</tr>';

// Data Rows
$grandTotal = 0;
foreach ($groupedSupplies as $cat => $items) {
    echo '<tr><td colspan="11" style="background-color:#ffff00; font-weight:bold; border:1px solid black; height:20pt; padding-left:10px;">' . $cat . '</td></tr>';
    foreach ($items as $item) {
        $prev = (float)$item['prev'];
        $acq = (float)$item['acq'];
        $iss = (float)$item['iss'];
        $bal = (float)$item['reported_bal'];
        $cost = (float)$item['unit_cost'];
        $total = $bal * $cost;
        $grandTotal += $total;

        $totalAcq = $acq * $cost;
        echo '<tr style="font-size:9pt; height:20pt;">';
        echo '<td align="center" style="border:1px solid black;">SC</td>';
        echo '<td align="center" style="border:1px solid black;">' . htmlspecialchars($item['stock_no'] ?? '') . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . htmlspecialchars($item['unit'] ?? '') . '</td>';
        
        $descStr = ($item['description'] ?? '');
        $displayText = htmlspecialchars($item['item'] ?? '');
        if ($descStr !== '') {
            $displayText .= ' - ' . htmlspecialchars($descStr);
        }
        
        echo '<td style="border:1px solid black; padding-left:5px;">' . $displayText . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . ($prev > 0 ? $prev : '0') . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . ($acq > 0 ? $acq : '0') . '</td>';
        echo '<td align="right" style="border:1px solid black; padding-right:5px;">' . number_format($totalAcq, 2) . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . ($iss > 0 ? $iss : '0') . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . ($bal >= 0 ? $bal : '0') . '</td>';
        echo '<td align="right" style="border:1px solid black; padding-right:5px;">' . number_format($cost, 2) . '</td>';
        echo '<td align="right" style="border:1px solid black; padding-right:5px;">' . number_format($total, 2) . '</td>';
        echo '</tr>';
    }
}

// Total
echo '<tr>
    <td colspan="10" align="right" style="font-weight:bold; border:1px solid black; background-color:#f2f2f2; height:20pt; padding-right:5px;">TOTAL ON HAND VALUE</td>
    <td align="right" style="font-weight:bold; border:1px solid black; background-color:#f2f2f2; padding-right:5px;">' . number_format($grandTotal, 2) . '</td>
</tr>';

// Footer
echo '<tr><td colspan="11" style="border:none;">&nbsp;</td></tr>';
echo '<tr><td colspan="11" style="border:none;">&nbsp;</td></tr>';

echo '<tr>
    <td colspan="4" style="border:none;">Prepared by:</td>
    <td colspan="3" align="center" style="border:none; text-align:center;">Noted by:</td>
    <td colspan="4" align="center" style="border:none; text-align:center;">Approved by:</td>
</tr>';
echo '<tr><td colspan="11" height="40" style="border:none;"></td></tr>';

echo '<tr>
    <td colspan="4" align="center" style="border-bottom:1px solid black; font-weight:bold; text-align:center;">INGRID B. CLEMENTE</td>
    <td colspan="3" align="center" style="border-bottom:1px solid black; font-weight:bold; text-align:center;">ATTY. PHILIP M. CONDOR</td>
    <td colspan="4" align="center" style="border-bottom:1px solid black; font-weight:bold; text-align:center;">LEAH P. NOVERAS, Ed.D, CESO VI</td>
</tr>';
echo '<tr>
    <td colspan="4" align="center" style="border:none; text-align:center; font-size:9pt;">ADOF IV</td>
    <td colspan="3" align="center" style="border:none; text-align:center; font-size:9pt;">ADOF V</td>
    <td colspan="4" align="center" style="border:none; text-align:center; font-size:9pt;">Schools Division Superintendent</td>
</tr>';

echo '</table></body></html>';
?>
