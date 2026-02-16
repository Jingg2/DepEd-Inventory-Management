<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\get_employee.php
header('Content-Type: application/json');
require_once __DIR__ . '/../model/employeeModel.php';

$id = $_GET['id'] ?? '';
error_log("GET_EMPLOYEE API: Looking for ID: " . $id);

$model = new EmployeeModel();

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

$employee = $model->getEmployeeById($id);

if ($employee) {
    echo json_encode(['success' => true, 'employee' => $employee]);
} else {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
}
?>
