<?php
require_once __DIR__ . '/db/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Ordered list of tables to delete from
    $tables = [
        'request_item',
        'supply_history',
        'school_deliveries',
        'monthly_inventory_snapshot',
        'rsmi_snapshot',
        'waste_items',
        'supply'
    ];
    
    echo "Starting data deletion sequence...\n";
    
    $conn->beginTransaction();
    
    // Disable foreign key checks temporarily to ensure clean deletion
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($tables as $table) {
        $stmt = $conn->query("DELETE FROM `$table` ");
        $rowCount = $stmt->rowCount();
        echo "Deleted $rowCount records from table: $table\n";
    }
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $conn->commit();
    echo "\nAll data deleted successfully.\n";

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error during deletion: " . $e->getMessage() . "\n";
    exit(1);
}
?>
