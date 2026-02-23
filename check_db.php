<?php
require_once 'db/database.php';
$db = new Database();
$pdo = $db->getConnection();

echo "--- supply table ---\n";
$stmt = $pdo->query("DESCRIBE supply");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- supply_history table ---\n";
$stmt = $pdo->query("DESCRIBE supply_history");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Sample supply_history data (recent) ---\n";
$stmt = $pdo->query("SELECT * FROM supply_history ORDER BY history_id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
