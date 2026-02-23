<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\debug_accuracy.php
require_once __DIR__ . '/model/supplyModel.php';
require_once __DIR__ . '/db/database.php';

$id = $argv[1] ?? null;
$month = $argv[2] ?? date('Y-m');

if (!$id) {
    echo "Usage: php debug_accuracy.php <id> [month]\n";
    exit;
}

$db = (new Database())->getConnection();
echo "<pre><h1>Debug Accuracy for Supply ID: $id (Month: $month)</h1>";

// 1. Check Official Columns
$stmt = $db->prepare("SELECT quantity, previous_month, add_stock, issuance FROM supply WHERE supply_id = ?");
$stmt->execute([$id]);
$supply = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<h3>1. Official Supply Table Data</h3>";
print_r($supply);

// 2. Check Supply History (Acquisitions & Manual Deductions)
echo "<h3>2. Supply History (Raw) for $month</h3>";
$stmt = $db->prepare("SELECT * FROM supply_history WHERE supply_id = ? AND created_at LIKE ? ORDER BY created_at");
$stmt->execute([$id, $month . '%']);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$calcAcq = 0;
$calcDed = 0;

foreach ($history as $h) {
    if ($h['quantity_change'] > 0) {
        $calcAcq += $h['quantity_change'];
        echo "[ACQ] Date: {$h['created_at']} | Change: {$h['quantity_change']} | Type: {$h['type']}\n";
    } elseif ($h['quantity_change'] < 0) {
        $calcDed += abs($h['quantity_change']);
        echo "[DED] Date: {$h['created_at']} | Change: {$h['quantity_change']} | Type: {$h['type']}\n";
    }
}
echo "<strong>Calculated Acquisitions: $calcAcq</strong>\n";
echo "<strong>Calculated Manual Deductions: $calcDed</strong>\n";

// 3. Check Requisition Issuances for this month
echo "<h3>3. Requisition Issuances for $month</h3>";
$sql = "SELECT r.requisition_id, r.approved_date, r.created_at, ri.issued_quantity, r.status 
        FROM request_item ri 
        JOIN requisition r ON ri.requisition_id = r.requisition_id 
        WHERE ri.supply_id = ? AND r.status = 'Approved' AND r.approved_date LIKE ?
        ORDER BY r.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$id, $month . '%']);
$requisitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$calcIss = 0;
foreach ($requisitions as $r) {
    $calcIss += (float)$r['issued_quantity'];
    echo "[REQ] ID: {$r['requisition_id']} | Approved: {$r['approved_date']} | Qty: {$r['issued_quantity']} | Status: {$r['status']}\n";
}
echo "<strong>Calculated Requisition Issuances: $calcIss</strong>\n";

// 4. Final Logic Simulation
echo "<h3>4. Final Report Logic Simulation</h3>";
echo "Official Acq: " . ($supply['add_stock'] ?? 'NULL') . "\n";
echo "Official Iss: " . ($supply['issuance'] ?? 'NULL') . "\n";
echo "Calculated Acq: $calcAcq\n";
echo "Calculated Iss (Req + Manual Deductions): " . ($calcIss + $calcDed) . "\n";

$finalAcq = ($supply['add_stock'] > 0) ? $supply['add_stock'] : $calcAcq;
$finalIss = ($supply['issuance'] > 0) ? $supply['issuance'] : ($calcIss + $calcDed);

echo "<strong>REPORT WILL SHOW ACQ: $finalAcq</strong>\n";
echo "<strong>REPORT WILL SHOW ISS: $finalIss</strong>\n";
