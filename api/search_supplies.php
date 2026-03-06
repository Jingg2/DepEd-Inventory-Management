<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\search_supplies.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/database.php';

try {
    $query = $_GET['query'] ?? '';
    $db = new Database();
    $conn = $db->getConnection();
    
    if (empty($query)) {
        // Return top 50 most recently updated or added items by default
        // Filter: Only Semi-expendable and PPE
        $sql = "SELECT supply_id, item, stock_no, unit, quantity, returned_quantity, property_classification 
                FROM supply 
                WHERE property_classification NOT LIKE '%Consumable%'
                ORDER BY item ASC 
                LIMIT 50";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    } else {
        $searchTerm = "%$query%";
        $sql = "SELECT supply_id, item, stock_no, unit, quantity, returned_quantity, property_classification 
                FROM supply 
                WHERE (item LIKE ? OR stock_no LIKE ?)
                AND property_classification NOT LIKE '%Consumable%'
                ORDER BY item ASC 
                LIMIT 20";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
    }
    
    $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'supplies' => $supplies]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
