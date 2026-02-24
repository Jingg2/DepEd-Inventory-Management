<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\verify_rpci_accuracy.php
// Verification script to validate RPCI report accuracy based on DB columns
require_once __DIR__ . '/model/supplyModel.php';
require_once __DIR__ . '/db/database.php';

$month = $_GET['month'] ?? date('Y-m');
$db = (new Database())->getConnection();

// Load supplies
$model = new SupplyModel();
$supplies = $model->getAllSupplies();

echo "<html><head><style>
body { font-family: Arial; margin: 20px; background: #f8f9fa; }
h1 { color: #065f46; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
th { background: #065f46; color: white; padding: 8px 12px; text-align: center; border: 1px solid #333; font-size: 11px; }
td { padding: 6px 12px; border: 1px solid #ddd; font-size: 11px; }
tr:nth-child(even) { background: #f7fdf9; }
.num { text-align: right; }
.warn { background: #fef3c7 !important; }
.mismatch-val { background: #fee2e2 !important; color: #dc2626; font-weight: bold; }
.ok { color: #065f46; font-weight: bold; }
.mismatch { color: #dc2626; font-weight: bold; }
.summary { background: #ecfdf5; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #10b981; }
</style></head><body>";

echo "<h1>🔍 RPCI Column Consistency Check — $month</h1>";
echo "<p>This script verifies that: <code>Previous Month + Acquisition - Issuance = Current Balance</code></p>";

// Categorize items
$excludedCategories = ['ICT', 'OFFICE EQUIPMENT', 'OFFICE FURNITURES', 'OFFICE FURNITURES & FIXTURES', 'OFFICE FURNITURES AND FIXTURES', 'OFFICE FUNITURES AND FIXTURED', 'MEDICAL EQUIPMENT', 'MOTOR SERVICE VEHICLE'];
$rpciItems = [];
$issuesFound = 0;

foreach ($supplies as $supply) {
    $cat = strtoupper(trim($supply['category'] ?? ''));
    if (in_array($cat, $excludedCategories) || strpos($cat, 'FURNITURE') !== false) continue;
    if (isset($supply['property_classification'])) {
        $pc = strtoupper($supply['property_classification']);
        if (strpos($pc, 'PPE') !== false || strpos($pc, 'HIGH VALUE') !== false) continue;
        if ($cat !== 'TEST' && strpos($pc, 'PROPERTY') !== false) continue;
    }

    $prev = (float)($supply['previous_month'] ?? 0);
    $acq = (float)($supply['requisition'] ?? 0);
    $iss = (float)($supply['issuance'] ?? 0);
    $qty = (float)($supply['quantity'] ?? 0);

    // Formula: prev + acq - iss = qty
    $calculatedBal = $prev + $acq - $iss;
    $hasIssue = abs($calculatedBal - $qty) > 0.001; // Float comparison

    if ($hasIssue) $issuesFound++;

    $rpciItems[] = [
        'item' => $supply['item'],
        'stock_no' => $supply['stock_no'],
        'prev' => $prev,
        'acq' => $acq,
        'iss' => $iss,
        'qty' => $qty,
        'calc' => $calculatedBal,
        'has_issue' => $hasIssue
    ];
}

// Summary
echo "<div class='summary'>";
echo "<strong>Total RPCI Items:</strong> " . count($rpciItems) . " | ";
echo "<strong>Consistency Mismatches:</strong> <span class='" . ($issuesFound > 0 ? 'mismatch' : 'ok') . "'>$issuesFound</span>";
echo "</div>";

// Table
echo "<table>";
echo "<tr>
    <th>Stock No</th>
    <th>Item Description</th>
    <th>Previous Month Balance</th>
    <th>Acquisition (requisition)</th>
    <th>Issuance for Month</th>
    <th>Expected Balance</th>
    <th>Actual Balance (quantity)</th>
    <th>Status</th>
</tr>";

foreach ($rpciItems as $r) {
    $rowClass = $r['has_issue'] ? 'warn' : '';
    $valClass = $r['has_issue'] ? 'mismatch-val' : 'num';
    
    echo "<tr class='$rowClass'>";
    echo "<td>" . htmlspecialchars($r['stock_no']) . "</td>";
    echo "<td>" . htmlspecialchars($r['item']) . "</td>";
    echo "<td class='num'>" . number_format($r['prev'], 0) . "</td>";
    echo "<td class='num'>" . number_format($r['acq'], 0) . "</td>";
    echo "<td class='num'>" . number_format($r['iss'], 0) . "</td>";
    echo "<td class='num'><strong>" . number_format($r['calc'], 0) . "</strong></td>";
    echo "<td class='$valClass'><strong>" . number_format($r['qty'], 0) . "</strong></td>";
    echo "<td class='" . ($r['has_issue'] ? 'mismatch' : 'ok') . "'>" . ($r['has_issue'] ? '❌ MISMATCH' : '✅ OK') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</body></html>";
?>
