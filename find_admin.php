<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT employee_id FROM employee WHERE role = 'Admin' LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row ? $row['employee_id'] : 'NONE';
?>
