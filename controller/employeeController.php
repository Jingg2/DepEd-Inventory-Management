<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\controller\employeeController.php
require_once __DIR__ . '/../model/employeeModel.php';
require_once __DIR__ . '/../model/departmentModel.php';

class EmployeeController {
    private $model;
    private $deptModel;

    public function __construct() {
        $this->model = new EmployeeModel();
        $this->deptModel = new DepartmentModel();
    }

    public function handleRequest() {
        $message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate required fields
            $errors = [];
            if (empty($_POST['first_name'])) $errors[] = 'First Name is required';
            if (empty($_POST['last_name'])) $errors[] = 'Last Name is required';
            if (empty($_POST['position'])) $errors[] = 'Position is required';
            if (empty($_POST['department_id'])) $errors[] = 'Department ID is required';
            
            // Explicit check for AJAX header or hidden field
            $isAjax = (isset($_POST['ajax']) && $_POST['ajax'] === '1') || 
                      (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

            if (!empty($errors)) {
                if ($isAjax) {
                    if (ob_get_level()) ob_end_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
                    exit();
                } else {
                    $message = implode('. ', $errors);
                }
            } else {
                // Handle custom department
                $departmentId = $_POST['department_id'];
                
                if ($departmentId === 'Other') {
                    // Create new department
                    if (empty($_POST['custom_department'])) {
                        $errors[] = 'Department name is required when selecting "Other"';
                        if ($isAjax) {
                            if (ob_get_level()) ob_end_clean();
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
                            exit();
                        } else {
                            $message = implode('. ', $errors);
                        }
                    } else {
                        $customDepartmentName = trim($_POST['custom_department']);
                        $newDepartmentId = $this->deptModel->insertDepartment($customDepartmentName);
                        
                        if (!$newDepartmentId) {
                            if ($isAjax) {
                                if (ob_get_level()) ob_end_clean();
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => 'Failed to create new department']);
                                exit();
                            } else {
                                $message = 'Failed to create new department';
                            }
                        } else {
                            $departmentId = $newDepartmentId;
                        }
                    }
                }
                
                // Only proceed if no errors occurred during department creation
                if (empty($errors) && $message === '') {
                    $data = [
                        'employee_id' => trim($_POST['employee_id']),
                        'first_name' => trim($_POST['first_name']),
                        'last_name' => trim($_POST['last_name']),
                        'position' => trim($_POST['position']),
                        'department_id' => (int)$departmentId,
                        'role' => $_POST['role'] ?? 'Staff',
                        'status' => $_POST['status'] ?? 'Active'
                    ];

                    $result = $this->model->insertEmployee($data);

                    if ($result) {
                        if ($isAjax) {
                            $newEmployee = $this->model->getLastInsertedEmployee();
                            if (ob_get_level()) ob_end_clean();
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'employee' => $newEmployee]);
                            exit();
                        } else {
                            header("Location: employees?success=1");
                            exit();
                        }
                    } else {
                        if ($isAjax) {
                            if (ob_get_level()) ob_end_clean();
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $this->model->lastError]);
                            exit();
                        } else {
                            $message = $this->model->lastError;
                        }
                    }
                }
            }
        }

        // Fetch all employees and departments for the view
        return [
            'employees' => $this->model->getAllEmployees(),
            'departments' => $this->deptModel->getAllDepartments(),
            'message' => $message
        ];
    }
}
?>
