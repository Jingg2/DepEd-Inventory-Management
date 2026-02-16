<?php
require_once __DIR__ . '/../includes/security.php';
initSecureSession();
requireAuth();

require_once __DIR__ . '/../model/requisitionModel.php';

header('Content-Type: application/json');

try {
    $model = new RequisitionModel();
    $stats = $model->getRequisitionStats();
    
    // Get the latest pending request ID to detect "newness" more accurately than just count
    $pending = $model->getPendingRequisitions(1);
    $latestId = !empty($pending) ? $pending[0]['requisition_id'] : 0;
    
    echo json_encode([
        'success' => true,
        'count' => (int)($stats['pending'] ?? 0),
        'latest_id' => (int)$latestId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
