<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\list_supplies_v2.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db/database.php';

try {
    echo "Connecting...\n";
    $db = (new Database())->getConnection();
    echo "Connected.\n";
    
    $stmt = $db->query("SELECT supply_id, item FROM supply LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($rows) . " rows.\n";
    print_r($rows);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
