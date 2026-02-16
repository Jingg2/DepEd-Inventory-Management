<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\get_stock_card_history.php
header('Content-Type: application/json');
require_once __DIR__ . '/../model/supplyModel.php';

$id = $_GET['id'] ?? null;
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit();
}

$model = new SupplyModel();
$history = $model->getSupplyTransactionHistory($id, $from, $to);

if ($history) {
    echo json_encode(['success' => true, 'data' => $history]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch history or supply not found']);
}
?>
