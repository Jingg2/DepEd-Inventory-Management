<?php
// RCPI - Consumable/Expendable Items (Appendix 66 format)
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate     = $_GET['start_date'] ?? null;
$endDate       = $_GET['end_date'] ?? null;

$model = new SupplyModel();
$currentMonthStr = date('Y-m');
$monthToReport   = ($selectedMonth === 'current' || !$selectedMonth) ? $currentMonthStr : $selectedMonth;

// Data source: live or snapshot
if ($startDate && $endDate) {
    $allData = $model->getMonthlyReportData(null, $startDate, $endDate);
} elseif ($monthToReport === $currentMonthStr) {
    require_once __DIR__ . '/../model/requisitionModel.php';
    (new RequisitionModel())->syncAllMonthlyTotals();
    $allData = $model->getAllSupplies();
} else {
    require_once __DIR__ . '/../model/snapshotModel.php';
    $snap = new SnapshotModel();
    $allData = $snap->snapshotExists($monthToReport)
        ? $snap->getSnapshotData($monthToReport)
        : $model->getMonthlyReportData($monthToReport);
}

// Filter: Consumable / Expendable  (NOT Semi-Expendable, NOT PPE)
$supplies = array_filter($allData, function ($s) {
    $pc = strtolower(trim($s['property_classification'] ?? ''));
    $isSemi = (strpos($pc, 'semi-expendable') !== false || strpos($pc, 'semi expendable') !== false);
    $isPPE  = (strpos($pc, 'ppe') !== false || strpos($pc, 'property, plant') !== false || strpos($pc, 'property plant') !== false);
    return !$isSemi && !$isPPE;
});

// Group by category
$groupedSupplies = [];
foreach ($supplies as $supply) {
    $cat = strtoupper(trim($supply['category'] ?? 'UNCATEGORIZED'));
    if ($cat === 'OFFICE' || $cat === 'OFFICE SUPPLY') $cat = 'OFFICE SUPPLIES';
    $groupedSupplies[$cat][] = $supply;
}
ksort($groupedSupplies);

// Log
(new SystemLogModel())->log("EXPORT_RCPI_CONSUMABLE", "Exported RCPI (Consumable/Expendable) for $monthToReport");

// Headers
if (ob_get_level()) ob_end_clean();
$dateSuffix = $startDate && $endDate ? "{$startDate}_to_{$endDate}" : $monthToReport;
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=RCPI_Consumable_' . $dateSuffix . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

