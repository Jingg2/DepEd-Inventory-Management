<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\update_requisition_status.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/security.php';
initSecureSession();
requireAuth();

require_once __DIR__ . '/../model/requisitionModel.php';

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$requisitionId = $data['id'] ?? '';
$action = $data['action'] ?? ''; // 'Approved' or 'Rejected'

if (empty($requisitionId) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing ID or action']);
    exit();
}

$model = new RequisitionModel();
$result = $model->updateRequisitionStatus($requisitionId, $action, $_SESSION['admin_id'] ?? 1);

if ($result['success']) {
    require_once __DIR__ . '/../model/SystemLogModel.php';
    $logModel = new SystemLogModel();
    $logModel->log("UPDATE_REQUEST_STATUS", "Updated status of requisition ID: $requisitionId to $action");
}

echo json_encode($result);
?>
