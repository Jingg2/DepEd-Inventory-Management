<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\submit_issuance.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/security.php';
initSecureSession();
requireAuth();

require_once __DIR__ . '/../model/requisitionModel.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['id']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid issuance data']);
    exit();
}

$model = new RequisitionModel();
$result = $model->issueRequisitionItems($data['id'], $data['items'], $_SESSION['admin_id'] ?? 1);

if ($result['success']) {
    require_once __DIR__ . '/../model/SystemLogModel.php';
    $logModel = new SystemLogModel();
    $logModel->log("APPROVE_REQUEST", "Approved and issued items for requisition ID: " . $data['id']);
}

echo json_encode($result);
?>
