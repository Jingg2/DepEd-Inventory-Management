<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();

echo "--- Duplicate Stock Nos ---\n";
$stmt = $conn->query("SELECT stock_no, COUNT(*) as count FROM supply GROUP BY stock_no HAVING count > 1");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

echo "\n--- Duplicate Items (Same Name and Cost) ---\n";
$stmt = $conn->query("SELECT item, unit_cost, COUNT(*) as count FROM supply GROUP BY item, unit_cost HAVING count > 1");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
?>
