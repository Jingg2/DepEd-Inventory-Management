<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\departmentModel.php
class DepartmentModel {
    private $conn;
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function getAllDepartments() {
        $sql = "SELECT * FROM department ORDER BY department_name ASC";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all departments error: " . $e->getMessage());
            return [];
        }
    }

    public function insertDepartment($departmentName) {
        $sql = "INSERT INTO department (department_name) VALUES (:department_name)";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':department_name', $departmentName);
            $stmt->execute();
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Insert department error: " . $e->getMessage());
            return false;
        }
    }
}
?>