$displayDate = $startDate && $endDate
    ? strtoupper(date('M d, Y', strtotime($startDate)) . ' TO ' . date('M d, Y', strtotime($endDate)))
    : strtoupper(date('F t, Y', strtotime($monthToReport . '-01')));

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
    .hc  { border:0.5pt solid windowtext; font-weight:bold; text-align:center; background-color:#f2f2f2; vertical-align:middle; }
    .dc  { border:0.5pt solid windowtext; vertical-align:middle; }
    .nf  { mso-number-format:"\#\,\#\#0\.00"; text-align:right; border:0.5pt solid windowtext; }
    .qf  { mso-number-format:"\#\,\#\#0"; text-align:center; border:0.5pt solid windowtext; }
</style>
</head>';
echo '<body>';
echo '<table border="0" cellpadding="3" cellspacing="0" style="border-collapse:collapse;width:100%;font-family:Times New Roman,serif;font-size:10pt;">';

echo '<colgroup>
    <col width="90">  <!-- Stock No -->
    <col width="70">  <!-- Unit -->
    <col width="280"> <!-- Description -->
    <col width="90">  <!-- Unit Cost -->
    <col width="85">  <!-- Balance per Card -->
    <col width="85">  <!-- On Hand -->
    <col width="65">  <!-- Shortage Qty -->
    <col width="90">  <!-- Shortage Value -->
    <col width="105"> <!-- Total Value -->
    <col width="110"> <!-- Remarks -->
</colgroup>';

// Header block
echo '<tr><td colspan="10" align="right" style="font-style:italic;font-weight:bold;border:none;">Appendix 66</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-weight:bold;font-size:14pt;border:none;">REPORT ON THE PHYSICAL COUNT OF INVENTORIES (RCPI)</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-size:10pt;border:none;">(Consumable/Expendable Supplies)</td></tr>';
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-weight:bold;font-size:11pt;border:none;">AS OF ' . $displayDate . '</td></tr>';
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';

echo '<tr>
    <td colspan="2" style="font-weight:bold;border:none;">Entity Name:</td>
    <td colspan="8" style="border-bottom:0.5pt solid black;">CITY GOVERNMENT OF BOGO</td>
</tr>';
echo '<tr>
    <td colspan="2" style="font-weight:bold;border:none;">Fund Cluster:</td>
    <td colspan="8" style="border-bottom:0.5pt solid black;">&nbsp;</td>
</tr>';
echo '<tr><td colspan="10" style="border:none;height:8px;">&nbsp;</td></tr>';

// Column headers
echo '<tr style="font-weight:bold;font-size:9pt;">
    <th class="hc" rowspan="2">STOCK NO.</th>
    <th class="hc" rowspan="2">UNIT</th>
    <th class="hc" rowspan="2">ITEM / DESCRIPTION</th>
    <th class="hc" rowspan="2">UNIT COST</th>
    <th class="hc" rowspan="2">BALANCE PER CARD</th>
    <th class="hc" rowspan="2">ON HAND PER COUNT</th>
    <th class="hc" colspan="2">SHORTAGE/OVERAGE</th>
    <th class="hc" rowspan="2">TOTAL VALUE</th>
    <th class="hc" rowspan="2">REMARKS</th>
</tr>';
echo '<tr style="font-weight:bold;font-size:8pt;">
    <th class="hc">QTY</th>
    <th class="hc">VALUE</th>
</tr>';

// Data rows
$grandTotal = 0;
foreach ($groupedSupplies as $cat => $items) {
    echo '<tr><td colspan="10" style="background-color:#ffffcc;font-weight:bold;border:1px solid black;padding-left:5px;">' . htmlspecialchars($cat) . '</td></tr>';
    foreach ($items as $item) {
        $bal   = (float)($item['quantity'] ?? 0);
        $cost  = (float)($item['unit_cost'] ?? 0);
        $total = $bal * $cost;
        $grandTotal += $total;

        echo '<tr>';
        echo '<td class="qf">' . htmlspecialchars($item['stock_no'] ?? '') . '</td>';
        echo '<td class="qf">' . htmlspecialchars($item['unit'] ?? '') . '</td>';
        echo '<td class="dc" style="padding-left:4px;text-align:left;">' . htmlspecialchars(($item['item'] ?? '') . (!empty($item['description']) ? ' - ' . $item['description'] : '')) . '</td>';
        echo '<td class="nf">' . number_format($cost, 2) . '</td>';
        echo '<td class="qf">' . (int)$bal . '</td>';
        echo '<td class="qf">' . (int)$bal . '</td>';
        echo '<td class="qf">0</td>';
        echo '<td class="nf">0.00</td>';
        echo '<td class="nf" style="font-weight:bold;">' . number_format($total, 2) . '</td>';
        echo '<td class="dc">&nbsp;</td>';
        echo '</tr>';
    }
}

// Grand total
echo '<tr>
    <td colspan="8" style="font-weight:bold;border:1px solid black;background-color:#f2f2f2;padding-right:5px;text-align:right;">TOTAL ON HAND VALUE</td>
    <td class="nf" style="font-weight:bold;background-color:#f2f2f2;">' . number_format($grandTotal, 2) . '</td>
    <td style="border:1px solid black;background-color:#f2f2f2;">&nbsp;</td>
</tr>';

// Signatories
echo '<tr><td colspan="10" style="border:none;height:35px;">&nbsp;</td></tr>';
echo '<tr>
    <td colspan="3" style="border:none;">Certified Correct by:</td>
    <td colspan="4" align="center" style="border:none;text-align:center;">Approved by:</td>
    <td colspan="3" align="center" style="border:none;text-align:center;">Verified by:</td>
</tr>';
echo '<tr><td colspan="10" height="40" style="border:none;"></td></tr>';
echo '<tr>
    <td colspan="3" align="center" style="border-bottom:1px solid black;font-weight:bold;text-align:center;">Signature over Printed Name of<br>Inventory Committee Chair and Members</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="3" align="center" style="border-bottom:1px solid black;font-weight:bold;text-align:center;">Signature over Printed Name of Head<br>of Agency Entity or Authorized Representative</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="2" align="center" style="border-bottom:1px solid black;font-weight:bold;text-align:center;">Signature over Printed Name<br>of COA Representative</td>
</tr>';

echo '</table></body></html>';
