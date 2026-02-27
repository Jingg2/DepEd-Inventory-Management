<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\delete_requisition.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/security.php';
initSecureSession();
requireAuth();

require_once __DIR__ . '/../model/requisitionModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data or missing ID']);
    exit();
}

$requisitionId = $data['id'];

$model = new RequisitionModel();
$result = $model->deleteRequisition($requisitionId);

if ($result['success']) {
    $logModel = new SystemLogModel();
    $logModel->log("DELETE_REQUISITION", "Deleted requisition ID: $requisitionId and restored stock.");
}

echo json_encode($result);
?>
