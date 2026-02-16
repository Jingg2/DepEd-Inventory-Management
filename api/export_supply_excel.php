<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\export_supply_excel.php
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../model/snapshotModel.php';

// Check if month parameter is provided
$selectedMonth = $_GET['month'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$isSnapshot = false;
$supplies = [];

if (empty($startDate) && empty($endDate) && $selectedMonth && preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    // Load from snapshot
    $snapshotModel = new SnapshotModel();
    $supplies = $snapshotModel->getSnapshotData($selectedMonth);
    $isSnapshot = true;
    
    // Format supplies to match expected structure
    $formattedSupplies = [];
    foreach ($supplies as $supply) {
        $formattedSupplies[] = [
            'supply_id' => $supply['supply_id'],
            'stock_no' => $supply['stock_no'],
            'item' => $supply['item'],
            'category' => $supply['category'],
            'unit' => $supply['unit'],
            'description' => $supply['description'],
            'quantity' => $supply['quantity'],
            'previous_month' => $supply['previous_month'] ?? 0,
            'unit_cost' => $supply['unit_cost'],
            'total_cost' => $supply['total_cost'],
            'status' => $supply['status']
        ];
    }
    $supplies = $formattedSupplies;
} else {
    // Load current/live data
    $model = new SupplyModel();
    $supplies = $model->getAllSupplies();
    if (empty($selectedMonth)) $selectedMonth = date('Y-m');
}

// Filter out specific high-value categories as requested by USER
// These items are managed in a separate report (Property/Equipment)
$excludedCategories = [
    'ICT', 
    'OFFICE EQUIPMENT', 
    'OFFICE FURNITURES',
    'OFFICE FURNITURES & FIXTURES',
    'OFFICE FURNITURES AND FIXTURES',
    'OFFICE FUNITURES AND FIXTURED',
    'MEDICAL EQUIPMENT', 
    'MOTOR SERVICE VEHICLE'
];

$supplies = array_filter($supplies, function($supply) use ($excludedCategories) {
    if (!isset($supply['category'])) return true;
    $cat = strtoupper(trim($supply['category']));
    
    // Check exact matches
    if (in_array($cat, $excludedCategories)) return false;
    
    // Fuzzy check for furniture to be safe
    if (stripos($cat, 'FURNITURE') !== false || stripos($cat, 'FUNITURE') !== false) {
        // If it contains furniture/funiture, exclude it from RPCI
        return false;
    }

    return true;
});

// Log the action
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logAction = "Exported Monthly Inventory Excel for " . ($selectedMonth ?? 'Current');
if (!empty($startDate) && !empty($endDate)) {
    $logAction = "Exported Inventory Excel for Range: $startDate to $endDate";
}
$logModel->log("EXPORT_INVENTORY", $logAction);

// Clear output buffer
if (ob_get_level()) ob_end_clean();

// Set headers for download as Excel file
if (!empty($startDate) && !empty($endDate)) {
    $monthLabel = "_" . $startDate . "_to_" . $endDate;
} else {
    $monthLabel = $selectedMonth ? "_" . $selectedMonth : "_" . date('Y-m');
}
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=RPCI_Inventory_Report' . $monthLabel . '.xls');
header('Pragma: no-cache');
header('Expires: 0');

// Organize data by category
$groupedSupplies = [];
foreach ($supplies as $supply) {
    $category = $supply['category'] ?? 'Uncategorized';
    
    // Normalize Category Name
    $cat = trim($category);
    
    // Only normalize to "OFFICE SUPPLIES" if it's explicitly about supplies
    // Avoid broad "OFFICE" match which catches Furniture/Equipment
    $upperCat = strtoupper($cat);
    if ($upperCat === 'OFFICE' || $upperCat === 'OFFICE SUPPLY' || $upperCat === 'OFFICE SUPPLIES') {
        $cat = 'OFFICE SUPPLIES';
    }
    
    if (!isset($groupedSupplies[$cat])) {
        $groupedSupplies[$cat] = [];
    }
    $groupedSupplies[$cat][] = $supply;
}

// Case-insensitive prioritization for "OFFICE SUPPLIES"
$officeKey = null;
foreach (array_keys($groupedSupplies) as $key) {
    if (strcasecmp($key, 'OFFICE SUPPLIES') === 0) {
        $officeKey = $key;
        break;
    }
}

if ($officeKey) {
    $officeItems = $groupedSupplies[$officeKey];
    unset($groupedSupplies[$officeKey]);
    $groupedSupplies = [$officeKey => $officeItems] + $groupedSupplies;
}

// Start HTML output for Excel (Appendix 66 - RPCI Format)
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>RPCI</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>
        body { font-family: "Arial", sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 4px; vertical-align: middle; }
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
echo '<tr><td colspan="11" class="appendix-label">Appendix 66</td></tr>';

// Formal Header
echo '<tr><td colspan="11" class="header-title">REPORT ON THE PHYSICAL COUNT OF INVENTORIES (RPCI)</td></tr>';
echo '<tr><td colspan="11" class="no-border">&nbsp;</td></tr>';

$currMonthDate = $isSnapshot ? strtotime($selectedMonth . '-01') : time();
$dateLabel = "AS OF:";
$displayMonth = date('F t, Y', $currMonthDate);

if (!empty($startDate) && !empty($endDate)) {
    $dateLabel = "FOR THE PERIOD:";
    $displayMonth = date('M d, Y', strtotime($startDate)) . " TO " . date('M d, Y', strtotime($endDate));
} elseif (!empty($endDate)) {
    $displayMonth = date('F d, Y', strtotime($endDate));
}

echo '<tr>
        <td colspan="6" class="no-border"><b>ENTITY NAME:</b> CITY GOVERNMENT OF BOGO</td>
        <td colspan="5" class="no-border text-right"><b>' . $dateLabel . '</b> ' . strtoupper($displayMonth) . '</td>
      </tr>';
echo '<tr>
        <td colspan="6" class="no-border"><b>FUND CLUSTER:</b> ____________________</td>
        <td colspan="5" class="no-border">&nbsp;</td>
      </tr>';
echo '<tr><td colspan="11" class="no-border">&nbsp;</td></tr>';

// Table Columns (Refined RPCI Structure)
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

$grandTotalValue = 0;

foreach ($groupedSupplies as $category => $items) {
    // Category Header
    echo '<tr>';
    echo '<td colspan="11" class="category-header"><b>CATEGORY: ' . strtoupper($category) . '</b></td>';
    echo '</tr>';
    
    foreach ($items as $supply) {
        $currQty = isset($supply['quantity']) ? (float)$supply['quantity'] : 0;
        $unitCost = isset($supply['unit_cost']) ? (float)$supply['unit_cost'] : 0;
        
        $balancePerCard = $currQty;
        $onHand = $currQty; 
        
        $totalValue = $onHand * $unitCost;
        $grandTotalValue += $totalValue;
        
        echo '<tr>';
        echo '<td></td>'; // Article blank
        echo '<td>' . htmlspecialchars(($supply['item'] ?? '') . ' - ' . ($supply['description'] ?? '')) . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['stock_no'] ?? '') . '</td>';
        echo '<td class="text-center">' . htmlspecialchars($supply['unit'] ?? '') . '</td>';
        echo '<td class="text-right">' . number_format($unitCost, 2) . '</td>';
        echo '<td class="text-center">' . ($balancePerCard > 0 ? $balancePerCard : '-') . '</td>';
        echo '<td class="text-center">' . ($onHand > 0 ? $onHand : '-') . '</td>';
        echo '<td class="text-center"></td>';
        echo '<td class="text-center"></td>';
        echo '<td></td>'; // Remarks blank
        echo '</tr>';
    }
}

