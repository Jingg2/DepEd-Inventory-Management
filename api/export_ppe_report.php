<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_ppe_report.php
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

// Get PPE/Semi-Expendable items
$model = new SupplyModel();
$supplies = $model->getPPESemiExpendableItems();

// Log the action
$logModel = new SystemLogModel();
$logModel->log("EXPORT_PPE_REPORT", "Exported PPE & Semi-Expendable Report");

// Clear output buffer
if (ob_get_level()) ob_end_clean();

// Set headers for download as Excel file
$dateLabel = date('Y-m-d');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=PPE_SemiExpendable_Report_' . $dateLabel . '.xls');
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

// Start HTML output for Excel
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>PPE Report</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>
        body { font-family: "Arial", sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; vertical-align: middle; }
        th { text-align: center; font-weight: bold; background-color: #4a5568; color: white; font-size: 9pt; }
        .header-title { font-weight: bold; text-align: center; border: none; font-size: 12pt; }
        .no-border { border: none; }
        .category-header { background-color: #667eea; color: white; font-weight: bold; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .status-functional { background-color: #d4edda; color: #155724; }
        .status-repair { background-color: #fff3cd; color: #856404; }
        .status-not-functional { background-color: #f8d7da; color: #721c24; }
        .status-disposed { background-color: #e2e3e5; color: #383d41; }
      </style>';
echo '</head>';
echo '<body>';
echo '<table>';

// Header
echo '<tr><td colspan="10" class="header-title">PPE & SEMI-EXPENDABLE PROPERTY REPORT</td></tr>';
echo '<tr><td colspan="10" class="no-border">&nbsp;</td></tr>';
echo '<tr>
        <td colspan="5" class="no-border"><b>ENTITY NAME:</b> CITY GOVERNMENT OF BOGO</td>
        <td colspan="5" class="no-border text-right"><b>AS OF:</b> ' . strtoupper(date('F d, Y')) . '</td>
      </tr>';
echo '<tr><td colspan="10" class="no-border">&nbsp;</td></tr>';

// Table Columns
echo '<tr>
        <th style="width: 50px;">NO.</th>
        <th style="width: 100px;">STOCK NO.</th>
        <th style="width: 200px;">ITEM / DESCRIPTION</th>
        <th style="width: 80px;">UNIT</th>
        <th style="width: 80px;">QUANTITY</th>
        <th style="width: 100px;">UNIT COST</th>
        <th style="width: 120px;">TOTAL VALUE</th>
        <th style="width: 120px;">CLASSIFICATION</th>
        <th style="width: 120px;">CLASSIFICATION</th>
        <th style="width: 150px;">REMARKS</th>
      </tr>';

$grandTotal = 0;
$rowNum = 0;

// Status counts for summary
$statusCounts = [
    'Functional' => 0,
    'For Repair' => 0,
    'Not Functional' => 0,
    'Disposed' => 0
];

foreach ($groupedSupplies as $classification => $items) {
    // Classification Header
    echo '<tr>';
    echo '<td colspan="10" class="category-header"><b>' . strtoupper($classification) . '</b></td>';
    echo '</tr>';
    
    foreach ($items as $supply) {
        $rowNum++;
        $qty = (int)($supply['quantity'] ?? 0);
        $unitCost = (float)($supply['unit_cost'] ?? 0);
        $totalValue = $qty * $unitCost;
        $grandTotal += $totalValue;
        
        $grandTotal += $totalValue;
        
        echo '<tr>';
        echo '<td class="text-center">' . $rowNum . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['stock_no'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars(($supply['item'] ?? '') . ' - ' . ($supply['description'] ?? '')) . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['unit'] ?? '') . '</td>';
        echo '<td class="text-center">' . $qty . '</td>';
        echo '<td class="text-right">₱' . number_format($unitCost, 2) . '</td>';
        echo '<td class="text-right">₱' . number_format($totalValue, 2) . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['property_classification'] ?? '') . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['property_classification'] ?? '') . '</td>';
        echo '<td></td>';
        echo '</tr>';
    }
}

// Grand Total Row
echo '<tr>';
echo '<td colspan="6" class="text-right font-bold">GRAND TOTAL</td>';
echo '<td class="text-right font-bold">₱' . number_format($grandTotal, 2) . '</td>';
echo '<td colspan="3" class="no-border"></td>';
echo '</tr>';

// Summary Section
echo '<tr><td colspan="10" class="no-border">&nbsp;</td></tr>';

echo '</table>';
echo '</body>';
echo '</html>';
?>
