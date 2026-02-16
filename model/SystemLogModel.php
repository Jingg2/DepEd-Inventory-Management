<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\SystemLogModel.php

class SystemLogModel {
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $db = new Database();
        $this->conn = $db->getConnection();
        
        // Auto-create table if it doesn't exist
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS system_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $this->conn->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating system_logs table: " . $e->getMessage());
        }
    }

    public function log($action, $details = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $admin_id = $_SESSION['admin_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        
        $sql = "INSERT INTO system_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$admin_id, $action, $details, $ip]);
        } catch (PDOException $e) {
            error_log("Logging error: " . $e->getMessage());
            return false;
        }
    }

    public function getLogs($limit = 50, $offset = 0) {
        $sql = "SELECT l.*, a.username 
                FROM system_logs l 
                LEFT JOIN admin a ON l.admin_id = a.admin_id 
                ORDER BY l.created_at DESC 
                LIMIT ? OFFSET ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get logs error: " . $e->getMessage());
            return [];
        }
    }

    public function getTotalCount() {
        $sql = "SELECT COUNT(*) FROM system_logs";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function clearLogs() {
        $sql = "DELETE FROM system_logs";
        try {
            $this->conn->exec($sql);
            $this->log('CLEAR_LOGS', 'Manually cleared all system logs.');
            return true;
        } catch (PDOException $e) {
            error_log("Clear logs error: " . $e->getMessage());
            return false;
        }
    }
}
