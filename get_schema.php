<?php
require_once 'db/database.php';
$db = new Database();
$pdo = $db->getConnection();

echo "Supply table schema:\n";
$stmt = $pdo->prepare("SHOW CREATE TABLE supply");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nSupply History table schema:\n";
$stmt = $pdo->prepare("SHOW CREATE TABLE supply_history");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
