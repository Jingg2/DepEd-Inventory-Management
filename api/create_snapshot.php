<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\create_snapshot.php
require_once __DIR__ . '/../model/snapshotModel.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');
initSecureSession();
requireAuth();

$month = $_GET['month'] ?? date('Y-m');
$model = new SnapshotModel();

$success = $model->createMonthlySnapshot($month);
$rsmiSuccess = $model->createRSMISnapshot($month);

if ($success && $rsmiSuccess) {
    echo json_encode(['success' => true, 'message' => "Snapshot for $month created successfully."]);
} else {
    echo json_encode(['success' => false, 'message' => "Failed to create snapshot for $month."]);
}
