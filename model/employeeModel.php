<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\employeeModel.php
class EmployeeModel {
    private $conn;
    private $db;
    public $lastError = '';

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function getAllEmployees() {
        $sql = "SELECT e.*, d.department_name,
                (SELECT COUNT(*) FROM request_item ri 
                 JOIN supply s ON ri.supply_id = s.supply_id 
                 JOIN requisition r ON ri.requisition_id = r.requisition_id
                 WHERE r.employee_id = e.employee_id 
                 AND s.property_classification LIKE 'Semi-Expendable%' 
                 AND ri.issued_quantity > 0 AND r.status = 'Approved') as held_items_count
                FROM employee e 
                LEFT JOIN department d ON e.department_id = d.department_id 
                ORDER BY e.created_at DESC";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all employees error: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function getEmployeeHeldItemsForICS($employeeId) {
        $sql = "SELECT ri.*, s.item as item_name, s.unit, s.stock_no, s.unit_cost, s.description, r.approved_date
                FROM request_item ri
                JOIN supply s ON ri.supply_id = s.supply_id
                JOIN requisition r ON ri.requisition_id = r.requisition_id
                WHERE r.employee_id = ? 
                AND s.property_classification LIKE 'Semi-Expendable%' 
                AND ri.issued_quantity > 0 AND r.status = 'Approved'
                ORDER BY r.approved_date DESC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$employeeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Employee Held Items For ICS Error: " . $e->getMessage());
            return [];
        }
    }

    public function insertEmployee($data) {
        $sql = "INSERT INTO employee (employee_id, first_name, last_name, position, department_id, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $data['employee_id']);
            $stmt->bindValue(2, $data['first_name']);
            $stmt->bindValue(3, $data['last_name']);
            $stmt->bindValue(4, $data['position']);
            $stmt->bindValue(5, $data['department_id'], PDO::PARAM_INT);
            $stmt->bindValue(6, $data['role']);
            $stmt->bindValue(7, $data['status']);
            $stmt->bindValue(8, date('Y-m-d H:i:s'));
            
            $result = $stmt->execute();
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = $errorInfo[2] ?? 'Unknown error';
                return false;
            }
            return true;
        } catch (PDOException $e) {
            error_log("Insert employee error: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getEmployeeById($id) {
        $sql = "SELECT e.*, d.department_name 
                FROM employee e 
                LEFT JOIN department d ON e.department_id = d.department_id 
                WHERE e.employee_id = ? 
                LIMIT 1";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get employee by ID error: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    public function getLastInsertedEmployee() {
        $sql = "SELECT e.*, d.department_name 
                FROM employee e 
                LEFT JOIN department d ON e.department_id = d.department_id 
                ORDER BY e.created_at DESC LIMIT 1";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get last employee error: " . $e->getMessage());
            return null;
        }
    }

    public function updateEmployee($data) {
        $sql = "UPDATE employee 
                SET first_name = ?, last_name = ?, position = ?, department_id = ?, role = ?, status = ?
                WHERE employee_id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['position'],
                $data['department_id'],
                $data['role'],
                $data['status'],
                $data['employee_id']
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = $errorInfo[2] ?? 'Unknown error';
                return false;
            }
            return true;
        } catch (PDOException $e) {
            error_log("Update employee error: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function deleteEmployee($id) {
        try {
            $this->conn->beginTransaction();

            // 1. Delete associated request_items first (these are linked via requisition)
            // We find all requisition IDs for this employee
            $getReqIdsSql = "SELECT requisition_id FROM requisition WHERE employee_id = ?";
            $getReqIdsStmt = $this->conn->prepare($getReqIdsSql);
            $getReqIdsStmt->execute([$id]);
            $reqIds = $getReqIdsStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($reqIds)) {
                $placeholders = implode(',', array_fill(0, count($reqIds), '?'));
                
                // 1a. Delete from request_item
                $delItemsSql = "DELETE FROM request_item WHERE requisition_id IN ($placeholders)";
                $delItemsStmt = $this->conn->prepare($delItemsSql);
                $delItemsStmt->execute($reqIds);

                // 2. Delete from requisition
                $delReqsSql = "DELETE FROM requisition WHERE employee_id = ?";
                $delReqsStmt = $this->conn->prepare($delReqsSql);
                $delReqsStmt->execute([$id]);
            }

            // 3. Delete from waste_items (if any)
            $delWasteSql = "DELETE FROM waste_items WHERE employee_id = ?";
            $delWasteStmt = $this->conn->prepare($delWasteSql);
            $delWasteStmt->execute([$id]);

            // 4. Finally delete the employee record
            $sql = "DELETE FROM employee WHERE employee_id = ?";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$id]);

            $this->conn->commit();
            return $result;

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Delete employee cascade error: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    public function returnItem($supplyId, $employeeId, $quantity, $condition, $reason, $requestItemId) {
        try {
            $this->conn->beginTransaction();

            // 1. Validate Request Item
            $riSql = "SELECT issued_quantity FROM request_item WHERE request_item_id = ? AND issued_quantity >= ?";
            $riStmt = $this->conn->prepare($riSql);
            $riStmt->execute([$requestItemId, $quantity]);
            
            if ($riStmt->rowCount() == 0) {
                throw new Exception("Invalid return quantity or item not found.");
            }

            // 2. Decrement issued quantity ...
            $updRiSql = "UPDATE request_item SET issued_quantity = issued_quantity - ? WHERE request_item_id = ?";
            $updRiStmt = $this->conn->prepare($updRiSql);
            $updRiStmt->execute([$quantity, $requestItemId]);

            // Initialize SystemLog
            require_once __DIR__ . '/SystemLogModel.php';
            $logModel = new SystemLogModel();
            
            // Get Supply Info for logging
            $supSql = "SELECT item FROM supply WHERE supply_id = ?";
            $supStmt = $this->conn->prepare($supSql);
            $supStmt->execute([$supplyId]);
            $supplyItem = $supStmt->fetchColumn();

            if ($condition === 'Functional') {
                // 3a. Return to Stock
                $updSupplySql = "UPDATE supply SET quantity = quantity + ? WHERE supply_id = ?";
                $updSupplyStmt = $this->conn->prepare($updSupplySql);
                $updSupplyStmt->execute([$quantity, $supplyId]);
                
                // Log return to stock
                $logModel->log("RETURN_ITEM_STOCK", "Returned $quantity x $supplyItem from Employee $employeeId to Stock. Reason: $reason");
            } else {
                // 3b. Log to Waste Items
                $wasteSql = "INSERT INTO waste_items (supply_id, employee_id, quantity, reason, condition_status, date_returned) 
                             VALUES (?, ?, ?, ?, ?, NOW())";
                $wasteStmt = $this->conn->prepare($wasteSql);
                $wasteStmt->execute([$supplyId, $employeeId, $quantity, $reason, $condition]);
                
                // Log waste
                $logModel->log("RETURN_ITEM_WASTE", "Returned $quantity x $supplyItem from Employee $employeeId to Waste ($condition). Reason: $reason");
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Item returned successfully.'];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Return Item Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

