<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\list_supplies.php
require_once __DIR__ . '/db/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT supply_id, item FROM supply LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
