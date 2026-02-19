<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\requisitionModel.php
class RequisitionModel {
    private $conn;
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function saveRequisition($data, $items) {
        try {
            $this->conn->beginTransaction();

            // 1. Generate Requisition No
            $requisitionNo = $this->generateRequisitionNo();

            // 2. Insert Requisition Header
            $sql = "INSERT INTO requisition (requisition_no, employee_id, department_id, request_date, purpose, status) 
                    VALUES (?, ?, ?, ?, ?, 'Pending')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $requisitionNo,
                $data['employee_id'],
                $data['department_id'],
                $data['request_date'],
                $data['purpose']
            ]);

            $requisitionId = $this->conn->lastInsertId();

            // 3. Insert Requisition Items
            $itemSql = "INSERT INTO request_item (requisition_id, supply_id, quantity, status) 
                        VALUES (?, ?, ?, 'Requested')";
            $itemStmt = $this->conn->prepare($itemSql);

            foreach ($items as $item) {
                $itemStmt->execute([
                    $requisitionId,
                    $item['id'],
                    $item['requestQty']
                ]);
            }

            $this->conn->commit();
            return ['success' => true, 'requisition_no' => $requisitionNo];

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Save Requisition Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAllRequisitions() {
        $sql = "SELECT r.*, e.first_name, e.last_name, d.department_name,
                GROUP_CONCAT(s.item SEPARATOR ', ') as item_list,
                GROUP_CONCAT(s.description SEPARATOR ', ') as description_list,
                GROUP_CONCAT(ri.quantity SEPARATOR ', ') as quantity_list,
                SUM(CASE WHEN s.property_classification LIKE 'Semi-Expendable%' THEN 1 ELSE 0 END) as semi_expendable_count
                FROM requisition r
                JOIN employee e ON r.employee_id = e.employee_id
                JOIN department d ON r.department_id = d.department_id
                LEFT JOIN request_item ri ON r.requisition_id = ri.requisition_id
                LEFT JOIN supply s ON ri.supply_id = s.supply_id
                GROUP BY r.requisition_id
                ORDER BY r.created_at DESC";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get All Requisitions Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRequisitionItems($requisitionId) {
        $sql = "SELECT ri.*, s.item as item_name, s.unit, r.requisition_no
                FROM request_item ri
                JOIN supply s ON ri.supply_id = s.supply_id
                JOIN requisition r ON ri.requisition_id = r.requisition_id
                WHERE ri.requisition_id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$requisitionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Requisition Items Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRequisitionById($id) {
        $sql = "SELECT r.*, e.first_name, e.last_name, d.department_name, 
                       adm.first_name as approver_first, adm.last_name as approver_last, adm.username as approver_username
                FROM requisition r
                JOIN employee e ON r.employee_id = e.employee_id
                JOIN department d ON r.department_id = d.department_id
                LEFT JOIN admin adm ON r.approved_by = adm.admin_id
                WHERE r.requisition_id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Requisition By ID Error: " . $e->getMessage());
            return null;
        }
    }

