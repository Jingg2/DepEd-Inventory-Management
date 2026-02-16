<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\get_snapshots.php
require_once __DIR__ . '/../model/snapshotModel.php';

header('Content-Type: application/json');

$model = new SnapshotModel();

// Auto-create current month snapshot if it doesn't exist
$model->autoCreateCurrentMonthSnapshot();

// Get available snapshots
$snapshots = $model->getAvailableSnapshots();

// Add current month option (live data)
$currentMonth = date('Y-m');
$response = [
    'current_month' => $currentMonth,
    'snapshots' => $snapshots,
    'success' => true
];

echo json_encode($response);
?>
