<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_ppe_report.php
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

// Check if month parameter is provided
$selectedMonth = $_GET['month'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Get PPE/Semi-Expendable items
$model = new SupplyModel();
$supplies = $model->getPPESemiExpendableItems();

// Filter by date if provided (using updated_at as proxy for current status at that time)
if ($startDate && $endDate) {
    $startTs = strtotime($startDate . ' 00:00:00');
    $endTs = strtotime($endDate . ' 23:59:59');
    $supplies = array_filter($supplies, function($s) use ($startTs, $endTs) {
        $ts = strtotime($s['updated_at']);
        return $ts >= $startTs && $ts <= $endTs;
    });
} else if ($selectedMonth && $selectedMonth !== 'current') {
    $supplies = array_filter($supplies, function($s) use ($selectedMonth) {
        return date('Y-m', strtotime($s['updated_at'])) === $selectedMonth;
    });
}

// Log the action
$logModel = new SystemLogModel();
$logAction = "Exported PPE & Semi-Expendable Report";
if ($startDate && $endDate) $logAction .= " ($startDate to $endDate)";
else if ($selectedMonth) $logAction .= " ($selectedMonth)";
$logModel->log("EXPORT_PPE_REPORT", $logAction);

// Clear output buffer
if (ob_get_level()) ob_end_clean();

// Set headers for download as Excel file
$dateSuffix = date('Y-m-d');
if ($startDate && $endDate) $dateSuffix = $startDate . "_to_" . $endDate;
else if ($selectedMonth) $dateSuffix = $selectedMonth;

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=RPCI_PPE_Report_' . $dateSuffix . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Group by property classification
$groupedSupplies = [];
foreach ($supplies as $supply) {
    $classification = $supply['property_classification'] ?? 'Uncategorized';
    if (!isset($groupedSupplies[$classification])) {
        $groupedSupplies[$classification] = [];
    }
    $groupedSupplies[$classification][] = $supply;
}

// Start HTML output for Excel (Appendix 66 - RPCI Format)
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>RPCI PPE</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>
        body { font-family: "Arial", sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; vertical-align: middle; }
        th { text-align: center; font-weight: bold; background-color: #f2f2f2; font-size: 9pt; }
        .header-title { font-weight: bold; text-align: center; border: none; font-size: 11pt; }
        .no-border { border: none; }
        .category-header { background-color: #f8f9fa; font-weight: bold; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .appendix-label { text-align: right; font-style: italic; border: none; font-size: 10pt; }
      </style>';
echo '</head>';
echo '<body>';
echo '<table>';

// Appendix Label
echo '<tr><td colspan="10" class="appendix-label">Appendix 66</td></tr>';

// Header
echo '<tr><td colspan="10" class="header-title">REPORT ON THE PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT (RPCI)</td></tr>';
echo '<tr><td colspan="10" class="no-border">&nbsp;</td></tr>';

$dateLabel = "AS OF: " . strtoupper(date('F d, Y'));
if ($startDate && $endDate) {
    $dateLabel = "PERIOD: " . strtoupper(date('M d, Y', strtotime($startDate))) . " TO " . strtoupper(date('M d, Y', strtotime($endDate)));
} else if ($selectedMonth && $selectedMonth !== 'current') {
    $dateLabel = "AS OF: " . strtoupper(date('F t, Y', strtotime($selectedMonth . '-01')));
}

echo '<tr>
        <td colspan="5" class="no-border"><b>ENTITY NAME:</b> CITY GOVERNMENT OF BOGO</td>
        <td colspan="5" class="no-border text-right"><b>' . $dateLabel . '</b></td>
      </tr>';
echo '<tr>
        <td colspan="5" class="no-border"><b>FUND CLUSTER:</b> ____________________</td>
        <td colspan="5" class="no-border">&nbsp;</td>
      </tr>';
echo '<tr><td colspan="10" class="no-border">&nbsp;</td></tr>';

// Table Columns (RPCI Structure)
echo '<tr>
        <th rowspan="2" style="width: 150px;">ARTICLE</th>
        <th rowspan="2" style="width: 250px;">DESCRIPTION</th>
        <th rowspan="2" style="width: 100px;">STOCK NO.</th>
        <th rowspan="2" style="width: 80px;">UNIT OF MEASURE</th>
        <th rowspan="2" style="width: 100px;">UNIT VALUE</th>
        <th rowspan="2" style="width: 100px;">BALANCE PER CARD (Quantity)</th>
        <th rowspan="2" style="width: 100px;">ON HAND PER COUNT (Quantity)</th>
        <th colspan="2">SHORTAGE/OVERAGE</th>
        <th rowspan="2" style="width: 150px;">REMARKS</th>
      </tr>';
echo '<tr>
        <th style="width: 80px;">Quantity</th>
        <th style="width: 100px;">Value</th>
      </tr>';

$grandTotal = 0;
$rowNum = 0;

foreach ($groupedSupplies as $classification => $items) {
    // Classification Header
    echo '<tr>';
    echo '<td colspan="10" class="category-header"><b>CLASSIFICATION: ' . strtoupper($classification) . '</b></td>';
    echo '</tr>';
    
    foreach ($items as $supply) {
        $rowNum++;
        $qty = (float)($supply['quantity'] ?? 0);
        $unitCost = (float)($supply['unit_cost'] ?? 0);
        $totalValue = $qty * $unitCost;
        $grandTotal += $totalValue;
        
        echo '<tr>';
        echo '<td></td>'; // Article blank
        echo '<td>' . htmlspecialchars(($supply['item'] ?? '') . ' - ' . ($supply['description'] ?? '')) . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['stock_no'] ?? '') . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['unit'] ?? '') . '</td>';
        echo '<td class="text-right">' . number_format($unitCost, 2) . '</td>';
        echo '<td class="text-center">' . ($qty > 0 ? $qty : '-') . '</td>';
        echo '<td class="text-center">' . ($qty > 0 ? $qty : '-') . '</td>';
        echo '<td class="text-center"></td>';
        echo '<td class="text-center"></td>';
        echo '<td>' . htmlspecialchars($supply['status'] ?? '') . '</td>';
        echo '</tr>';
    }
}

// Grand Total Row
echo '<tr>';
echo '<td colspan="6" class="text-right font-bold">TOTAL ON HAND VALUE</td>';
echo '<td colspan="1" class="text-center font-bold"></td>';
echo '<td colspan="1" class="text-center font-bold"></td>';
echo '<td class="text-right font-bold">₱' . number_format($grandTotal, 2) . '</td>';
echo '<td class="no-border"></td>';
echo '</tr>';

// Signatories (RPCI Format)
echo '<tr><td colspan="10" class="no-border">&nbsp;</td></tr>';
echo '<tr><td colspan="10" class="no-border">&nbsp;</td></tr>';

echo '<tr>
        <td colspan="3" class="no-border"><b>Certified Correct By:</b></td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="no-border"><b>Approved By:</b></td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="2" class="no-border"><b>Verified By:</b></td>
      </tr>';

echo '<tr><td colspan="10" class="no-border" style="height: 40px;">&nbsp;</td></tr>';

echo '<tr>
        <td colspan="3" class="text-center font-bold" style="border:none; border-bottom: 1px solid black;">ELISA M. ARREZA</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="text-center font-bold" style="border:none; border-bottom: 1px solid black;">NILO J. LUMANCAS, PhD</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="2" class="text-center font-bold" style="border:none; border-bottom: 1px solid black;">COA REPRESENTATIVE</td>
      </tr>';

echo '<tr>
        <td colspan="3" class="no-border text-center">Administrative Aide VI / Supply Officer</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="no-border text-center">Schools Division Superintendent</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="2" class="no-border text-center">Commission on Audit</td>
      </tr>';

echo '</table>';
echo '</body>';
echo '</html>';
?>
