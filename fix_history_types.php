<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\fix_history_types.php
require_once __DIR__ . '/db/database.php';

$db = (new Database())->getConnection();

// Analyze ID 935
echo "Analyzing ID 935...\n";
$sql = "SELECT * FROM supply_history WHERE supply_id = 935";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo "ID: {$row['history_id']} | Type: '{$row['type']}' | Remarks: '{$row['remarks']}' | QtyChange: {$row['quantity_change']}\n";
    
    // Check match manually
    if ($row['type'] === 'Receipt' && $row['remarks'] === 'Stock replenished') {
        echo " -> MATCH FOUND! Will update this.\n";
    } else {
        echo " -> No match.\n";
    }
}

echo "\nRunning Update...\n";
// Update 'Receipt' to 'Correction' checks remarks
$sql = "UPDATE supply_history SET type = 'Correction' WHERE type = 'Receipt' AND (remarks = 'Stock replenished' OR remarks = 'Initial Balance / Gap Correction')";
$stmt = $db->prepare($sql);
$stmt->execute();

echo "Updated " . $stmt->rowCount() . " rows.\n";
