<?php
require_once 'db/database.php';
$db = new Database();
$conn = $db->getConnection();

function showTable($conn, $table) {
    echo "\nTable: $table\n";
    try {
        $stmt = $conn->query("DESCRIBE $table");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($results as $row) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

showTable($conn, 'supply');
showTable($conn, 'delivery_items');
showTable($conn, 'deliveries');
showTable($conn, 'items');
