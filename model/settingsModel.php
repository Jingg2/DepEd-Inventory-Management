<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\settingsModel.php
class SettingsModel {
    private $conn;
    private $db;

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function getSetting($key, $default = null) {
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$key]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ? $res['setting_value'] : $default;
        } catch (PDOException $e) {
            error_log("Get setting error: " . $e->getMessage());
            return $default;
        }
    }

    public function updateSetting($key, $value) {
        $sql = "INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$key, $value, $value]);
        } catch (PDOException $e) {
            error_log("Update setting error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllSettings() {
        $sql = "SELECT setting_key, setting_value FROM system_settings";
        try {
            $stmt = $this->conn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        } catch (PDOException $e) {
            error_log("Get all settings error: " . $e->getMessage());
            return [];
        }
    }
}
