<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();

echo "--- Verification Results ---\n";
$stmt = $conn->query("SELECT COUNT(*) FROM supply");
echo "Total records in supply: " . $stmt->fetchColumn() . "\n";

echo "--- Record Counts by Category ---\n";
$stmt = $conn->query("SELECT category, COUNT(*) as count FROM supply GROUP BY category");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "Category: {$row['category']} - Count: {$row['count']}\n";
}

echo "--- Sample Records ---\n";
$stmt = $conn->query("SELECT stock_no, item, quantity, unit_cost, total_cost FROM supply LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "{$row['stock_no']} | {$row['item']} | Qty: {$row['quantity']} | Cost: {$row['unit_cost']} | Total: {$row['total_cost']}\n";
}
?>
