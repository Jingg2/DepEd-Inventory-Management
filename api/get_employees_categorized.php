<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../model/employeeModel.php';

try {
    $model = new EmployeeModel();
    $employees = $model->getAllEmployees();
    
    $departments = [];
    foreach ($employees as $emp) {
        $deptName = $emp['department_name'] ?? 'Other / No Department';
        if (!isset($departments[$deptName])) {
            $departments[$deptName] = [];
        }
        $departments[$deptName][] = $emp;
    }
    
    // Sort departments alphabetically
    ksort($departments);
    
    echo json_encode([
        'success' => true,
        'departments' => $departments
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
