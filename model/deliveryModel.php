<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\deliveryModel.php
class DeliveryModel {
    private $conn;
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Save a complete delivery with its items
     */
    public function saveDelivery($data, $items, $adminId = 1) {
        try {
            $this->conn->beginTransaction();

            // 1. Insert Delivery Header
            $sql = "INSERT INTO deliveries (
                        receipt_no, school, address, delivery_date, delivered_by, 
                        received_by_officer, received_by_librarian, supplier, total_amount
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['receipt_no'] ?? null,
                $data['school'] ?? null,
                $data['address'] ?? null,
                $data['delivery_date'] ?? date('Y-m-d'),
                $data['delivered_by'] ?? null,
                $data['received_by_officer'] ?? null,
                $data['received_by_librarian'] ?? null,
                $data['supplier'] ?? 'Inspiration Publishing Co.',
                $data['total_amount'] ?? 0
            ]);
            
            $deliveryId = $this->conn->lastInsertId();

            // 2. Insert Items (Supplies)
            require_once __DIR__ . '/supplyModel.php';
            $supplyModel = new SupplyModel();

            foreach ($items as $item) {
                // Ensure delivery_id is linked
                $item['delivery_id'] = $deliveryId;
                $item['admin_id'] = $adminId;
                $item['school'] = $data['school'] ?? $item['school'] ?? null;
                $item['status'] = 'Available';
                
                // Use supplyModel's insert logic (slightly modified to support delivery_id)
                $success = $this->insertDeliveryItem($item, $supplyModel);
                if (!$success) {
                    throw new Exception("Failed to insert item: " . ($item['item'] ?? 'Unknown'));
                }
            }

            $this->conn->commit();
            return ['success' => true, 'delivery_id' => $deliveryId];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Save Delivery Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Helper to insert item specifically from a delivery
     */
    private function insertDeliveryItem($data, $supplyModel) {
        // Validation
        if (empty($data['item'])) return false;

        $unit_cost = (float)($data['unit_cost'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 0);
        $total_cost = $unit_cost * $quantity;
        $updated_at = date('Y-m-d H:i:s');
        $stock_no = !empty($data['stock_no']) ? $data['stock_no'] : "AUTO-" . time() . "-" . rand(10, 99);

        $sql = "INSERT INTO supply (
                    delivery_id, stock_no, category, unit, item, description, 
                    quantity, unit_cost, total_cost, status, updated_by, 
                    updated_at, property_classification, school
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['delivery_id'],
            $stock_no,
            $data['category'] ?? 'Miscellaneous',
            $data['unit'] ?? 'pcs',
            $data['item'],
            $data['description'] ?? '',
            $quantity,
            $unit_cost,
            $total_cost,
            $data['status'],
            $data['admin_id'],
            $updated_at,
            $data['property_classification'] ?? null,
            $data['school']
        ]);

        if ($result) {
            $newId = $this->conn->lastInsertId();
            // Record history using a custom remark
            $remarks = "Delivered via Receipt No: " . ($data['receipt_no'] ?? 'N/A');
            
            // We need a way to call recordSupplyHistory from SupplyModel or duplicate logic
            // Since recordSupplyHistory is private, we'll use a direct insert here for speed/safety within transaction
            $sqlHist = "INSERT INTO supply_history (
                            supply_id, add_stock, quantity_change, previous_quantity, 
                            new_quantity, type, remarks, created_at, updated_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->conn->prepare($sqlHist)->execute([
                $newId, $quantity, $quantity, 0, $quantity, 'Receipt', 
                $remarks, $updated_at, $data['admin_id']
            ]);
            return true;
        }
        return false;
    }

    public function getAllDeliveries() {
        $sql = "SELECT * FROM deliveries ORDER BY delivery_date DESC, created_at DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeliveryById($id) {
        $sql = "SELECT * FROM deliveries WHERE delivery_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($delivery) {
            $sqlItems = "SELECT * FROM supply WHERE delivery_id = ?";
            $stmtItems = $this->conn->prepare($sqlItems);
            $stmtItems->execute([$id]);
            $delivery['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $delivery;
    }
}
