<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\get_filtered_requests.php
require_once __DIR__ . '/../model/requisitionModel.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');
initSecureSession();
requireAuth();

$deptId = $_GET['dept_id'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$model = new RequisitionModel();
$requests = $model->getFilteredApprovedRequisitions($deptId, $startDate, $endDate);

echo json_encode(['success' => true, 'requests' => $requests]);
