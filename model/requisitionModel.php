<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\requisitionModel.php
class RequisitionModel {
    private $conn;
    private $db;
    private $supplyModel;

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        require_once __DIR__ . '/supplyModel.php';
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->supplyModel = new SupplyModel();
        $this->ensureStockTypeColumnExists();
    }

    private function ensureStockTypeColumnExists() {
        try {
            // stock_type: 'New' (default) or 'Returned'
            $this->conn->exec("ALTER TABLE request_item ADD COLUMN IF NOT EXISTS stock_type VARCHAR(20) DEFAULT 'New' AFTER quantity");
        } catch (Exception $e) {
            error_log("Failed to ensure stock_type column: " . $e->getMessage());
        }
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
        $itemSql = "INSERT INTO request_item (requisition_id, supply_id, quantity, stock_type, status) 
                    VALUES (?, ?, ?, ?, 'Requested')";
        $itemStmt = $this->conn->prepare($itemSql);
        
        foreach ($items as $item) {
            $itemStmt->execute([
                $requisitionId,
                $item['id'],
                $item['requestQty'],
                $item['stockType'] ?? 'New'
            ]);

            // Sync Monthly Totals (Acquisition/Issuance)
            $this->syncSupplyTotals($item['id']);
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

    public function saveDirectAssignment($data, $items) {
        try {
            $this->conn->beginTransaction();

            // 1. Generate Requisition No
            $requisitionNo = $this->generateRequisitionNo();

            // 2. Insert Requisition Header (Already Approved)
            $sql = "INSERT INTO requisition (requisition_no, employee_id, department_id, request_date, approved_date, purpose, status, approved_by) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Approved', ?)";
            $stmt = $this->conn->prepare($sql);
            
            $issueDate = $data['request_date'] ?? date('Y-m-d');
            $approvedDate = ($issueDate == date('Y-m-d')) ? date('Y-m-d H:i:s') : ($issueDate . ' 09:00:00');

            $stmt->execute([
                $requisitionNo,
                $data['employee_id'],
                $data['department_id'],
                $issueDate,
                $approvedDate,
                $data['purpose'] ?? 'Direct issuance of items',
                $data['approved_by'] ?? 1
            ]);

            $requisitionId = $this->conn->lastInsertId();

            // 3. Insert and Issue Items
            foreach ($items as $item) {
                $qty = (int)$item['requestQty'];
                $stockType = $item['stockType'] ?? 'New';
                $supplyId = $item['id'];

                // Insert into request_item as Issued
                $riSql = "INSERT INTO request_item (requisition_id, supply_id, quantity, issued_quantity, stock_type, status) 
                          VALUES (?, ?, ?, ?, ?, 'Issued')";
                $riStmt = $this->conn->prepare($riSql);
                $riStmt->execute([
                    $requisitionId,
                    $supplyId,
                    $qty,
                    $qty,
                    $stockType
                ]);

                // Deduct from stock and get current total
                if ($stockType === 'Returned') {
                    $updStockSql = "UPDATE supply SET returned_quantity = returned_quantity - ? WHERE supply_id = ?";
                } else {
                    $updStockSql = "UPDATE supply SET quantity = quantity - ? WHERE supply_id = ?";
                }
                
                // Get pre-update quantity for history
                $qStmt = $this->conn->prepare("SELECT quantity, item FROM supply WHERE supply_id = ?");
                $qStmt->execute([$supplyId]);
                $sData = $qStmt->fetch(PDO::FETCH_ASSOC);
                $prevQty = (int)$sData['quantity'];

                $updStockStmt = $this->conn->prepare($updStockSql);
                $updStockStmt->execute([$qty, $supplyId]);
                
                $newQty = $prevQty - ($stockType === 'Returned' ? 0 : $qty);

                // Record History
                $this->supplyModel->recordSupplyHistory(
                    $supplyId, 0, -$qty, $prevQty, $newQty, 'Issuance', 
                    "Direct Assignment: " . $requisitionNo, date('Y-m-d H:i:s'), $data['approved_by'] ?? 1
                );

                // Sync Monthly Totals (for dashboard simplicity)
                if ($stockType !== 'Returned') {
                    $this->syncSupplyTotals($supplyId);
                }
            }

            $this->conn->commit();
            return ['success' => true, 'requisition_no' => $requisitionNo, 'message' => 'Direct assignment recorded and stock updated.'];

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Save Direct Assignment Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function saveDirectAssignmentNoStock($data, $items) {
        try {
            $this->conn->beginTransaction();

            // 1. Generate Requisition No
            $requisitionNo = $this->generateRequisitionNo();

            // 2. Insert Requisition Header (Already Approved)
            $sql = "INSERT INTO requisition (requisition_no, employee_id, department_id, request_date, approved_date, purpose, status, approved_by) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Approved', ?)";
            $stmt = $this->conn->prepare($sql);
            
            $issueDate = $data['request_date'] ?? date('Y-m-d');
            $approvedDate = ($issueDate == date('Y-m-d')) ? date('Y-m-d H:i:s') : ($issueDate . ' 09:00:00');

            $stmt->execute([
                $requisitionNo,
                $data['employee_id'],
                $data['department_id'],
                $issueDate,
                $approvedDate,
                $data['purpose'] ?? 'Existing asset recording (No stock effect)',
                $data['approved_by'] ?? 1
            ]);

            $requisitionId = $this->conn->lastInsertId();

            // 3. Insert and Issue Items (NO STOCK DEDUCTION)
            foreach ($items as $item) {
                $qty = (int)$item['requestQty'];
                $stockType = $item['stockType'] ?? 'New';
                $supplyId = $item['id'];

                // Insert into request_item as Issued
                $riSql = "INSERT INTO request_item (requisition_id, supply_id, quantity, issued_quantity, stock_type, status) 
                          VALUES (?, ?, ?, ?, ?, 'Issued')";
                $riStmt = $this->conn->prepare($riSql);
                $riStmt->execute([
                    $requisitionId,
                    $supplyId,
                    $qty,
                    $qty,
                    $stockType
                ]);

                // SKIPPING: UPDATE supply SET ... (No stock deduction)
            }

            $this->conn->commit();
            return ['success' => true, 'requisition_no' => $requisitionNo, 'message' => 'Direct assignment recorded (inventory unaffected).'];

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Save Direct Assignment No-Stock Error: " . $e->getMessage());
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
            // Repair/Sync all totals for the current month when viewing the list
            $this->syncAllMonthlyTotals();
            
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get All Requisitions Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRequisitionItems($requisitionId) {
        $sql = "SELECT ri.*, s.item as item_name, s.unit, s.unit_cost, s.stock_no 
                FROM request_item ri
                JOIN supply s ON ri.supply_id = s.supply_id
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

    public function getFilteredApprovedRequisitions($deptId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT r.requisition_id, r.requisition_no, 
                       CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                       r.approved_date
                FROM requisition r
                JOIN employee e ON r.employee_id = e.employee_id
                WHERE r.status = 'Approved'";
        
        $params = [];
        if ($deptId) {
            $sql .= " AND r.department_id = ?";
            $params[] = $deptId;
        }
        
        if ($startDate && $endDate) {
            $sql .= " AND DATE(r.approved_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY r.approved_date DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Filtered Approved Requisitions Error: " . $e->getMessage());
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
        $sql = "SELECT ri.*, s.item as item_name, s.unit, 
                       CASE WHEN ri.stock_type = 'Returned' THEN s.returned_quantity ELSE s.quantity END as current_stock,
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
            $sql = "SELECT status, requisition_no FROM requisition WHERE requisition_id = ? FOR UPDATE";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$requisitionId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) throw new Exception("Requisition not found.");
            if ($req['status'] !== 'Processing' && $req['status'] !== 'Pending') {
                throw new Exception("Requisition must be Pending or Processing.");
            }

            // Collect supply IDs to sync after commit
            $supplyIdsToSync = [];

            // 2. Process each item issuance
            foreach ($items as $item) {
                $riId = $item['request_item_id'];
                $issuedQty = (int)$item['issued_quantity'];
                $remarks = $item['remarks'] ?? '';

                // Get original request item info
                $riSql = "SELECT supply_id, stock_type FROM request_item WHERE request_item_id = ?";
                $riStmt = $this->conn->prepare($riSql);
                $riStmt->execute([$riId]);
                $ri = $riStmt->fetch(PDO::FETCH_ASSOC);
                if (!$ri) throw new Exception("Request item $riId not found.");

                $supplyId = $ri['supply_id'];
                $stockType = $ri['stock_type'];

                // Record the final issuance (Stock Out)
                // Get pre-update quantity
                $qStmt = $this->conn->prepare("SELECT quantity FROM supply WHERE supply_id = ? FOR UPDATE");
                $qStmt->execute([$supplyId]);
                $prevQty = (int)$qStmt->fetchColumn();

                if ($stockType === 'Returned') {
                    $updStockSql = "UPDATE supply SET returned_quantity = returned_quantity - ? WHERE supply_id = ?";
                } else {
                    $updStockSql = "UPDATE supply SET quantity = quantity - ? WHERE supply_id = ?";
                }
                
                $updStockStmt = $this->conn->prepare($updStockSql);
                $updStockStmt->execute([$issuedQty, $supplyId]);

                $newQty = $prevQty - ($stockType === 'Returned' ? 0 : $issuedQty);

                // Record History for Ledger
                $this->supplyModel->recordSupplyHistory(
                    $supplyId, 0, -$issuedQty, $prevQty, $newQty, 'Issuance', 
                    "Requisition: " . $req['requisition_no'], date('Y-m-d H:i:s'), $adminId
                );

                // Queue sync for after commit (avoids reading uncommitted data)
                if ($stockType !== 'Returned') {
                    $supplyIdsToSync[] = $supplyId;
                }

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

            // 4. Sync monthly totals AFTER commit so supply_history entries are fully visible
            foreach (array_unique($supplyIdsToSync) as $sid) {
                $this->syncSupplyTotals($sid);
            }

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
            // Update requisition status
            $sql = "UPDATE requisition SET status = ?, updated_at = NOW() WHERE requisition_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$status, $requisitionId]);

            if ($status === 'Rejected') {
                $items = $this->getRequisitionItems($requisitionId);
                foreach ($items as $item) {
                    // Sync Monthly Totals
                    $this->syncSupplyTotals($item['supply_id']);

                    // Update request_item status to Rejected
                    $updateItemStatusSql = "UPDATE request_item SET status = 'Rejected' WHERE request_item_id = ?";
                    $updateItemStmt = $this->conn->prepare($updateItemStatusSql);
                    $updateItemStmt->execute([$item['request_item_id']]);
                }
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

     /**
      * Performs a global sync of Monthly Acquisition and Issuance for all items
      * involved in transactions this month (or a specific month).
      */
     public function syncAllMonthlyTotals($month = null) {
        try {
            // Get ALL supply IDs to ensure everything is synced (even to 0 if no trans this month)
            $sql = "SELECT supply_id FROM supply";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $supplyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($supplyIds as $id) {
                $this->syncSupplyTotals($id, $month);
            }
        } catch (PDOException $e) {
            error_log("Global Sync Error: " . $e->getMessage());
        }
    }

    /**
     * Recalculates requisition (Acquisition) and issuance totals for a specific month
     * and updates the supply table for a specific item.
     * @param int $supplyId
     * @param string|null $month Format YYYY-MM
     */
    private function syncSupplyTotals($supplyId, $month = null) {
        $targetMonth = $month ?: date('Y-m');
        
        try {
            // 1. Calculate Issuance Total (All approved/issued quantities in target month)
            $issSql = "SELECT IFNULL(SUM(ri.issued_quantity), 0) as total
                       FROM request_item ri
                       JOIN requisition r ON ri.requisition_id = r.requisition_id
                       WHERE ri.supply_id = ? 
                       AND r.approved_date LIKE ?
                       AND r.status = 'Approved'
                       AND ri.stock_type = 'New'";
            $issStmt = $this->conn->prepare($issSql);
            $issStmt->execute([$supplyId, $targetMonth . '%']);
            $issTotal = $issStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 2. Calculate Acquisition Total (sum of quantities in acquisition table for target month)
            $acqSql = "SELECT IFNULL(SUM(quantity), 0) as total FROM acquisition 
                       WHERE supply_id = ? AND acquisition_date LIKE ?";
            $acqStmt = $this->conn->prepare($acqSql);
            $acqStmt->execute([$supplyId, $targetMonth . '%']);
            $acqTotal = $acqStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 3. Update Supply table and self-heal previous_month (only if syncing current month)
            // For historical syncs, we only update requisition/issuance columns
            if (!$month || $month === date('Y-m')) {
                $updSql = "UPDATE supply SET 
                           issuance = ?, 
                           requisition = ?, 
                           previous_month = quantity - (? - ?)
                           WHERE supply_id = ?";
                $updStmt = $this->conn->prepare($updSql);
                $updStmt->execute([$issTotal, $acqTotal, $acqTotal, $issTotal, $supplyId]);
            } else {
                // Just sync requisition/issuance if we're preparing for a previous month's snapshot
                $updSql = "UPDATE supply SET issuance = ?, requisition = ? WHERE supply_id = ?";
                $updStmt = $this->conn->prepare($updSql);
                $updStmt->execute([$issTotal, $acqTotal, $supplyId]);
            }

        } catch (PDOException $e) {
            error_log("Sync Supply Totals Error: " . $e->getMessage());
        }
    }

    public function deleteRequisition($requisitionId) {
        try {
            $this->conn->beginTransaction();

            // 1. Get all items in the requisition to restore stock
            $itemsSql = "SELECT supply_id, quantity, issued_quantity, status FROM request_item WHERE requisition_id = ?";
            $itemsStmt = $this->conn->prepare($itemsSql);
            $itemsStmt->execute([$requisitionId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $supplyId = $item['supply_id'];
                $issuedQty = (int)$item['issued_quantity'];
                $stockType = $item['stock_type'] ?? 'New';

                if ($item['status'] === 'Issued' || $item['status'] === 'Approved') {
                    // Get pre-restore quantity
                    $qStmt = $this->conn->prepare("SELECT quantity FROM supply WHERE supply_id = ? FOR UPDATE");
                    $qStmt->execute([$supplyId]);
                    $prevQty = (int)$qStmt->fetchColumn();

                    // Restore to the correct column
                    if ($stockType === 'Returned') {
                        $updStockSql = "UPDATE supply SET returned_quantity = returned_quantity + ? WHERE supply_id = ?";
                    } else {
                        $updStockSql = "UPDATE supply SET quantity = quantity + ? WHERE supply_id = ?";
                    }
                    $updStockStmt = $this->conn->prepare($updStockSql);
                    $updStockStmt->execute([$issuedQty, $supplyId]);

                    $newQty = $prevQty + ($stockType === 'Returned' ? 0 : $issuedQty);

                    // Record History (Correction/Restoration)
                    $this->supplyModel->recordSupplyHistory(
                        $supplyId, 0, $issuedQty, $prevQty, $newQty, 'Correction', 
                        "Restored from deleted Requisition", date('Y-m-d H:i:s')
                    );
                }

                // Sync totals if standard stock
                if ($stockType !== 'Returned') {
                    $this->syncSupplyTotals($supplyId);
                }
            }

            // 2. Delete request items
            $delItemsSql = "DELETE FROM request_item WHERE requisition_id = ?";
            $delItemsStmt = $this->conn->prepare($delItemsSql);
            $delItemsStmt->execute([$requisitionId]);

            // 3. Delete requisition
            $delReqSql = "DELETE FROM requisition WHERE requisition_id = ?";
            $delReqStmt = $this->conn->prepare($delReqSql);
            $delReqStmt->execute([$requisitionId]);

            $this->conn->commit();
            return ['success' => true, 'message' => "Requisition deleted and stock restored successfully."];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Delete Requisition Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

