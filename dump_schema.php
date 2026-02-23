<?php
// We'll use the Database class directly
require_once 'db/database.php';
$db = new Database();
$pdo = $db->getConnection();

function dumpTable($pdo, $table) {
    echo "--- $table ---\n";
    try {
        $stmt = $pdo->query("DESC $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

dumpTable($pdo, 'supply');
dumpTable($pdo, 'supply_history');
dumpTable($pdo, 'monthly_inventory_snapshot');
?>
