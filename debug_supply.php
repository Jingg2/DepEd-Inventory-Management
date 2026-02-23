<?php
require_once 'model/supplyModel.php';
require_once 'model/snapshotModel.php';

$model = new SupplyModel();
$supplies = $model->getAllSupplies();

echo "--- LIVE SUPPLY TABLE CHECK ---\n";
$found = false;
foreach ($supplies as $s) {
    if (strpos($s['item'], 'Ballpen') !== false) {
        echo "Item: " . $s['item'] . "\n";
        echo "previous_month: [" . ($s['previous_month'] ?? 'NULL') . "]\n";
        echo "add_stock: [" . ($s['add_stock'] ?? 'NULL') . "]\n";
        echo "issuance: [" . ($s['issuance'] ?? 'NULL') . "]\n";
        echo "quantity: [" . ($s['quantity'] ?? 'NULL') . "]\n";
        $found = true;
        break;
    }
}
if (!$found) echo "Ballpen not found in live table.\n";

$snapshotModel = new SnapshotModel();
if ($snapshotModel->snapshotExists('2026-02')) {
    echo "\n--- SNAPSHOT 2026-02 CHECK ---\n";
    $snapData = $snapshotModel->getSnapshotData('2026-02');
    $foundSnap = false;
    foreach ($snapData as $s) {
        if (strpos($s['item'], 'Ballpen') !== false) {
            echo "Item: " . $s['item'] . "\n";
            echo "previous_month: [" . ($s['previous_month'] ?? 'NULL') . "]\n";
            echo "add_stock: [" . ($s['add_stock'] ?? 'NULL') . "]\n";
            echo "issuance: [" . ($s['issuance'] ?? 'NULL') . "]\n";
            echo "quantity: [" . ($s['quantity'] ?? 'NULL') . "]\n";
            $foundSnap = true;
            break;
        }
    }
    if (!$foundSnap) echo "Ballpen not found in snapshot.\n";
}
?>
