<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\get_snapshots.php
require_once __DIR__ . '/../model/snapshotModel.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');
initSecureSession();
requireAuth();

$model = new SnapshotModel();
$snapshots = $model->getAvailableSnapshots();

echo json_encode(['success' => true, 'snapshots' => $snapshots]);