    public function getRequisitionItemsForICS($requisitionId) {
        $sql = "SELECT ri.*, s.item as item_name, s.unit, s.stock_no, s.unit_cost, s.description
                FROM request_item ri
                JOIN supply s ON ri.supply_id = s.supply_id
                WHERE ri.requisition_id = ? AND s.property_classification LIKE 'Semi-Expendable%' AND ri.issued_quantity > 0";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$requisitionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Requisition Items For ICS Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRequisitionStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                FROM requisition";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Requisition Stats Error: " . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'processing' => 0, 'approved' => 0, 'rejected' => 0];
        }
    }

    public function getRequisitionItemsWithStock($requisitionId) {
        $sql = "SELECT ri.*, s.item as item_name, s.unit, s.quantity as current_stock,
                       s.description, s.category, s.stock_no, s.unit_cost, s.image, s.status as item_status,
                       s.property_classification, s.low_stock_threshold, s.critical_stock_threshold
                FROM request_item ri
                JOIN supply s ON ri.supply_id = s.supply_id
                WHERE ri.requisition_id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$requisitionId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert image binary data to base64 for display
            foreach ($items as &$item) {
                try {
                    if (!empty($item['image'])) {
                        $item['image_base64'] = 'data:image/jpeg;base64,' . base64_encode($item['image']);
                    } else {
                        $item['image_base64'] = null; // Let JS handle the default
                    }
                } catch (Exception $e) {
                    $item['image_base64'] = null;
                }
                unset($item['image']); // Remove binary data from response
            }

            return $items;
        } catch (PDOException $e) {
            error_log("Get Requisition Items With Stock Error: " . $e->getMessage());
            return [];
        }
    }

    public function issueRequisitionItems($requisitionId, $items, $adminId = 1) {
        try {
            $this->conn->beginTransaction();

            // 1. Check if requisition exists and is in correct state
            $sql = "SELECT status FROM requisition WHERE requisition_id = ? FOR UPDATE";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$requisitionId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) throw new Exception("Requisition not found.");
            if ($req['status'] !== 'Processing' && $req['status'] !== 'Pending') {
                throw new Exception("Requisition must be Pending or Processing.");
            }

            // 2. Process each item issuance
            foreach ($items as $item) {
                $riId = $item['request_item_id'];
                $issuedQty = (int)$item['issued_quantity'];
                $remarks = $item['remarks'] ?? '';

                // Get original request item info
                $riSql = "SELECT supply_id FROM request_item WHERE request_item_id = ?";
                $riStmt = $this->conn->prepare($riSql);
                $riStmt->execute([$riId]);
                $ri = $riStmt->fetch(PDO::FETCH_ASSOC);
                if (!$ri) throw new Exception("Request item $riId not found.");

                $supplyId = $ri['supply_id'];

                // Check stock
                $sSql = "SELECT quantity, item FROM supply WHERE supply_id = ? FOR UPDATE";
                $sStmt = $this->conn->prepare($sSql);
                $sStmt->execute([$supplyId]);
                $supply = $sStmt->fetch(PDO::FETCH_ASSOC);

                if (!$supply || $supply['quantity'] < $issuedQty) {
                    throw new Exception("Insufficient stock for: " . ($supply['item'] ?? "ID $supplyId"));
                }

                // Deduct stock
                $updSqp = "UPDATE supply SET quantity = quantity - ? WHERE supply_id = ?";
                $updStmt = $this->conn->prepare($updSqp);
                $updStmt->execute([$issuedQty, $supplyId]);

                // Update request_item
                $riUpdSql = "UPDATE request_item SET issued_quantity = ?, remarks = ?, status = 'Issued' WHERE request_item_id = ?";
                $riUpdStmt = $this->conn->prepare($riUpdSql);
                $riUpdStmt->execute([$issuedQty, $remarks, $riId]);
            }

            // 3. Update Requisition Header
            $updReqSql = "UPDATE requisition SET status = 'Approved', approved_by = ?, approved_date = NOW() WHERE requisition_id = ?";
            $updReqStmt = $this->conn->prepare($updReqSql);
            $updReqStmt->execute([$adminId, $requisitionId]);

            $this->conn->commit();
            return ['success' => true, 'message' => "Requisition items issued and approved successfully."];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log("Issue Requisition Items Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateRequisitionStatus($requisitionId, $status, $adminId = 1) {
        try {
            $this->conn->beginTransaction();

            // 1. Get current requisition details
            $sql = "SELECT status FROM requisition WHERE requisition_id = ? FOR UPDATE";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$requisitionId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                throw new Exception("Requisition not found.");
            }

            // Define valid transitions
            $currentStatus = $req['status'];
            if ($status === 'Processing') {
                if ($currentStatus !== 'Pending') {
                    throw new Exception("Only Pending requests can be moved to Processing.");
                }
            } elseif ($status === 'Approved') {
                if ($currentStatus !== 'Processing' && $currentStatus !== 'Pending') {
                    throw new Exception("Request must be Pending or Processing to be Approved.");
                }
            } elseif ($status === 'Rejected') {
                if ($currentStatus !== 'Pending' && $currentStatus !== 'Processing') {
                    throw new Exception("Request is already " . $currentStatus);
                }
            }

            // 2. If approving, check and deduct stock
            if ($status === 'Approved') {
                $items = $this->getRequisitionItems($requisitionId);
                foreach ($items as $item) {
                    // Check stock
                    $checkSql = "SELECT quantity, item FROM supply WHERE supply_id = ? FOR UPDATE";
                    $checkStmt = $this->conn->prepare($checkSql);
                    $checkStmt->execute([$item['supply_id']]);
                    $supply = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$supply || $supply['quantity'] < $item['quantity']) {
                        throw new Exception("Insufficient stock for item: " . ($supply['item'] ?? 'Unknown ID ' . $item['supply_id']));
                    }

                    // Deduct stock
                    $updateStockSql = "UPDATE supply SET quantity = quantity - ? WHERE supply_id = ?";
                    $updateStockStmt = $this->conn->prepare($updateStockSql);
                    $updateStockStmt->execute([$item['quantity'], $item['supply_id']]);
                    
                    // Update request_item status
                    $updateItemStatusSql = "UPDATE request_item SET status = 'Approved' WHERE request_item_id = ?";
                    $updateItemStmt = $this->conn->prepare($updateItemStatusSql);
                    $updateItemStmt->execute([$item['request_item_id']]);
                }
            } elseif ($status === 'Rejected') {
                 // Update request_item status to Rejected
                 $updateItemStatusSql = "UPDATE request_item SET status = 'Rejected' WHERE requisition_id = ?";
                 $updateItemStmt = $this->conn->prepare($updateItemStatusSql);
                 $updateItemStmt->execute([$requisitionId]);
            } elseif ($status === 'Processing') {
                // Update request_item status to Processing
                $updateItemStatusSql = "UPDATE request_item SET status = 'Processing' WHERE requisition_id = ?";
                $updateItemStmt = $this->conn->prepare($updateItemStatusSql);
                $updateItemStmt->execute([$requisitionId]);
            }

            // 3. Update Requisition Header
            $updateReqSql = "UPDATE requisition SET status = ?, approved_by = ?, approved_date = NOW() WHERE requisition_id = ?";
            $updateReqStmt = $this->conn->prepare($updateReqSql);
            $updateReqStmt->execute([$status, $adminId, $requisitionId]);

            $this->conn->commit();
            return ['success' => true, 'message' => "Requisition set to $status successfully."];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Update Requisition Status Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getPendingRequisitions($limit = 10) {
        $sql = "SELECT r.*, e.first_name, e.last_name, d.department_name
                FROM requisition r
                JOIN employee e ON r.employee_id = e.employee_id
                JOIN department d ON r.department_id = d.department_id
                WHERE r.status = 'Pending'
                ORDER BY r.created_at DESC
                LIMIT :limit";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Pending Requisitions Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRequisitionsPerEmployee($limit = 10) {
        $sql = "SELECT e.first_name, e.last_name, COUNT(r.requisition_id) as requisition_count
                FROM employee e
                LEFT JOIN requisition r ON e.employee_id = r.employee_id
                GROUP BY e.employee_id, e.first_name, e.last_name
                ORDER BY requisition_count DESC
                LIMIT :limit";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Requisitions Per Employee Error: " . $e->getMessage());
            return [];
        }
    }

    private function generateRequisitionNo() {
        // Format: REQ-YYYYMMDD-XXXX
        $date = date('Ymd');
        $prefix = "REQ-" . $date . "-";
        
        $sql = "SELECT requisition_no FROM requisition WHERE requisition_no LIKE ? ORDER BY requisition_id DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($last) {
            $lastNum = (int)substr($last['requisition_no'], -4);
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return $prefix . $newNum;
    }
}

