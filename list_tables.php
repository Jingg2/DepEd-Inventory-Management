<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Table: $table\n";
    $stmtCol = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'supply_id'");
    if ($stmtCol->rowCount() > 0) {
        echo "  -> has supply_id\n";
    }
}
?>
