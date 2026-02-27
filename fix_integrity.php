<?php
require_once 'db/database.php';
$db = new Database();
$pdo = $db->getConnection();

echo "1. Checking if employee ID 1 exists...\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE employee_id = 1");
$stmt->execute();
$exists = $stmt->fetchColumn();
echo "ID 1 exists: " . ($exists ? "YES" : "NO") . "\n\n";

echo "2. Checking for supply records with updated_by = 1...\n";
$stmt = $pdo->prepare("SELECT supply_id, item, updated_by FROM supply WHERE updated_by = 1");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($rows) . " records with invalid updated_by = 1.\n";
foreach ($rows as $r) {
    echo "Supply ID: " . $r['supply_id'] . " | Item: " . $r['item'] . "\n";
}

echo "\n3. Checking for supply_history records with updated_by = 1...\n";
$stmt = $pdo->prepare("SELECT history_id, supply_id, updated_by FROM supply_history WHERE updated_by = 1");
$stmt->execute();
$rowsH = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($rowsH) . " history records with invalid updated_by = 1.\n";

echo "\n4. Fixing invalid IDs to NULL...\n";
$pdo->exec("UPDATE supply SET updated_by = NULL WHERE updated_by = 1");
$pdo->exec("UPDATE supply_history SET updated_by = NULL WHERE updated_by = 1");
echo "Update complete.\n";
?>
