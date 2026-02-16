<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();

echo "Table: supply\n";
$stmt = $conn->query("DESCRIBE supply");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\nTable: requisition\n";
$stmt = $conn->query("DESCRIBE requisition");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
