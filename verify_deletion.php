<?php
require_once __DIR__ . '/db/database.php';

$db = new Database();
$conn = $db->getConnection();

$tables = [
    'request_item',
    'supply_history',
    'school_deliveries',
    'monthly_inventory_snapshot',
    'rsmi_snapshot',
    'waste_items',
    'supply'
];

echo "Verifying deletion results:\n";
foreach ($tables as $table) {
    $stmt = $conn->query("SELECT COUNT(*) FROM `$table` ");
    $count = $stmt->fetchColumn();
    echo "Table: $table - Count: $count\n";
}
?>
