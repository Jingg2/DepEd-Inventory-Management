<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\submit_direct_assignment_no_stock.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/security.php';
initSecureSession();
require_once __DIR__ . '/../model/requisitionModel.php';
require_once __DIR__ . '/../model/employeeModel.php';

// Get the logged-in admin's ID from session
$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: No admin session found.']);
    exit();
}

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

// 1. Verify Employee and get Department ID
$empModel = new EmployeeModel();
$employee = $empModel->getEmployeeById($employeeInfo['id']);

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Invalid Employee ID']);
    exit();
}

// 2. Prepare data for RequisitionModel
$assignmentData = [
    'employee_id' => $employee['employee_id'],
    'department_id' => $employee['department_id'],
    'request_date' => $employeeInfo['date'] ?? date('Y-m-d'),
    'purpose' => $employeeInfo['purpose'] ?? 'Existing asset recording (Inventory unaffected)',
    'approved_by' => $adminId // Use logged-in admin's ID
];

$reqModel = new RequisitionModel();
$result = $reqModel->saveDirectAssignmentNoStock($assignmentData, $items);

echo json_encode($result);
?>
