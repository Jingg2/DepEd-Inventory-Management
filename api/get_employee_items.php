<?php
ob_start();
require_once __DIR__ . '/../model/employeeModel.php';
require_once __DIR__ . '/../includes/security.php';

// Ensure session is started properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic Auth Check
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Use ob_clean() before outputting JSON to remove any previous output
ob_clean();
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit;
}

try {
    $model = new EmployeeModel();
    $items = $model->getEmployeeHeldItemsForICS($_GET['id']);
    
    echo json_encode(['success' => true, 'data' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
