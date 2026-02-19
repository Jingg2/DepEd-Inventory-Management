<?php
// api/update_condition.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/security.php';
initSecureSession();

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../model/supplyModel.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;
$condition = $input['condition'] ?? null;

if (!$id || !$condition) {
    echo json_encode(['success' => false, 'message' => 'ID and Condition are required']);
    exit;
}

try {
    $model = new SupplyModel();
    $result = $model->updateDeliveryItemCondition($id, $condition);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Condition updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update condition']);
    }
} catch (Exception $e) {
    error_log("Update condition error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
