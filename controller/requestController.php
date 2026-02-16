<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\controller\requestController.php
require_once __DIR__ . '/../model/requisitionModel.php';

class RequestController {
    private $model;

    public function __construct() {
        $this->model = new RequisitionModel();
    }

    public function handleRequest() {
        // Fetch all requisitions and stats for the view
        return [
            'requisitions' => $this->model->getAllRequisitions(),
            'stats' => $this->model->getRequisitionStats()
        ];
    }

    public function getItems($requisitionId) {
        return $this->model->getRequisitionItems($requisitionId);
    }
}
?>
