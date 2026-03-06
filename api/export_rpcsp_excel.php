<?php
// RPCSP - Report on the Physical Count of Semi-Expendable Property (Annex A.8)
// Covers Semi-Expendable items with unit_cost > 5000
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate     = $_GET['start_date'] ?? null;
$endDate       = $_GET['end_date'] ?? null;

$model = new SupplyModel();
$currentMonthStr = date('Y-m');
$monthToReport   = ($selectedMonth === 'current' || !$selectedMonth) ? $currentMonthStr : $selectedMonth;

// Data source
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

// Filter: Semi-Expendable AND 5000 < unit_cost < 50000
$supplies = array_filter($allData, function ($s) {
    $pc   = strtolower(trim($s['property_classification'] ?? ''));
    $cost = (float)($s['unit_cost'] ?? 0);
    $isSemi = (strpos($pc, 'semi-expendable') !== false || strpos($pc, 'semi expendable') !== false);
    return $isSemi && $cost > 5000 && $cost < 50000;
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
(new SystemLogModel())->log("EXPORT_RPCSP", "Exported RPCSP (Semi-Expendable >5000) for $monthToReport");

// Headers
if (ob_get_level()) ob_end_clean();
$dateSuffix = $startDate && $endDate ? "{$startDate}_to_{$endDate}" : $monthToReport;
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=RPCSP_SemiExpendable_HighValue_' . $dateSuffix . '.xls');
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
// 10 columns: Article | Description | Semi-Exp Prop No | Unit of Measure | Unit Value | Balance Per Card Qty | On Hand Per Count Qty | Shortage/Overage Qty | Shortage/Overage Value | Remarks
echo '<table border="0" cellpadding="3" cellspacing="0" style="border-collapse:collapse;width:100%;font-family:Times New Roman,serif;font-size:10pt;">';

echo '<colgroup>
    <col width="75">  <!-- Article -->
    <col width="260"> <!-- Description -->
    <col width="105"> <!-- Semi-Exp Prop No -->
    <col width="80">  <!-- Unit of Measure -->
    <col width="90">  <!-- Unit Value -->
    <col width="80">  <!-- Balance Per Card -->
    <col width="80">  <!-- On Hand Per Count -->
    <col width="65">  <!-- Shortage Qty -->
    <col width="90">  <!-- Shortage Value -->
    <col width="110"> <!-- Remarks -->
</colgroup>';

// Title block — Annex A.8
echo '<tr><td colspan="10" align="right" style="font-style:italic;font-weight:bold;border:none;">Annex A.8</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-weight:bold;font-size:13pt;border:none;">REPORT ON THE PHYSICAL COUNT OF SEMI-EXPENDABLE PROPERTY</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-size:10pt;border:none;">(Type of Semi-Expendable Property &mdash; Above &#8369;5,000 up to Below &#8369;50,000)</td></tr>';
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';
echo '<tr><td colspan="10" align="center" style="font-weight:bold;font-size:11pt;border:none;">AS OF ' . $displayDate . '</td></tr>';
echo '<tr><td colspan="10" style="border:none;">&nbsp;</td></tr>';

echo '<tr>
    <td colspan="1" style="font-weight:bold;border:none;">Fund Cluster:</td>
    <td colspan="9" style="border-bottom:0.5pt solid black;">&nbsp;</td>
</tr>';
echo '<tr>
    <td colspan="2" style="font-weight:bold;border:none;">For which</td>
    <td colspan="2" style="border-bottom:0.5pt solid black;text-align:center;">(Name of Accountable Officer)</td>
    <td colspan="2" style="border-bottom:0.5pt solid black;text-align:center;">(Official Designation)</td>
    <td colspan="2" style="border-bottom:0.5pt solid black;text-align:center;">(Entity Name)</td>
    <td colspan="2" style="border:none;">is accountable, having assumed such accountability on (Date of Assumption)</td>
</tr>';
echo '<tr><td colspan="10" style="border:none;height:8px;">&nbsp;</td></tr>';

// Column headers (Annex A.8)
echo '<tr style="font-weight:bold;font-size:9pt;">
    <th class="hc" rowspan="2">ARTICLE</th>
    <th class="hc" rowspan="2">DESCRIPTION</th>
    <th class="hc" rowspan="2">SEMI-EXPENDABLE<br>PROPERTY NO.</th>
    <th class="hc" rowspan="2">UNIT OF<br>MEASURE</th>
    <th class="hc" rowspan="2">UNIT VALUE</th>
    <th class="hc" rowspan="2">BALANCE PER<br>CARD QTY.</th>
    <th class="hc" rowspan="2">ON HAND<br>PER COUNT QTY.</th>
    <th class="hc" colspan="2">SHORTAGE/OVERAGE</th>
    <th class="hc" rowspan="2">REMARKS</th>
</tr>';
echo '<tr style="font-weight:bold;font-size:8pt;">
    <th class="hc">QTY.</th>
    <th class="hc">VALUE</th>
</tr>';

// Data rows
$grandTotal = 0;
foreach ($groupedSupplies as $cat => $items) {
    echo '<tr><td colspan="10" style="background-color:#e8f0fe;font-weight:bold;border:1px solid black;padding-left:5px;">' . htmlspecialchars($cat) . '</td></tr>';
    foreach ($items as $item) {
        $bal   = (float)($item['quantity'] ?? 0);
        $cost  = (float)($item['unit_cost'] ?? 0);
        $total = $bal * $cost;
        $grandTotal += $total;

        echo '<tr>';
        echo '<td class="dc" style="text-align:center;">&nbsp;</td>'; // Article (blank)
        echo '<td class="dc" style="padding-left:4px;">' . htmlspecialchars(($item['item'] ?? '') . (!empty($item['description']) ? ' - ' . $item['description'] : '')) . '</td>';
        echo '<td class="qf">' . htmlspecialchars($item['stock_no'] ?? '') . '</td>'; // Semi-Expendable Prop No
        echo '<td class="qf">' . htmlspecialchars($item['unit'] ?? '') . '</td>';
        echo '<td class="nf">' . number_format($cost, 2) . '</td>';
        echo '<td class="qf">' . (int)$bal . '</td>';
        echo '<td class="qf">' . (int)$bal . '</td>';
        echo '<td class="qf">0</td>';
        echo '<td class="nf">0.00</td>';
        echo '<td class="dc">&nbsp;</td>';
        echo '</tr>';
    }
}

// Grand total
echo '<tr>
    <td colspan="8" style="font-weight:bold;border:1px solid black;background-color:#f2f2f2;padding-right:5px;text-align:right;">TOTAL ON HAND VALUE</td>
    <td class="nf" style="font-weight:bold;background-color:#f2f2f2;text-align:right;padding-right:4px;">' . number_format($grandTotal, 2) . '</td>
    <td style="border:1px solid black;background-color:#f2f2f2;">&nbsp;</td>
</tr>';

// Signatories (Annex A.8)
echo '<tr><td colspan="10" style="border:none;height:35px;">&nbsp;</td></tr>';
echo '<tr>
    <td colspan="3" style="border:none;">Certified Correct by:</td>
    <td colspan="4" align="center" style="border:none;text-align:center;">Approved by:</td>
    <td colspan="3" align="center" style="border:none;text-align:center;">Witness by:</td>
</tr>';
echo '<tr><td colspan="10" height="40" style="border:none;"></td></tr>';
echo '<tr>
    <td colspan="3" align="center" style="border-bottom:1px solid black;font-weight:bold;text-align:center;">Signature Over Printed Name of<br>Inventory Committee Chair and Members</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="3" align="center" style="border-bottom:1px solid black;font-weight:bold;text-align:center;">Signature Over Printed Name of Head of the<br>Agency Entity or Authorized Representative</td>
    <td colspan="1" style="border:none;"></td>
    <td colspan="2" align="center" style="border-bottom:1px solid black;font-weight:bold;text-align:center;">Signature Over Printed Name of<br>COA Representative</td>
</tr>';

echo '</table></body></html>';
