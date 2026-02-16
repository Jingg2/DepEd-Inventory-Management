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
                ORDER BY e.employee_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get last employee error: " . $e->getMessage());
            return null;
        }
    }
}
?>
