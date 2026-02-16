<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\BackupModel.php
require_once __DIR__ . '/../db/database.php';

class BackupModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Generates a SQL dump of the database
     * @return string SQL content
     */
    public function generateBackup() {
        $tables = [];
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- Inventory System Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop table statement
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";

            // Create table statement
            $res = $this->conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $sql .= $res['Create Table'] . ";\n\n";

            // Data insertion
            $res = $this->conn->query("SELECT * FROM `$table` ");
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $sql .= "INSERT INTO `$table` VALUES(";
                $values = [];
                foreach ($row as $value) {
                    if (isset($value)) {
                        $values[] = $this->conn->quote($value);
                    } else {
                        $values[] = 'NULL';
                    }
                }
                $sql .= implode(",", $values) . ");\n";
            }
            $sql .= "\n\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    /**
     * Restores the database from SQL content
     * @param string $sqlContent
     * @return array Status and message
     */
    public function restoreBackup($sqlContent) {
        try {
            // Basic validation - check if it looks like our SQL
            if (empty($sqlContent) || stripos($sqlContent, 'INSERT INTO') === false && stripos($sqlContent, 'CREATE TABLE') === false) {
                return ['success' => false, 'message' => 'Invalid backup file format.'];
            }

            // Split into individual queries
            // A more robust splitter would be better, but this works for basic dumps
            $queries = explode(";\n", $sqlContent);
            
            $this->conn->beginTransaction();
            $this->conn->exec("SET FOREIGN_KEY_CHECKS=0");

            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $this->conn->exec($query);
                }
            }

            $this->conn->exec("SET FOREIGN_KEY_CHECKS=1");
            $this->conn->commit();

            return ['success' => true, 'message' => 'Database restored successfully!'];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Restore error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()];
        }
    }

    /**
     * Clears system logs
     * @return bool
     */
    public function clearLogs() {
        try {
            return $this->conn->exec("TRUNCATE TABLE system_logs") !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
