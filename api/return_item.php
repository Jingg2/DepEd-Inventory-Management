<?php
ob_start();
require_once __DIR__ . '/../model/employeeModel.php';
require_once __DIR__ . '/../includes/security.php';

// Ensure session is started properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic Auth Check (Adjust logic if specific roles needed)
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Use ob_clean() before outputting JSON to remove any previous output
ob_clean();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$required = ['supply_id', 'employee_id', 'quantity', 'condition', 'request_item_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit;
    }
}

$reason = $input['reason'] ?? '';

try {
    $model = new EmployeeModel();
    $result = $model->returnItem(
        $input['supply_id'],
        $input['employee_id'],
        $input['quantity'],
        $input['condition'],
        $reason,
        $input['request_item_id']
    );

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
