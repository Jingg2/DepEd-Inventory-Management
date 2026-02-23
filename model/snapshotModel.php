<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\snapshotModel.php
require_once __DIR__ . '/../db/database.php';

class SnapshotModel {
    private $conn;
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->ensureTablesExist();
    }

    private function ensureTablesExist() {
        $sql1 = "CREATE TABLE IF NOT EXISTS monthly_inventory_snapshot (
            snapshot_id INT AUTO_INCREMENT PRIMARY KEY,
            snapshot_date DATE,
            snapshot_month VARCHAR(7),
            supply_id INT,
            stock_no VARCHAR(50),
            item VARCHAR(255),
            category VARCHAR(100),
            unit VARCHAR(50),
            description TEXT,
            quantity INT,
            previous_month INT DEFAULT 0,
            add_stock INT DEFAULT 0,
            issuance INT DEFAULT 0,
            unit_cost DECIMAL(10,2),
            total_cost DECIMAL(10,2),
            status VARCHAR(50),
            property_classification VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $sql_alter = [
            "ALTER TABLE monthly_inventory_snapshot ADD COLUMN IF NOT EXISTS previous_month INT DEFAULT 0 AFTER quantity",
            "ALTER TABLE monthly_inventory_snapshot ADD COLUMN IF NOT EXISTS add_stock INT DEFAULT 0 AFTER previous_month",
            "ALTER TABLE monthly_inventory_snapshot ADD COLUMN IF NOT EXISTS issuance INT DEFAULT 0 AFTER add_stock"
        ];
        
        $sql2 = "CREATE TABLE IF NOT EXISTS rsmi_snapshot (
            rsmi_id INT AUTO_INCREMENT PRIMARY KEY,
            snapshot_month VARCHAR(7),
            requisition_id INT,
            requisition_no VARCHAR(50),
            supply_id INT,
            stock_no VARCHAR(50),
            item_name VARCHAR(255),
            unit VARCHAR(50),
            issued_quantity INT,
            unit_cost DECIMAL(10,2),
            total_amount DECIMAL(10,2),
            approved_date DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        try {
            $this->conn->exec($sql1);
            foreach ($sql_alter as $alter) {
                $this->conn->exec($alter);
            }
            $this->conn->exec($sql2);
        } catch (Exception $e) {
            error_log("Failed to ensure snapshot tables: " . $e->getMessage());
        }
    }

    /**
     * Create a monthly snapshot for a specific month
     * @param string $month Format: YYYY-MM (e.g., "2025-01")
     * @return bool Success status
     */
    public function createMonthlySnapshot($month = null) {
        try {
            // Use current month if not specified
            if ($month === null) {
                $month = date('Y-m');
            }
            
            // Validate month format
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                error_log("Invalid month format: $month");
                return false;
            }

            $this->conn->beginTransaction();

            // Get all current supplies with perfected columns
            $sql = "SELECT supply_id, stock_no, item, category, unit, description, 
                           quantity, previous_month, add_stock, issuance,
                           unit_cost, total_cost, status, property_classification 
                    FROM supply";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($supplies)) {
                $this->conn->rollBack();
                return false;
            }

            // Delete existing snapshot for this month if exists
            $deleteSql = "DELETE FROM monthly_inventory_snapshot WHERE snapshot_month = ?";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->execute([$month]);

            // Insert new snapshot
            $insertSql = "INSERT INTO monthly_inventory_snapshot 
                         (snapshot_date, snapshot_month, supply_id, stock_no, item, category, 
                          unit, description, quantity, previous_month, add_stock, issuance,
                          unit_cost, total_cost, status, property_classification) 
                         VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->conn->prepare($insertSql);

            foreach ($supplies as $supply) {
                $insertStmt->execute([
                    $month,
                    $supply['supply_id'],
                    $supply['stock_no'],
                    $supply['item'],
                    $supply['category'],
                    $supply['unit'],
                    $supply['description'],
                    $supply['quantity'],
                    $supply['previous_month'],
                    $supply['add_stock'],
                    $supply['issuance'],
                    $supply['unit_cost'],
                    $supply['total_cost'],
                    $supply['status'],
                    $supply['property_classification']
                ]);
            }

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Snapshot creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of available snapshot months
     * @return array Array of months with snapshot data
     */
    public function getAvailableSnapshots() {
        try {
            $sql = "SELECT DISTINCT snapshot_month, 
                           MAX(snapshot_date) as snapshot_date,
                           MAX(created_at) as created_at,
                           COUNT(*) as item_count
                    FROM monthly_inventory_snapshot 
                    GROUP BY snapshot_month 
                    ORDER BY snapshot_month DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get snapshots error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get snapshot data for a specific month
     * @param string $month Format: YYYY-MM
     * @return array Snapshot data
     */
    public function getSnapshotData($month) {
        try {
            $sql = "SELECT * FROM monthly_inventory_snapshot 
                    WHERE snapshot_month = ? 
                    ORDER BY category, item";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get snapshot data error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if snapshot exists for a specific month
     * @param string $month Format: YYYY-MM
     * @return bool True if snapshot exists
     */
    public function snapshotExists($month) {
        try {
            $sql = "SELECT COUNT(*) as count FROM monthly_inventory_snapshot 
                    WHERE snapshot_month = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Check snapshot exists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Auto-create snapshot if current month doesn't have one yet
     * This is called automatically to ensure snapshots are up to date
     */
    public function autoCreateCurrentMonthSnapshot() {
        $currentMonth = date('Y-m');
        $success = true;
        
        if (!$this->snapshotExists($currentMonth)) {
            $success = $this->createMonthlySnapshot($currentMonth);
        }
        
        if (!$this->rsmiSnapshotExists($currentMonth)) {
            $success = $success && $this->createRSMISnapshot($currentMonth);
        }
        
        return $success;
    }

    /**
     * Create a monthly snapshot for RSMI (issuances)
     */
    public function createRSMISnapshot($month = null) {
        try {
            if ($month === null) $month = date('Y-m');
            
            $this->conn->beginTransaction();

            // Delete existing to prevent duplicates
            $deleteStmt = $this->conn->prepare("DELETE FROM rsmi_snapshot WHERE snapshot_month = ?");
            $deleteStmt->execute([$month]);

            // Get all approved issuances for the month
            $sql = "SELECT ri.*, s.item as item_name, s.unit, s.unit_cost, s.stock_no, r.requisition_no, r.approved_date
                    FROM request_item ri
                    JOIN supply s ON ri.supply_id = s.supply_id
                    JOIN requisition r ON ri.requisition_id = r.requisition_id
                    WHERE r.status = 'Approved' AND ri.issued_quantity > 0
                    AND r.approved_date LIKE ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month . '%']);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($items)) {
                $insertSql = "INSERT INTO rsmi_snapshot 
                             (snapshot_month, requisition_id, requisition_no, supply_id, stock_no, 
                              item_name, unit, issued_quantity, unit_cost, total_amount, approved_date) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $this->conn->prepare($insertSql);

                foreach ($items as $item) {
                    $qty = (int)$item['issued_quantity'];
                    $cost = (float)$item['unit_cost'];
                    $insertStmt->execute([
                        $month,
                        $item['requisition_id'],
                        $item['requisition_no'],
                        $item['supply_id'],
                        $item['stock_no'],
                        $item['item_name'],
                        $item['unit'],
                        $qty,
                        $cost,
                        ($qty * $cost),
                        $item['approved_date']
                    ]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log("RSMI Snapshot Error: " . $e->getMessage());
            return false;
        }
    }

    public function getRSMISnapshotData($month) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM rsmi_snapshot WHERE snapshot_month = ? ORDER BY approved_date DESC, requisition_no DESC");
            $stmt->execute([$month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get RSMI Snapshot Data Error: " . $e->getMessage());
            return [];
        }
    }

    public function rsmiSnapshotExists($month) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM rsmi_snapshot WHERE snapshot_month = ?");
            $stmt->execute([$month]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
