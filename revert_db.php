<?php
require_once 'db/database.php';
$db = new Database();
$conn = $db->getConnection();
try {
    $conn->exec("ALTER TABLE supply DROP COLUMN delivery_id");
    echo "Successfully dropped delivery_id from supply table.\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
