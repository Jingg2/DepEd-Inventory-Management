<?php
// filepath: c:\OJT DEVELOPMENT\Inventory_System\model\supplyModel.php
class SupplyModel {
    private $conn;
    private $db; // Keep reference to Database object to prevent premature closure
    public $lastError = ''; // Store last error message

    public function __construct() {
        require_once __DIR__ . '/../db/database.php';
        $this->db = new Database(); // Store reference to prevent destruction
        $this->conn = $this->db->getConnection();
        $this->ensureHistoryTableExists();
        $this->ensureSchoolTablesExist();
    }

    private function ensureHistoryTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS supply_history (
            history_id INT AUTO_INCREMENT PRIMARY KEY,
            supply_id INT NOT NULL,
            type ENUM('Receipt', 'Adjustment', 'Correction', 'Issuance') DEFAULT 'Receipt',
            add_stock INT DEFAULT 0,
            quantity_change INT NOT NULL,
            previous_quantity INT NOT NULL,
            new_quantity INT NOT NULL,
            remarks TEXT,
            updated_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        try {
            $this->conn->exec($sql);
            
            // Auto-migration: Ensure add_stock column exists for existing tables
            // This is necessary because 'CREATE TABLE IF NOT EXISTS' won't add missing columns
            try {
                $check = $this->conn->query("SHOW COLUMNS FROM supply_history LIKE 'add_stock'");
                if ($check->rowCount() == 0) {
                    $this->conn->exec("ALTER TABLE supply_history ADD COLUMN add_stock INT DEFAULT 0 AFTER type");
                }
            } catch (Exception $e) {
                // Ignore if check fails, column might exist or permission denied
            }
            
            // Auto-migration: Ensure equipment_status column exists in supply table
            try {
                $check = $this->conn->query("SHOW COLUMNS FROM supply LIKE 'equipment_status'");
                if ($check->rowCount() == 0) {
                    $this->conn->exec("ALTER TABLE supply ADD COLUMN equipment_status VARCHAR(50) DEFAULT 'Functional'");
                }
            } catch (Exception $e) {
                error_log("Failed to add equipment_status column: " . $e->getMessage());
            }
            
            // Auto-migration: Ensure school column exists in supply table
            try {
                $check = $this->conn->query("SHOW COLUMNS FROM supply LIKE 'school'");
                if ($check->rowCount() == 0) {
                    $this->conn->exec("ALTER TABLE supply ADD COLUMN school VARCHAR(255) DEFAULT NULL");
                }
            } catch (Exception $e) {
                error_log("Failed to add school column: " . $e->getMessage());
            }

            // Auto-migration: Ensure deliveries table exists
            $sqlDeliveries = "CREATE TABLE IF NOT EXISTS deliveries (
                delivery_id INT AUTO_INCREMENT PRIMARY KEY,
                receipt_no VARCHAR(100),
                school VARCHAR(255),
                address TEXT,
                delivery_date DATE,
                delivered_by VARCHAR(255),
                received_by_officer VARCHAR(255),
                received_by_librarian VARCHAR(255),
                supplier VARCHAR(255),
                total_amount DECIMAL(15, 2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            try {
                $this->conn->exec($sqlDeliveries);
            } catch (Exception $e) {
                error_log("Failed to create deliveries table: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log("Failed to ensure history table: " . $e->getMessage());
        }
    }

    private function ensureSchoolTablesExist() {
        try {
            // Create schools table (Internal ID and External School ID)
            $sqlSchools = "CREATE TABLE IF NOT EXISTS schools (
                id INT AUTO_INCREMENT PRIMARY KEY,
                school_id VARCHAR(50) UNIQUE NOT NULL,
                school_name VARCHAR(255) NOT NULL,
                address TEXT,
                contact_no VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB";
            $this->conn->exec($sqlSchools);

            // Create items table
            $sqlItems = "CREATE TABLE IF NOT EXISTS items (
                item_id INT AUTO_INCREMENT PRIMARY KEY,
                item_name VARCHAR(255) UNIQUE NOT NULL,
                category VARCHAR(50),
                unit VARCHAR(50),
                description TEXT,
                property_classification VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB";
            $this->conn->exec($sqlItems);

            // Create school_deliveries table
            $sqlSchoolDeliveries = "CREATE TABLE IF NOT EXISTS school_deliveries (
                delivery_item_id INT AUTO_INCREMENT PRIMARY KEY,
                school_id INT NOT NULL,
                supply_id INT NOT NULL,
                quantity INT NOT NULL,
                delivery_date DATE,
                received_by VARCHAR(255),
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (school_id),
                INDEX (supply_id),
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
                FOREIGN KEY (supply_id) REFERENCES supply(supply_id) ON DELETE CASCADE
            ) ENGINE=InnoDB";
            $this->conn->exec($sqlSchoolDeliveries);

            // Create delivery_items table (Itemized receipt records)
            $sqlDeliveryItems = "CREATE TABLE IF NOT EXISTS delivery_items (
                delivery_item_id INT AUTO_INCREMENT PRIMARY KEY,
                delivery_id INT NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                category VARCHAR(50),
                unit VARCHAR(50),
                quantity INT NOT NULL,
                unit_cost DECIMAL(15,2),
                total_cost DECIMAL(15,2),
                description TEXT,
                property_classification VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (delivery_id)
            ) ENGINE=InnoDB";
            $this->conn->exec($sqlDeliveryItems);

            // Migrate existing school names from supply table
            $stmtCheckSchools = $this->conn->query("SELECT COUNT(*) FROM schools");
            if ($stmtCheckSchools->fetchColumn() == 0) {
                // Try to get school and potential ID from supply table
                $stmt = $this->conn->query("SELECT DISTINCT school FROM supply WHERE school IS NOT NULL AND school != ''");
                $schools = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($schools)) {
                    $insertStmt = $this->conn->prepare("INSERT IGNORE INTO schools (school_id, school_name) VALUES (?, ?)");
                    foreach ($schools as $school) {
                        // For auto-migration, we'll use the name as ID if no ID exists
                        $insertStmt->execute([$school, $school]);
                    }
                }
            }

            // Migrate existing item definitions from supply table
            $stmtCheckItems = $this->conn->query("SELECT COUNT(*) FROM items");
            if ($stmtCheckItems->fetchColumn() == 0) {
                $stmt = $this->conn->query("SELECT DISTINCT item, category, unit, description, property_classification FROM supply");
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($items)) {
                    $insertStmt = $this->conn->prepare("INSERT IGNORE INTO items (item_name, category, unit, description, property_classification) VALUES (?, ?, ?, ?, ?)");
                    foreach ($items as $item) {
                        $insertStmt->execute([
                            $item['item'],
                            $item['category'],
                            $item['unit'],
                            $item['description'],
                            $item['property_classification']
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to ensure school and item tables: " . $e->getMessage());
        }
    }

    private function recordSupplyHistory($supplyId, $addStock, $change, $prev, $new, $type = 'Receipt', $remarks = '', $date = null, $adminId = null) {
        $sql = "INSERT INTO supply_history (supply_id, add_stock, quantity_change, previous_quantity, new_quantity, type, remarks, created_at, updated_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $insertDate = $date ?? date('Y-m-d H:i:s');
            $this->conn->prepare($sql)->execute([$supplyId, $addStock, $change, $prev, $new, $type, $remarks, $insertDate, $adminId]);
        } catch (Exception $e) {
            error_log("Failed to record supply history: " . $e->getMessage());
        }
    }

    public function insertSupply($data) {
        // Validate required fields
        if (empty($data['item']) || trim($data['item']) === '') {
            error_log("Insert supply validation failed: Missing item");
            return false;
        }
        if (empty($data['category']) || trim($data['category']) === '') {
            error_log("Insert supply validation failed: Missing category");
            return false;
        }
        if (empty($data['unit']) || trim($data['unit']) === '') {
            error_log("Insert supply validation failed: Missing unit");
            return false;
        }
        
        // Generate stock_no if not provided
        $stock_no = !empty($data['stock_no']) && trim($data['stock_no']) !== ''
            ? trim($data['stock_no']) 
            : "AUTO-" . time();
        
        // Handle image upload
        $imageData = null;
        if (!empty($data['image']) && is_array($data['image']) && $data['image']['error'] === UPLOAD_ERR_OK) {
            $raw = @file_get_contents($data['image']['tmp_name']);
            if ($raw !== false && strlen($raw) > 0) {
                $maxBytes = 1024 * 1024; // 1MB
                if (strlen($raw) <= $maxBytes) {
                    $imageData = $raw;
                }
            }
        }

        try {
            $unit_cost = isset($data['unit_cost']) ? (float)$data['unit_cost'] : 0.00;
            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
            $total_cost = $unit_cost * $quantity;
            $updated_at = date('Y-m-d H:i:s');
            
            if ($imageData !== null && strlen($imageData) > 0) {
                $sql = "INSERT INTO supply (stock_no, category, unit, item, description, quantity, unit_cost, total_cost, status, updated_by, updated_at, image, property_classification, low_stock_threshold, critical_stock_threshold, school, previous_month, issuance)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $params = [
                    $stock_no, $data['category'], $data['unit'], $data['item'],
                    !empty($data['description']) ? $data['description'] : '',
                    $quantity, $unit_cost, $total_cost, $data['status'], $data['admin_id'] ?? 1, $updated_at, $imageData,
                    $data['property_classification'] ?? null,
                    $data['low_stock_threshold'] ?? 10,
                    $data['critical_stock_threshold'] ?? 5,
                    $data['school'] ?? null,
                    $data['previous_month'] ?? 0,
                    $data['issuance'] ?? 0
                ];
                $result = $stmt->execute($params);
            } else {
                $sql = "INSERT INTO supply (stock_no, category, unit, item, description, quantity, unit_cost, total_cost, status, updated_by, updated_at, property_classification, low_stock_threshold, critical_stock_threshold, school, previous_month, issuance)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $params = [
                    $stock_no, $data['category'], $data['unit'], $data['item'],
                    !empty($data['description']) ? $data['description'] : '',
                    $quantity, $unit_cost, $total_cost, $data['status'], $data['admin_id'] ?? 1, $updated_at,
                    $data['property_classification'] ?? null,
                    $data['low_stock_threshold'] ?? 10,
                    $data['critical_stock_threshold'] ?? 5,
                    $data['school'] ?? null,
                    $data['previous_month'] ?? 0,
                    $data['issuance'] ?? 0
                ];
                $result = $stmt->execute($params);
            }
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown database error';
                return false;
            }
            
            // Record initial receipt
            $newId = $this->conn->lastInsertId();
            $adminId = $data['admin_id'] ?? 1;
            // Anchor initial stock to the item creation time for perfect sorting
            $initialDate = $updated_at; 
            $this->recordSupplyHistory($newId, $quantity, $quantity, 0, $quantity, 'Receipt', 'Initial stock entry', $initialDate, $adminId);
            
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getAllSupplies() {
        $sql = "SELECT * FROM supply ORDER BY supply_id DESC";
        try {
            $stmt = $this->conn->query($sql);
            $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert image binary data to base64 for display
            foreach ($supplies as &$supply) {
                // Use quantity; fallback to previous_month for older rows if needed
                if (!isset($supply['quantity']) && isset($supply['previous_month'])) {
                    $supply['quantity'] = $supply['previous_month'];
                }
                
                try {
                    if (!empty($supply['image']) && is_string($supply['image'])) {
                        // Convert binary data directly to base64
                        $supply['image_base64'] = 'data:image/jpeg;base64,' . base64_encode($supply['image']);
                    } else {
                        $supply['image_base64'] = 'img/Bogo_City_logo.png'; // Default image
                    }
                } catch (Exception $e) {
                    // If image conversion fails, use default
                    $supply['image_base64'] = 'img/Bogo_City_logo.png';
                }
            }
            
            return $supplies;
        } catch (PDOException $e) {
            error_log("Get all supplies error: " . $e->getMessage());
            return [];
        }
    }

    public function getAllCategories() {
        $sql = "SELECT DISTINCT category FROM supply WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
        try {
            return $this->conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Get categories error: " . $e->getMessage());
            return [];
        }
    }

    public function getLastInsertedSupply() {
        $sql = "SELECT * FROM supply ORDER BY supply_id DESC LIMIT 1";
        try {
            $stmt = $this->conn->query($sql);
            $supply = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$supply) {
                return null;
            }
            
            // Use quantity; fallback to previous_month for older rows if needed
            if (!isset($supply['quantity']) && isset($supply['previous_month'])) {
                $supply['quantity'] = $supply['previous_month'];
            }
            
            // Convert image binary data to base64 for display
            try {
                if (!empty($supply['image']) && is_string($supply['image'])) {
                    // Convert binary data directly to base64
                    $supply['image_base64'] = 'data:image/jpeg;base64,' . base64_encode($supply['image']);
                } else {
                    $supply['image_base64'] = 'img/Bogo_City_logo.png'; // Default image
                }
            } catch (Exception $e) {
                // If image conversion fails, use default
                $supply['image_base64'] = 'img/Bogo_City_logo.png';
            }
            
            return $supply;
        } catch (PDOException $e) {
            error_log("Get last supply error: " . $e->getMessage());
            return null;
        }
    }

    public function updateSupply($id, $data) {
        // Validate required fields
        if (empty($data['item']) || trim($data['item']) === '') {
            error_log("Update supply validation failed: Missing item");
            $this->lastError = 'Item Name is required';
            return false;
        }
        if (empty($data['category']) || trim($data['category']) === '') {
            error_log("Update supply validation failed: Missing category");
            $this->lastError = 'Category is required';
            return false;
        }
        if (empty($data['unit']) || trim($data['unit']) === '') {
            error_log("Update supply validation failed: Missing unit");
            $this->lastError = 'Unit is required';
            return false;
        }

        // Handle image upload if provided
        $imageData = null;
        if (!empty($data['image']) && is_array($data['image']) && $data['image']['error'] === UPLOAD_ERR_OK) {
            $raw = @file_get_contents($data['image']['tmp_name']);
            if ($raw !== false && strlen($raw) > 0) {
                $maxBytes = 1024 * 1024; // 1MB
                if (strlen($raw) <= $maxBytes) {
                    $imageData = $raw;
                } elseif (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
                    $img = @imagecreatefromstring($raw);
                    if ($img) {
                        $w = imagesx($img);
                        $h = imagesy($img);
                        $nw = $w;
                        $nh = $h;
                        if ($w > 800 || $h > 600) {
                            $r = min(800 / max(1, $w), 600 / max(1, $h));
                            $nw = (int) round($w * $r);
                            $nh = (int) round($h * $r);
                        }
                        $thumb = @imagecreatetruecolor($nw, $nh);
                        if ($thumb && @imagecopyresampled($thumb, $img, 0, 0, 0, 0, $nw, $nh, $w, $h)) {
                            ob_start();
                            imagejpeg($thumb, null, 82);
                            $imageData = ob_get_clean();
                            imagedestroy($thumb);
                        }
                        imagedestroy($img);
                    }
                }
                if ($imageData === null || strlen($imageData) > $maxBytes) {
                    $imageData = strlen($raw) <= $maxBytes ? $raw : null;
                }
            }
        }

        try {
            // Get previous quantity for history tracking
            $oldSupplyStmt = $this->conn->prepare("SELECT quantity FROM supply WHERE supply_id = ?");
            $oldSupplyStmt->execute([$id]);
            $oldSupply = $oldSupplyStmt->fetch(PDO::FETCH_ASSOC);
            $prevQty = $oldSupply ? (int)$oldSupply['quantity'] : 0;
            $adminId = $data['admin_id'] ?? 1;

            // Handle 'add_stock' logic
            $addStock = isset($data['add_stock']) ? (int)$data['add_stock'] : 0;
            if ($addStock > 0) {
                $quantity = $prevQty + $addStock;
            } else {
                $quantity = isset($data['quantity']) ? (int)$data['quantity'] : $prevQty;
            }

            // Calculate total cost
            $unit_cost = isset($data['unit_cost']) ? (float)$data['unit_cost'] : 0.00;
            $total_cost = $unit_cost * $quantity;
            $updated_at = date('Y-m-d H:i:s');
            
            // Build SQL based on whether image is being updated
        if ($imageData !== null && strlen($imageData) > 0) {
            $sql = "UPDATE supply SET 
                    stock_no = ?, 
                    category = ?, 
                    unit = ?, 
                    item = ?, 
                    description = ?, 
                    quantity = ?, 
                    add_stock = ?,
                    previous_quantity = ?,
                    unit_cost = ?, 
                    total_cost = ?, 
                    status = ?, 
                    updated_by = ?, 
                    updated_at = ?, 
                    image = ?,
                    property_classification = ?,
                    low_stock_threshold = ?,
                    critical_stock_threshold = ?,
                    school = ?,
                    previous_month = ?,
                    issuance = ?
                    WHERE supply_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, !empty($data['stock_no']) ? $data['stock_no'] : null);
            $stmt->bindValue(2, $data['category']);
            $stmt->bindValue(3, $data['unit']);
            $stmt->bindValue(4, $data['item']);
            $stmt->bindValue(5, !empty($data['description']) ? $data['description'] : '');
            $stmt->bindValue(6, $quantity, PDO::PARAM_INT);
            $stmt->bindValue(7, $addStock, PDO::PARAM_INT);
            $stmt->bindValue(8, $prevQty, PDO::PARAM_INT);
            $stmt->bindValue(9, $unit_cost);
            $stmt->bindValue(10, $total_cost);
            $stmt->bindValue(11, $data['status']);
            $stmt->bindValue(12, $adminId, PDO::PARAM_INT);
            $stmt->bindValue(13, $updated_at);
            $stmt->bindValue(14, $imageData, PDO::PARAM_LOB);
            $stmt->bindValue(15, $data['property_classification'] ?? null);
            $stmt->bindValue(16, $data['low_stock_threshold'] ?? 10, PDO::PARAM_INT);
            $stmt->bindValue(17, $data['critical_stock_threshold'] ?? 5, PDO::PARAM_INT);
            $stmt->bindValue(18, $data['school'] ?? null);
            $stmt->bindValue(19, $data['previous_month'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(20, $data['issuance'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(21, (int)$id, PDO::PARAM_INT);
        } else {
            $sql = "UPDATE supply SET 
                    stock_no = ?, 
                    category = ?, 
                    unit = ?, 
                    item = ?, 
                    description = ?, 
                    quantity = ?, 
                    add_stock = ?,
                    previous_quantity = ?,
                    unit_cost = ?, 
                    total_cost = ?, 
                    status = ?, 
                    updated_by = ?, 
                    updated_at = ?,
                    property_classification = ?,
                    low_stock_threshold = ?,
                    critical_stock_threshold = ?,
                    school = ?,
                    previous_month = ?,
                    issuance = ?
                    WHERE supply_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, !empty($data['stock_no']) ? $data['stock_no'] : null);
            $stmt->bindValue(2, $data['category']);
            $stmt->bindValue(3, $data['unit']);
            $stmt->bindValue(4, $data['item']);
            $stmt->bindValue(5, !empty($data['description']) ? $data['description'] : '');
            $stmt->bindValue(6, $quantity, PDO::PARAM_INT);
            $stmt->bindValue(7, $addStock, PDO::PARAM_INT);
            $stmt->bindValue(8, $prevQty, PDO::PARAM_INT);
            $stmt->bindValue(9, $unit_cost);
            $stmt->bindValue(10, $total_cost);
            $stmt->bindValue(11, $data['status']);
            $stmt->bindValue(12, $adminId, PDO::PARAM_INT);
            $stmt->bindValue(13, $updated_at);
            $stmt->bindValue(14, $data['property_classification'] ?? null);
            $stmt->bindValue(15, $data['low_stock_threshold'] ?? 10, PDO::PARAM_INT);
            $stmt->bindValue(16, $data['critical_stock_threshold'] ?? 5, PDO::PARAM_INT);
            $stmt->bindValue(17, $data['school'] ?? null);
            $stmt->bindValue(18, $data['previous_month'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(19, $data['issuance'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(20, (int)$id, PDO::PARAM_INT);
        }
            
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->lastError = isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown database error';
                error_log("Update supply failed: " . $this->lastError);
                return false;
            }

            // Record history if stock changed
            if ($addStock > 0) {
                $this->recordSupplyHistory($id, $addStock, $addStock, $prevQty, $quantity, 'Receipt', 'Manual Restock', $updated_at, $adminId);
            } else if ($quantity != $prevQty) {
                $diff = $quantity - $prevQty;
                // Direct edits are "Corrections" not "Receipts" (Receipts come from Add Stock)
                $type = 'Correction';
                $remarks = $diff > 0 ? 'Stock Correction (Increase)' : 'Stock Correction (Decrease)';
                $this->recordSupplyHistory($id, 0, $diff, $prevQty, $quantity, $type, $remarks, $updated_at, $adminId);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Update supply PDO Exception: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function deleteSupply($id, $force = false) {
        try {
            // 1. Check if item has any approved/pending requests
            $sqlCheck = "SELECT COUNT(*) FROM request_item WHERE supply_id = ?";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->execute([$id]);
            $count = $stmtCheck->fetchColumn();
            
            if ($count > 0 && !$force) {
                $this->lastError = "Cannot delete this item because it is associated with $count existing requests/issuances. Please 'Discontinue' it via Edit instead if it is no longer needed to maintain historical records.";
                error_log("Delete supply blocked: ID $id is used in $count requests");
                return false;
            }

            $this->conn->beginTransaction();

            // 2. If force, delete related requests first
            if ($force && $count > 0) {
                $sqlReq = "DELETE FROM request_item WHERE supply_id = ?";
                $stmtReq = $this->conn->prepare($sqlReq);
                $stmtReq->execute([$id]);
                error_log("Forcefully deleted $count request items for supply ID $id");
            }

            // 3. Clear history if any
            $sqlHist = "DELETE FROM supply_history WHERE supply_id = ?";
            $stmtHist = $this->conn->prepare($sqlHist);
            $stmtHist->execute([$id]);
            
            // 4. Delete the supply
            $sql = "DELETE FROM supply WHERE supply_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if (!$result) {
                $this->conn->rollBack();
                $errorInfo = $stmt->errorInfo();
                $this->lastError = isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown database error';
                error_log("Delete supply failed: " . $this->lastError);
                return false;
            }
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Delete supply PDO Exception: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getSupplyById($id) {
        $sql = "SELECT supply_id as id, item, unit, stock_no, quantity, quantity as current_qty, unit_cost, 
                       updated_at as created_at, property_classification, description, school, image,
                       previous_month, add_stock, issuance
                FROM supply
                WHERE supply_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSupplyTransactionHistory($id, $fromDate = null, $toDate = null) {
        try {
            // 1. Get current supply details
            $supply = $this->getSupplyById($id);
            if (!$supply) return null;

            // 2. Get all Receipts/Adjustments
            $sql = "SELECT sh.created_at as date, sh.remarks as reference, 
                           sh.quantity_change as received, 0 as issued, sh.remarks,
                           a.username as encoder_name, sh.type,
                           sh.quantity_change, sh.new_quantity, sh.remarks as raw_remarks,
                           sh.history_id, sh.created_at as sort_date
                    FROM supply_history sh 
                    LEFT JOIN admin a ON sh.updated_by = a.admin_id 
                    WHERE sh.supply_id = ? 
                      AND (sh.quantity_change != 0 OR sh.new_quantity != 0 OR sh.remarks = 'Manual Restock')
                    ORDER BY sort_date ASC, sh.history_id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Self-Healing: If we find a gap correction with incorrect 2024 date, update it to the item's creation date
            foreach ($receipts as &$rec) {
                if ($rec['remarks'] === 'Initial Balance / Gap Correction' && strpos($rec['date'], '2024-01-01') === 0) {
                    $newDate = $supply['created_at'] ?? $supply['date'] ?? date('Y-m-d H:i:s');
                    $fixStmt = $this->conn->prepare("UPDATE supply_history SET created_at = ? WHERE history_id = ?");
                    $fixStmt->execute([$newDate, $rec['history_id']]);
                    $rec['date'] = $newDate;
                    $rec['sort_date'] = $newDate;
                }
            }
            unset($rec);

            // 3. Get all Issuances (from approved requisitions)
            $transSql = "SELECT COALESCE(r.approved_date, r.created_at) as date, r.requisition_no as reference, 
                               ri.issued_quantity as issued, ri.remarks,
                               e.first_name, e.last_name, d.department_name, 
                               COALESCE(r.approved_date, r.created_at) as sort_date,
                               a.username as encoder_name, r.requisition_id
                        FROM request_item ri
                        JOIN requisition r ON ri.requisition_id = r.requisition_id
                        JOIN employee e ON r.employee_id = e.employee_id
                        JOIN department d ON r.department_id = d.department_id
                        LEFT JOIN admin a ON r.approved_by = a.admin_id
                        WHERE ri.supply_id = ? AND r.status = 'Approved'
                        ORDER BY sort_date ASC, r.requisition_id ASC";
            $stmt = $this->conn->prepare($transSql);
            $stmt->execute([$id]);
            $issuances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Merge and prep for sorting
            foreach ($issuances as &$iss) {
                $iss['received'] = 0;
                $iss['type'] = 'Issuance';
            }
            unset($iss);
            
            // Receipts already have sort_date from SQL
            
            // --- Gap Detection & Self-Healing ---
            $totalReceipts = 0;
            foreach ($receipts as $r) $totalReceipts += (int)$r['received'];
            
            $totalIssued = 0;
            foreach ($issuances as $i) $totalIssued += (int)$i['issued'];
            
            $historyBalance = $totalReceipts - $totalIssued;
            $currentQty = (int)$supply['quantity'];
            
            if ($historyBalance != $currentQty) {
                $gap = $currentQty - $historyBalance;
                if ($gap != 0) {
                    $gapType = $gap > 0 ? 'Receipt' : 'Issuance';
                    $gapRemarks = 'Initial Balance / Gap Correction';
                    $addStock = $gap > 0 ? $gap : 0;
                    // Use the item's creation timestamp for the gap correction record
                    $baseDate = $supply['created_at'] ?? $supply['date'] ?? date('Y-m-d H:i:s');
                    
                    $this->recordSupplyHistory(
                        $id, $addStock, $gap, $historyBalance, $currentQty, $gapType, $gapRemarks, $baseDate
                    );
                    
                    $receipts[] = [
                        'date' => $baseDate,
                        'reference' => 'Stock Adjustment',
                        'received' => $gap > 0 ? $gap : 0,
                        'issued' => $gap < 0 ? abs($gap) : 0,
                        'remarks' => $gapRemarks,
                        'encoder_name' => 'System',
                        'type' => $gapType,
                        'balance' => 0,
                        'history_id' => -1,
                        'sort_date' => $baseDate
                    ];
                }
            }
            
            // 5. Final Chronological Sort
            $allMovements = array_merge($issuances, $receipts);
            usort($allMovements, function($a, $b) {
                $d1 = date('Y-m-d', strtotime($a['date']));
                $d2 = date('Y-m-d', strtotime($b['date']));
                if ($d1 !== $d2) return strcmp($d1, $d2);
                
                $t1 = strtotime($a['sort_date'] ?? $a['date']);
                $t2 = strtotime($b['sort_date'] ?? $b['date']);
                if ($t1 !== $t2) return $t1 - $t2;
                
                $typeA = (isset($a['received']) && $a['received'] > 0) ? 0 : 1;
                $typeB = (isset($b['received']) && $b['received'] > 0) ? 0 : 1;
                if ($typeA !== $typeB) return $typeA - $typeB;

                $idA = $a['history_id'] ?? $a['reference'] ?? '';
                $idB = $b['history_id'] ?? $b['reference'] ?? '';
                return strcmp($idA, $idB);
            });

            // 6. Calculate running balance and filter
            $currentBal = 0;
            $beginningBalance = 0;
            $filteredMovements = [];
            
            $fromDateTime = $fromDate ? strtotime($fromDate . ' 00:00:00') : null;
            $toDateTime = $toDate ? strtotime($toDate . ' 23:59:59') : null;

            foreach ($allMovements as $move) {
                $currentBal += (int)($move['received'] ?? 0);
                $currentBal -= (int)($move['issued'] ?? 0);
                $move['balance'] = $currentBal;
                
                $moveTime = strtotime($move['date']);
                
                if ($fromDateTime && $moveTime < $fromDateTime) {
                    $beginningBalance = $currentBal;
                    continue;
                }
                
                if ($toDateTime && $moveTime > $toDateTime) {
                    continue;
                }
                
                $filteredMovements[] = $move;
            }

            return [
                'supply' => $supply,
                'transactions' => $filteredMovements,
                'beginning_balance' => $beginningBalance
            ];
        } catch (Throwable $e) {
            error_log("Stock Card History Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all PPE and Semi-Expendable items with equipment status
     * @return array List of PPE/Semi-Expendable supplies
     */
    public function getPPESemiExpendableItems() {
        $sql = "SELECT s.*
                FROM supply s
                WHERE 
                    (TRIM(s.property_classification) LIKE 'Semi-Expendable%' AND TRIM(s.property_classification) NOT LIKE '%Low Value%')
                    OR TRIM(s.property_classification) LIKE '%PPE%'
                    OR TRIM(s.property_classification) LIKE 'Property, Plant and Equipment%'
                    OR TRIM(s.property_classification) LIKE 'Property%'
                ORDER BY s.property_classification, s.item";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get PPE/Semi-Expendable items error: " . $e->getMessage());
            return [];
        }
    }



    /**
     * Get Waste Materials (Unserviceable Items)
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getWasteItems($startDate = null, $endDate = null) {
        $sql = "SELECT w.*, s.stock_no, s.item as item_name, s.unit, s.description, s.unit_cost, 
                       s.property_classification,
                       e.first_name, e.last_name, d.department_name
                FROM waste_items w
                JOIN supply s ON w.supply_id = s.supply_id
                JOIN employee e ON w.employee_id = e.employee_id
                JOIN department d ON e.department_id = d.department_id
                WHERE 1=1";
        
        $params = [];
        if ($startDate) {
            $sql .= " AND w.date_returned >= ?";
            $params[] = $startDate . " 00:00:00";
        }
        if ($endDate) {
            $sql .= " AND w.date_returned <= ?";
            $params[] = $endDate . " 23:59:59";
        }
        
        $sql .= " ORDER BY w.date_returned DESC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get waste items error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Delivery Summary for Reports
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getDeliverySummary($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    di.item_name as item,
                    COALESCE(i.description, di.description) as description,
                    COALESCE(i.category, di.category) as category,
                    COALESCE(i.unit, di.unit) as unit,
                    di.quantity,
                    di.unit_cost,
                    di.total_cost,
                    COALESCE(i.property_classification, di.property_classification) as property_classification,
                    d.school as school_name,
                    d.address,
                    d.delivery_date as updated_at,
                    d.receipt_no as stock_no,
                    si.contact_no
                FROM delivery_items di
                JOIN deliveries d ON di.delivery_id = d.delivery_id
                LEFT JOIN items i ON di.item_name = i.item_name
                LEFT JOIN schools si ON d.school = si.school_name
                WHERE 1=1";
        
        $params = [];
        if ($startDate) {
            $sql .= " AND d.delivery_date >= ?"; 
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND d.delivery_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY d.delivery_date DESC, d.school ASC";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get delivery summary error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get School Inventory for Reports
     * @param string $schoolName
     * @return array
     */
    public function getSchoolInventory($schoolName) {
        // Reusing getItemsBySchool but ensuring we get school info too
        return $this->getItemsBySchool($schoolName);
    }

    /**
     * Get top issued supplies for turnover analysis
     * @param int $limit Number of records to return
     * @return array List of supplies with issued counts
     */
    public function getTopIssuedSupplies($limit = 10) {
        $sql = "SELECT s.item as name, SUM(ri.issued_quantity) as issued, s.quantity as stock
                FROM request_item ri 
                JOIN supply s ON ri.supply_id = s.supply_id 
                WHERE ri.status = 'Issued' 
                GROUP BY ri.supply_id 
                ORDER BY issued DESC 
                LIMIT :limit";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get Top Issued Supplies Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all unique schools from controlled assets
     * @return array List of unique school names with item counts
     */
    public function getSchools() {
        try {
            $stmt = $this->conn->query("SELECT * FROM schools ORDER BY school_name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to get schools: " . $e->getMessage());
            return [];
        }
    }

    public function getSchoolByName($name) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM schools WHERE TRIM(school_name) = TRIM(?)");
            $stmt->execute([$name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to get school by name: " . $e->getMessage());
            return null;
        }
    }

    public function updateSchool($id, $data) {
        try {
            $sql = "UPDATE schools SET 
                    school_id = :school_id,
                    school_name = :school_name,
                    address = :address,
                    contact_no = :contact_no
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':school_id' => $data['school_id'],
                ':school_name' => $data['school_name'],
                ':address' => $data['address'],
                ':contact_no' => $data['contact_no'],
                ':id' => $id
            ]);
        } catch (Exception $e) {
            error_log("Failed to update school: " . $e->getMessage());
            return false;
        }
    }

    public function getSchoolsList() {
        $sql = "SELECT 
                    d.school as school, 
                    COUNT(di.delivery_item_id) as item_count,
                    SUM(di.quantity) as total_quantity
                FROM deliveries d
                JOIN delivery_items di ON d.delivery_id = di.delivery_id
                JOIN items i ON di.item_name = i.item_name
                WHERE ((i.property_classification LIKE 'Semi-Expendable%' AND i.property_classification NOT LIKE '%Low Value%')
                       OR i.property_classification LIKE 'PPE%'
                       OR i.property_classification LIKE 'Property%')
                GROUP BY d.school
                ORDER BY d.school ASC";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get schools list from deliveries error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all items for a specific school
     * @param string $schoolName The school name to filter by
     * @return array List of items for the specified school
     */
    public function getItemsBySchool($schoolName) {
        $sql = "SELECT di.delivery_item_id as supply_id, 
                       'DE-REC' as stock_no, 
                       i.item_name as item, 
                       i.description, 
                       i.category, 
                       i.unit, 
                       di.quantity, 
                       di.unit_cost, 
                       di.total_cost, 
                       i.property_classification, 
                       'Delivered' as status, 
                       di.created_at as updated_at, 
                       d.school,
                       d.receipt_no,
                       d.delivery_date,
                       di.item_condition,
                       null as image
                FROM delivery_items di
                JOIN deliveries d ON di.delivery_id = d.delivery_id
                JOIN items i ON di.item_name = i.item_name
                WHERE d.school = ?
                  AND ((i.property_classification LIKE 'Semi-Expendable%' AND i.property_classification NOT LIKE '%Low Value%')
                       OR i.property_classification LIKE 'PPE%'
                       OR i.property_classification LIKE 'Property%')
                ORDER BY d.delivery_date DESC, d.receipt_no DESC, i.item_name ASC";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$schoolName]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get items by school from deliveries error: " . $e->getMessage());
            return [];
        }
    }

    public function updateDeliveryItemCondition($id, $condition) {
        try {
            $sql = "UPDATE delivery_items SET item_condition = ? WHERE delivery_item_id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$condition, $id]);
        } catch (PDOException $e) {
            error_log("Update delivery item condition error: " . $e->getMessage());
            return false;
        }
    }

    // Don't close connection here as it's managed by Database class
}