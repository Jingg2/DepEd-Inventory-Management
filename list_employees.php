<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT employee_id, role, first_name, last_name FROM employee LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
?>
