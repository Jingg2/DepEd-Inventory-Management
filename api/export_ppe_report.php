<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_ppe_report.php
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

// Parameters
$selectedMonth = $_GET['month'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Get PPE/Semi-Expendable items directly from the database
$model = new SupplyModel();
$allSupplies = $model->getPPESemiExpendableItems();

// Map DB columns directly — the database IS the source of truth
// quantity = Balance for the Month (historical_balance)
$supplies = [];
foreach ($allSupplies as $s) {
    $s['historical_balance'] = (float)($s['quantity'] ?? 0);
    $supplies[] = $s;
}

// Log the action
$logModel = new SystemLogModel();
$logAction = "Exported PPE & Semi-Expendable Report";
if ($startDate && $endDate) $logAction .= " ($startDate to $endDate)";
else if ($selectedMonth) $logAction .= " ($selectedMonth)";
$logModel->log("EXPORT_PPE_REPORT", $logAction);

// Clear output buffer
if (ob_get_level()) ob_end_clean();

// Set headers for download
$dateSuffix = date('Y-m-d');
if ($startDate && $endDate) $dateSuffix = $startDate . "_to_" . $endDate;
else if ($selectedMonth) $dateSuffix = $selectedMonth;

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=RPCI_PPE_Report_' . $dateSuffix . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Group by category
$groupedSupplies = [];
foreach ($supplies as $supply) {
    $category = strtoupper(trim($supply['category'] ?? 'UNCATEGORIZED'));
    if ($category === 'OFFICE' || $category === 'OFFICE SUPPLY') $category = 'OFFICE SUPPLIES';
    $groupedSupplies[$category][] = $supply;
}
ksort($groupedSupplies);

// Start Output
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
echo '<body>';
echo '<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%; font-family:Arial, sans-serif;">';

// Define Widths (RPCI Structure has 10 columns)
echo '<colgroup>
    <col width="100"> <!-- Article -->
    <col width="300"> <!-- Description -->
    <col width="120"> <!-- Stock No -->
    <col width="60">  <!-- Unit -->
    <col width="100"> <!-- Unit Value -->
    <col width="90">  <!-- Balance Per Card -->
    <col width="90">  <!-- On Hand Per Count -->
    <col width="80">  <!-- Shortage Qty -->
    <col width="100"> <!-- Shortage Value -->
    <col width="150"> <!-- Remarks -->
</colgroup>';

// Appendix Label
echo '<tr><td colspan="10" align="right" style="font-style:italic; font-size:10pt; border:none;">Appendix 66</td></tr>';

// 1. Header (Centered, No Borders)
echo '<tr><td colspan="10" align="center" style="font-weight:bold; font-size:10pt; border:none;">REPUBLIC OF THE PHILIPPINES</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-weight:bold; font-size:10pt; border:none;">DEPARTMENT OF EDUCATION</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-weight:bold; font-size:10pt; border:none;">REGION VII, CENTRAL VISAYAS</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-weight:bold; font-size:10pt; border:none;">Buac, Cayang, Bogo City, Cebu</td></tr>';
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';

echo '<tr><td colspan="10" align="center" style="font-weight:bold; font-size:12pt; border:none;">REPORT ON THE PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT (RPCI)</td></tr>';

$dateLabel = "AS OF: " . strtoupper(date('F d, Y'));
if ($startDate && $endDate) {
    $dateLabel = "PERIOD: " . strtoupper(date('M d, Y', strtotime($startDate))) . " TO " . strtoupper(date('M d, Y', strtotime($endDate)));
} else if ($selectedMonth && $selectedMonth !== 'current') {
    $dateLabel = "AS OF: " . strtoupper(date('F t, Y', strtotime($selectedMonth . '-01')));
}
echo '<tr><td colspan="10" align="center" style="font-weight:bold; font-size:11pt; border:none;">' . $dateLabel . '</td></tr>';
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';

// 2. Table Headers
echo '<tr style="font-weight:bold; font-size:9pt; background-color:#ffffff;">
    <th rowspan="2" align="center" style="border:1px solid black;">ARTICLE</th>
    <th rowspan="2" align="center" style="border:1px solid black;">DESCRIPTION</th>
    <th rowspan="2" align="center" style="border:1px solid black;">STOCK NO.</th>
    <th rowspan="2" align="center" style="border:1px solid black;">UNIT OF MEASURE</th>
    <th rowspan="2" align="center" style="border:1px solid black;">UNIT VALUE</th>
    <th rowspan="2" align="center" style="border:1px solid black;">BALANCE PER CARD (Quantity)</th>
    <th rowspan="2" align="center" style="border:1px solid black;">ON HAND PER COUNT (Quantity)</th>
    <th colspan="2" align="center" style="border:1px solid black;">SHORTAGE/OVERAGE</th>
    <th rowspan="2" align="center" style="border:1px solid black;">REMARKS</th>
</tr>';
echo '<tr style="font-weight:bold; font-size:9pt;">
    <th align="center" style="border:1px solid black;">Qty</th>
    <th align="center" style="border:1px solid black;">Value</th>
</tr>';

// Data Rows
$grandTotal = 0;
foreach ($groupedSupplies as $cat => $items) {
    echo '<tr><td colspan="10" style="background-color:#ffff00; font-weight:bold; border:1px solid black; height:20pt; padding-left:5px;">' . $cat . '</td></tr>';
    foreach ($items as $item) {
        $qty = (float)($item['historical_balance'] ?? 0);
        $cost = (float)($item['unit_cost'] ?? 0);
        $total = $qty * $cost;
        $grandTotal += $total;

        echo '<tr style="font-size:9pt; height:20pt;">';
        echo '<td style="border:1px solid black;"></td>'; // Article
        echo '<td style="border:1px solid black; padding-left:5px;">' . htmlspecialchars($item['item'] . ($item['description'] ? ' - ' . $item['description'] : '')) . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . htmlspecialchars($item['stock_no']) . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . htmlspecialchars($item['unit']) . '</td>';
        echo '<td align="right" style="border:1px solid black; padding-right:5px;">' . number_format($cost, 2) . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . ($qty > 0 ? $qty : '0') . '</td>';
        echo '<td align="center" style="border:1px solid black;">' . ($qty > 0 ? $qty : '0') . '</td>';
        echo '<td align="center" style="border:1px solid black;"></td>'; // Shortage Qty
        echo '<td align="center" style="border:1px solid black;"></td>'; // Shortage Value
        echo '<td style="border:1px solid black; padding-left:5px;">' . htmlspecialchars($item['status'] ?? '') . '</td>';
        echo '</tr>';
    }
}

// Total
echo '<tr>
    <td colspan="6" align="right" style="font-weight:bold; border:1px solid black; background-color:#f2f2f2; padding-right:5px;">TOTAL ON HAND VALUE</td>
    <td align="center" style="border:1px solid black; background-color:#f2f2f2;"></td>
    <td align="center" style="border:1px solid black; background-color:#f2f2f2;"></td>
    <td align="right" style="font-weight:bold; border:1px solid black; background-color:#f2f2f2; padding-right:5px;">' . number_format($grandTotal, 2) . '</td>
    <td style="border:1px solid black; background-color:#f2f2f2;"></td>
</tr>';

// Footer/Signatories
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';

echo '<tr>
    <td colspan="3" style="border:none;">Prepared by:</td>
    <td colspan="4" align="center" style="border:none; text-align:center;">Noted by:</td>
    <td colspan="3" align="center" style="border:none; text-align:center;">Approved by:</td>
</tr>';
echo '<tr><td colspan="10" height="40" style="border:none;"></td></tr>';

echo '<tr>
    <td colspan="3" align="center" style="border-bottom:1px solid black; font-weight:bold; text-align:center;">INGRID B. CLEMENTE</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="3" align="center" style="border-bottom:1px solid black; font-weight:bold; text-align:center;">ATTY. PHILIP M. CONDOR</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="2" align="center" style="border-bottom:1px solid black; font-weight:bold; text-align:center;">LEAH P. NOVERAS, Ed.D, CESO VI</td>
</tr>';
echo '<tr>
    <td colspan="3" align="center" style="border:none; text-align:center; font-size:9pt;">ADOF IV</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="3" align="center" style="border:none; text-align:center; font-size:9pt;">ADOF V</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="2" align="center" style="border:none; text-align:center; font-size:9pt;">Schools Division Superintendent</td>
</tr>';

echo '</table></body></html>';
?>
