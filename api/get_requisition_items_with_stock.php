<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\get_requisition_items_with_stock.php
header('Content-Type: application/json');
require_once __DIR__ . '/../model/requisitionModel.php';

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Requisition ID is required']);
    exit();
}

$model = new RequisitionModel();
$items = $model->getRequisitionItemsWithStock($id);

echo json_encode(['success' => true, 'items' => $items]);
?>
