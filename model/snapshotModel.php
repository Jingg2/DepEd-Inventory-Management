<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\model\snapshotModel.php

class SnapshotModel {
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Create a monthly snapshot for a specific month
     * @param string $month Format: YYYY-MM
     * @return bool Success status
     */
    public function createMonthlySnapshot($month = null) {
        try {
            if ($month === null) {
                $month = date('Y-m');
            }
            
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                error_log("Invalid month format: $month");
                return false;
            }

            $this->conn->beginTransaction();

            // 1. Get transaction-calculated supply data for the target month
            require_once __DIR__ . '/supplyModel.php';
            $supplyModel = new SupplyModel();
            $supplies = $supplyModel->getMonthlyReportData($month);

            if (empty($supplies)) {
                $this->conn->rollBack();
                error_log("Snapshot creation skipped: No supply data found for $month");
                return false;
            }

            // Delete existing snapshot for this month if exists
            $deleteSql = "DELETE FROM monthly_inventory_snapshot WHERE snapshot_month = ?";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->execute([$month]);

            // Insert new snapshot
            $insertSql = "INSERT INTO monthly_inventory_snapshot
                         (snapshot_date, snapshot_month, supply_id, stock_no, item, category,
                          unit, description, quantity, previous_month,
                          requisition, issuance,
                          unit_cost, total_cost, status, property_classification,
                          school, updated_by, created_at, updated_at)
                         VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->conn->prepare($insertSql);

            foreach ($supplies as $supply) {
                $qty = (float)($supply['quantity'] ?? 0);
                $prev = (float)($supply['previous_month'] ?? 0);
                $acq = (float)($supply['requisition'] ?? 0);
                $iss = (float)($supply['issuance'] ?? 0);
                $cost = (float)($supply['unit_cost'] ?? 0);
                $totalCost = $qty * $cost;
                $now = date('Y-m-d H:i:s');
                
                $insertStmt->execute([
                    $month,
                    $supply['supply_id'],
                    $supply['stock_no'],
                    $supply['item'],
                    $supply['category'],
                    $supply['unit'],
                    $supply['description'] ?? '',
                    $qty,
                    $prev,
                    $acq,
                    $iss,
                    $cost,
                    $totalCost,
                    $supply['status'] ?? 'Active',
                    $supply['property_classification'] ?? 'General Service',
                    $supply['school'] ?? null,
                    $supply['updated_by'] ?? null,
                    $now,
                    $now
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
     * Create RSMI snapshot for the month
     */
    public function createRSMISnapshot($month) {
        try {
            // Delete existing
            $deleteSql = "DELETE FROM rsmi_snapshot WHERE snapshot_month = ?";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->execute([$month]);

            // Fetch all approved items for that month
            $sql = "SELECT r.requisition_no, CONCAT(e.first_name, ' ', e.last_name) as employee_name, 
                           s.item as item_name, s.stock_no, s.unit, ri.issued_quantity, s.unit_cost, 
                           r.approved_date
                    FROM request_item ri
                    JOIN requisition r ON ri.requisition_id = r.requisition_id
                    JOIN supply s ON ri.supply_id = s.supply_id
                    JOIN employee e ON r.employee_id = e.employee_id
                    WHERE r.status = 'Approved' AND ri.issued_quantity > 0 
                    AND r.approved_date LIKE ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month . '%']);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) return true;

            $insertSql = "INSERT INTO rsmi_snapshot 
                         (snapshot_month, requisition_no, employee_name, item_name, stock_no, unit, 
                          issued_quantity, unit_cost, total_cost, approved_date) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->conn->prepare($insertSql);

            foreach ($items as $item) {
                $total = (float)$item['issued_quantity'] * (float)$item['unit_cost'];
                $insertStmt->execute([
                    $month,
                    $item['requisition_no'],
                    $item['employee_name'],
                    $item['item_name'],
                    $item['stock_no'],
                    $item['unit'],
                    $item['issued_quantity'],
                    $item['unit_cost'],
                    $total,
                    $item['approved_date']
                ]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("RSMI Snapshot Error: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableSnapshots() {
        try {
            $sql = "SELECT snapshot_month, MIN(created_at) as created_at, COUNT(*) as item_count 
                    FROM monthly_inventory_snapshot 
                    GROUP BY snapshot_month 
                    ORDER BY snapshot_month DESC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get available snapshots error: " . $e->getMessage());
            return [];
        }
    }

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

    public function getRSMISnapshotData($month) {
        try {
            $sql = "SELECT * FROM rsmi_snapshot WHERE snapshot_month = ? ORDER BY approved_date, requisition_no";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get RSMI snapshot error: " . $e->getMessage());
            return [];
        }
    }

    public function snapshotExists($month) {
        try {
            $sql = "SELECT COUNT(*) FROM monthly_inventory_snapshot WHERE snapshot_month = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function rsmiSnapshotExists($month) {
        try {
            $sql = "SELECT COUNT(*) FROM rsmi_snapshot WHERE snapshot_month = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$month]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
