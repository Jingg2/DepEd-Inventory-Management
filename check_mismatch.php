<?php
require_once 'db/database.php';
$db = new Database();
$pdo = $db->getConnection();

echo "1. Admin Table Sample:\n";
$stmt = $pdo->prepare("SELECT * FROM admin LIMIT 5");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n2. Employee Table Sample:\n";
$stmt = $pdo->prepare("SELECT * FROM employee LIMIT 5");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n3. Checking if any admin_id matches an employee_id:\n";
$stmt = $pdo->prepare("SELECT admin_id, username FROM admin");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($admins as $a) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE employee_id = ?");
    $st->execute([$a['admin_id']]);
    $exists = $st->fetchColumn();
    echo "Admin " . $a['username'] . " (ID " . $a['admin_id'] . ") exists as employee: " . ($exists ? "YES" : "NO") . "\n";
}
?>
