<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query('SHOW CREATE TABLE supply');
echo $stmt->fetchColumn(1);
?>
