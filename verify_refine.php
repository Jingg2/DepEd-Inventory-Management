<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();

echo "--- Refined Verification Results ---\n";
$stmt = $conn->query("SELECT COUNT(*) FROM supply");
echo "Total records in supply: " . $stmt->fetchColumn() . "\n";

echo "--- Property Classifications ---\n";
$stmt = $conn->query("SELECT property_classification, COUNT(*) as count FROM supply GROUP BY property_classification");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "Class: {$row['property_classification']} - Count: {$row['count']}\n";
}

echo "--- High Value Items (Sample) ---\n";
$stmt = $conn->query("SELECT item, unit_cost, property_classification FROM supply WHERE unit_cost > 5000 LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "{$row['item']} | Cost: {$row['unit_cost']} | Class: {$row['property_classification']}\n";
}

echo "\n--- Redundancy Check (Duplicates) ---\n";
$stmt = $conn->query("SELECT item, unit_cost, COUNT(*) as count FROM supply GROUP BY item, unit_cost HAVING count > 1");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "No redundant items found. De-duplication successful.\n";
} else {
    print_r($rows);
}
?>
