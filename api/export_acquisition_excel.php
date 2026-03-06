<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_acquisition_excel.php
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

// Parameters
$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Load data from supply_history (positive changes = receipts/acquisitions)
$model = new SupplyModel();
$acquisitions = $model->getAcquisitionsReportData($selectedMonth, $startDate, $endDate);

// Logging
$rangeStr = ($startDate && $endDate) ? " ($startDate to $endDate)" : " ($selectedMonth)";
(new SystemLogModel())->log("EXPORT_ACQUISITION", "Exported Monthly Acquisition Log" . $rangeStr);

// Excel Headers
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=Acquisition_Log_' . str_replace('-', '', $selectedMonth) . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
    .header-cell { border: 0.5pt solid windowtext; font-weight: bold; text-align: center; background-color: #f2f2f2; }
    .data-cell { border: 0.5pt solid windowtext; vertical-align: middle; padding: 4px; }
    .num-fmt { mso-number-format: "\#\,\#\#0\.00"; text-align: right; border: 0.5pt solid windowtext; padding-right: 4px; }
    .qty-fmt { text-align: center; border: 0.5pt solid windowtext; }
</style>
</head>';
echo '<body>';
echo '<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%; font-family:Arial, sans-serif;">';

// Define Widths
echo '<colgroup>
    <col width="100"> <!-- Date -->
    <col width="120"> <!-- Stock No -->
    <col width="300"> <!-- Item Description -->
    <col width="80">  <!-- Unit -->
    <col width="80">  <!-- Qty -->
    <col width="100"> <!-- Unit Cost -->
    <col width="120"> <!-- Total Cost -->
    <col width="250"> <!-- Remarks -->
</colgroup>';

// 1. Header
echo '<tr><td colspan="8" align="center" style="font-weight:bold; font-size:14pt; border:none;">MONTHLY ACQUISITION LOG (STOCKING)</td></tr>';
$displayRange = ($startDate && $endDate) ? "Period: $startDate to $endDate" : "Month: " . date('F Y', strtotime($selectedMonth . '-01'));
echo '<tr><td colspan="8" align="center" style="font-weight:bold; font-size:11pt; border:none;">' . $displayRange . '</td></tr>';
echo '<tr><td colspan="8" style="border:none;">&nbsp;</td></tr>';

// 2. Table Headers
echo '<tr style="font-weight:bold; font-size:10pt;">
    <th class="header-cell">DATE</th>
    <th class="header-cell">STOCK NO.</th>
    <th class="header-cell">ITEM DESCRIPTION</th>
    <th class="header-cell">UNIT</th>
    <th class="header-cell">QTY</th>
    <th class="header-cell">UNIT COST</th>
    <th class="header-cell">TOTAL COST</th>
    <th class="header-cell">REMARKS / REFERENCE</th>
</tr>';

// Data Rows
$grandTotal = 0;
if (empty($acquisitions)) {
    echo '<tr><td colspan="8" align="center" class="data-cell">No acquisition records found for this period.</td></tr>';
} else {
    foreach ($acquisitions as $row) {
        $qty = (float)$row['quantity'];
        $cost = (float)$row['unit_cost'];
        $total = (float)$row['total_cost'];
        $grandTotal += $total;

        echo '<tr style="font-size:9pt; height:18pt;">';
        echo '<td class="qty-fmt">' . htmlspecialchars($row['acquisition_date']) . '</td>';
        echo '<td class="qty-fmt">' . htmlspecialchars($row['stock_no']) . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['item']) . '</td>';
        echo '<td class="qty-fmt">' . htmlspecialchars($row['unit']) . '</td>';
        echo '<td class="qty-fmt">' . number_format($qty) . '</td>';
        echo '<td class="num-fmt">' . number_format($cost, 2) . '</td>';
        echo '<td class="num-fmt">' . number_format($total, 2) . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['remarks'] ?? '') . '</td>';
        echo '</tr>';
    }
}

// Total
echo '<tr>
    <td colspan="6" align="right" style="font-weight:bold; border:1px solid black; background-color:#f2f2f2; height:20pt; padding-right:5px; text-align:right;">TOTAL ACQUISITION VALUE</td>
    <td class="num-fmt" style="font-weight:bold; background-color:#f2f2f2;">' . number_format($grandTotal, 2) . '</td>
    <td style="border:1px solid black; background-color:#f2f2f2;">&nbsp;</td>
</tr>';

echo '</table></body></html>';
?>
