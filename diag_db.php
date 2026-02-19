<?php
require_once 'db/database.php';
$db = new Database();
$conn = $db->getConnection();

$tables = ['employee', 'requisition', 'request_item', 'waste_items'];
$output = "";

foreach ($tables as $table) {
    $output .= "\n--- $table ---\n";
    try {
        $stmt = $conn->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            $output .= "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Key']}\n";
        }
    } catch (Exception $e) {
        $output .= "Error checking $table: " . $e->getMessage() . "\n";
    }
}

file_put_contents('diag_output.txt', $output);
echo "Done\n";
