<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\verify_rpci_accuracy.php
// Verification script to validate RPCI report accuracy
require_once __DIR__ . '/model/supplyModel.php';
require_once __DIR__ . '/model/snapshotModel.php';
require_once __DIR__ . '/db/database.php';

$month = $_GET['month'] ?? date('Y-m');
$db = (new Database())->getConnection();

$targetMonthEnd = date('Y-m-t 23:59:59', strtotime($month . '-01'));

// --- Same queries as export_supply_excel.php ---
// 1. Monthly Acquisitions
$acqStmt = $db->prepare("SELECT supply_id, SUM(quantity_change) as total_acq FROM supply_history WHERE type IN ('Receipt', 'Adjustment') AND quantity_change > 0 AND created_at LIKE ? GROUP BY supply_id");
$acqStmt->execute([$month . '%']);
$monthAcquisitions = $acqStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Post-month Acquisitions
$postAcqStmt = $db->prepare("SELECT supply_id, SUM(quantity_change) as total_acq FROM supply_history WHERE type IN ('Receipt', 'Adjustment') AND quantity_change > 0 AND created_at > ? GROUP BY supply_id");
$postAcqStmt->execute([$targetMonthEnd]);
$postAcquisitions = $postAcqStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Monthly Deductions
$dedStmt = $db->prepare("SELECT supply_id, SUM(ABS(quantity_change)) as total_ded FROM supply_history WHERE quantity_change < 0 AND created_at LIKE ? GROUP BY supply_id");
$dedStmt->execute([$month . '%']);
$monthDeductions = $dedStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Monthly Requisition Issuances
$issStmt = $db->prepare("SELECT ri.supply_id, SUM(ri.issued_quantity) as total_iss FROM request_item ri JOIN requisition r ON ri.requisition_id = r.requisition_id WHERE r.status = 'Approved' AND ri.issued_quantity > 0 AND r.approved_date LIKE ? GROUP BY ri.supply_id");
$issStmt->execute([$month . '%']);
$monthIssuances = $issStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 5. Post-month Deductions
$postDedStmt = $db->prepare("SELECT supply_id, SUM(ABS(quantity_change)) as total_ded FROM supply_history WHERE quantity_change < 0 AND created_at > ? GROUP BY supply_id");
$postDedStmt->execute([$targetMonthEnd]);
$postDeductions = $postDedStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 6. Post-month Requisition Issuances
$postIssStmt = $db->prepare("SELECT ri.supply_id, SUM(ri.issued_quantity) as total_iss FROM request_item ri JOIN requisition r ON ri.requisition_id = r.requisition_id WHERE r.status = 'Approved' AND ri.issued_quantity > 0 AND r.approved_date > ? GROUP BY ri.supply_id");
$postIssStmt->execute([$targetMonthEnd]);
$postIssuances = $postIssStmt->fetchAll(PDO::FETCH_KEY_PAIR);

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
.ok { color: #065f46; font-weight: bold; }
.mismatch { color: #dc2626; font-weight: bold; }
.summary { background: #ecfdf5; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #10b981; }
</style></head><body>";

echo "<h1>🔍 RPCI Accuracy Verification — $month</h1>";

// Categorize items
$excludedCategories = ['ICT', 'OFFICE EQUIPMENT', 'OFFICE FURNITURES', 'OFFICE FURNITURES & FIXTURES', 'OFFICE FURNITURES AND FIXTURES', 'OFFICE FUNITURES AND FIXTURED', 'MEDICAL EQUIPMENT', 'MOTOR SERVICE VEHICLE'];
$rpciItems = [];
$issuesFound = 0;

foreach ($supplies as &$supply) {
    $cat = strtoupper(trim($supply['category'] ?? ''));
    if (in_array($cat, $excludedCategories) || strpos($cat, 'FURNITURE') !== false) continue;
    if (isset($supply['property_classification'])) {
        $pc = strtoupper($supply['property_classification']);
        if (strpos($pc, 'PPE') !== false || strpos($pc, 'HIGH VALUE') !== false) continue;
        if ($cat !== 'TEST' && strpos($pc, 'PROPERTY') !== false) continue;
    }

    $id = $supply['supply_id'];
    $dbPrev = isset($supply['previous_month']) ? (float)$supply['previous_month'] : null;
    $dbAcq = isset($supply['add_stock']) ? (float)$supply['add_stock'] : null;
    $dbIss = isset($supply['issuance']) ? (float)$supply['issuance'] : null;

    $liveQty = (float)($supply['quantity'] ?? 0);
    $postAcq = (float)($postAcquisitions[$id] ?? 0);
    $postIss = (float)($postIssuances[$id] ?? 0);
    $postDed = (float)($postDeductions[$id] ?? 0);
    $eomBal = $liveQty - $postAcq + $postIss + $postDed;

    $acqMonth = (float)($monthAcquisitions[$id] ?? 0);
    $issMonth = (float)($monthIssuances[$id] ?? 0);
    $dedMonth = (float)($monthDeductions[$id] ?? 0);
    $totalIss = $issMonth + $dedMonth;
    $prevCalc = $eomBal - $acqMonth + $totalIss;

    // Use DB values directly; fall back only when NULL
    $finalAcq = ($dbAcq !== null) ? $dbAcq : $acqMonth;
    $finalIss = ($dbIss !== null) ? $dbIss : $totalIss;
    $finalPrev = ($dbPrev !== null) ? $dbPrev : $prevCalc;
    $reportedBal = $finalPrev + $finalAcq - $finalIss;

    // Consistency check: balance should not be negative
    $hasIssue = ($reportedBal < 0) || ($finalPrev < 0);

    if ($hasIssue) $issuesFound++;

    $rpciItems[] = [
        'item' => $supply['item'],
        'live_qty' => $liveQty,
        'db_prev' => $dbPrev,
        'db_acq' => $dbAcq,
        'db_iss' => $dbIss,
        'calc_prev' => $prevCalc,
        'calc_acq' => $acqMonth,
        'calc_iss' => $totalIss,
        'final_prev' => $finalPrev,
        'final_acq' => $finalAcq,
        'final_iss' => $finalIss,
        'reported_bal' => $reportedBal,
        'has_issue' => $hasIssue,
        'source' => ($dbPrev !== null) ? 'Database' : 'Calculated',
    ];
}

// Summary
echo "<div class='summary'>";
echo "<strong>Total RPCI Items:</strong> " . count($rpciItems) . " | ";
echo "<strong>Issues Found:</strong> <span class='" . ($issuesFound > 0 ? 'mismatch' : 'ok') . "'>$issuesFound</span> | ";
echo "<strong>Month:</strong> $month";
echo "</div>";

// Table
echo "<table>";
echo "<tr>
    <th>Item</th>
    <th>Live Qty</th>
    <th>Source</th>
    <th>DB Prev</th>
    <th>DB Acq</th>
    <th>DB Iss</th>
    <th>Calc Prev</th>
    <th>Calc Acq</th>
    <th>Calc Iss</th>
    <th>→ Final Prev</th>
    <th>→ Final Acq</th>
    <th>→ Final Iss</th>
    <th>→ Reported Bal</th>
    <th>Status</th>
</tr>";

foreach ($rpciItems as $r) {
    $rowClass = $r['has_issue'] ? 'warn' : '';
    echo "<tr class='$rowClass'>";
    echo "<td>" . htmlspecialchars($r['item']) . "</td>";
    echo "<td class='num'>" . $r['live_qty'] . "</td>";
    echo "<td>" . $r['source'] . "</td>";
    echo "<td class='num'>" . ($r['db_prev'] ?? '-') . "</td>";
    echo "<td class='num'>" . ($r['db_acq'] ?? '-') . "</td>";
    echo "<td class='num'>" . ($r['db_iss'] ?? '-') . "</td>";
    echo "<td class='num'>" . $r['calc_prev'] . "</td>";
    echo "<td class='num'>" . $r['calc_acq'] . "</td>";
    echo "<td class='num'>" . $r['calc_iss'] . "</td>";
    echo "<td class='num'><strong>" . $r['final_prev'] . "</strong></td>";
    echo "<td class='num'><strong>" . $r['final_acq'] . "</strong></td>";
    echo "<td class='num'><strong>" . $r['final_iss'] . "</strong></td>";
    echo "<td class='num'><strong>" . $r['reported_bal'] . "</strong></td>";
    echo "<td class='" . ($r['has_issue'] ? 'mismatch' : 'ok') . "'>" . ($r['has_issue'] ? '⚠ CHECK' : '✓ OK') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</body></html>";
?>