// Grand Total Row
echo '<tr>';
echo '<td colspan="6" class="text-right font-bold">TOTAL ON HAND VALUE</td>';
echo '<td colspan="1" class="text-center font-bold"></td>';
echo '<td colspan="1" class="text-center font-bold"></td>';
echo '<td colspan="1" class="text-right font-bold">₱' . number_format($grandTotalValue, 2) . '</td>';
echo '<td colspan="2" class="no-border"></td>';
echo '</tr>';

// Signatories (RPCI Format)
echo '<tr><td colspan="11" class="no-border">&nbsp;</td></tr>';
echo '<tr><td colspan="11" class="no-border">&nbsp;</td></tr>';

echo '<tr>
        <td colspan="3" class="no-border"><b>Certified Correct By:</b></td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="no-border"><b>Approved By:</b></td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="no-border"><b>Verified By:</b></td>
      </tr>';

echo '<tr><td colspan="11" class="no-border" style="height: 40px;">&nbsp;</td></tr>';

echo '<tr>
        <td colspan="3" class="text-center font-bold" style="border:none; border-bottom: 1px solid black;">ELISA M. ARREZA</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="text-center font-bold" style="border:none; border-bottom: 1px solid black;">NILO J. LUMANCAS, PhD</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="text-center font-bold" style="border:none; border-bottom: 1px solid black;">COA REPRESENTATIVE</td>
      </tr>';

echo '<tr>
        <td colspan="3" class="no-border text-center">Administrative Aide VI / Supply Officer</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="no-border text-center">Schools Division Superintendent</td>
        <td colspan="1" class="no-border">&nbsp;</td>
        <td colspan="3" class="no-border text-center">Commission on Audit</td>
      </tr>';

echo '</table>';
echo '</body>';
echo '</html>';
?>
