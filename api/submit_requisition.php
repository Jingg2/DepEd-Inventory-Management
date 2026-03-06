<?php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../model/requisitionModel.php';
require_once __DIR__ . '/../model/employeeModel.php';

ob_clean();

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$employeeInfo = $data['employee'] ?? null;
$items = $data['items'] ?? [];

if (!$employeeInfo || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Missing employee info or items']);
    exit();
}

// Verify Employee and get Department ID
$empModel = new EmployeeModel();
$employee = $empModel->getEmployeeById($employeeInfo['id']);

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Invalid Employee ID: ' . ($employeeInfo['id'] ?? 'none')]);
    exit();
}

// Prepare data for RequisitionModel
$requisitionData = [
    'employee_id'   => $employee['employee_id'],
    'department_id' => $employee['department_id'] ?? null,
    'request_date'  => $employeeInfo['date'] ?? date('Y-m-d'),
    'purpose'       => $employeeInfo['purpose'] ?? 'General supply request'
];

$reqModel = new RequisitionModel();
$result = $reqModel->saveRequisition($requisitionData, $items);

echo json_encode($result);
?>
