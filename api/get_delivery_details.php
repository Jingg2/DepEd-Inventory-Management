<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/security.php';
initSecureSession();

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db/database.php';
$db = new Database();
$conn = $db->getConnection();

// Search mode: Find delivery by item name, school, and cost
$itemName = $_GET['item'] ?? null;
$schoolName = $_GET['school'] ?? null;
$unitCost = $_GET['unit_cost'] ?? null;

if (!$itemName || !$schoolName) {
    echo json_encode(['success' => false, 'message' => 'Item name and school are required for search']);
    exit;
}

try {
    // 1. Find the most recent delivery item matching these criteria
    $sql = "SELECT d.*, s.school_name, s.school_id as official_school_id, di.delivery_item_id
            FROM deliveries d 
            JOIN delivery_items di ON d.delivery_id = di.delivery_id
            LEFT JOIN schools s ON d.school_id = s.id 
            WHERE di.item_name = ? AND d.school = ?
            ORDER BY d.delivery_date DESC, d.created_at DESC 
            LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$itemName, $schoolName]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$delivery) {
        // Broaden search if school name mismatch (sometimes school names vary slightly)
        $sql = "SELECT d.*, s.school_name, s.official_school_id, di.delivery_item_id
                FROM deliveries d 
                JOIN delivery_items di ON d.delivery_id = di.delivery_id
                LEFT JOIN schools s ON d.school_id = s.id 
                WHERE di.item_name LIKE ? AND d.school LIKE ?
                ORDER BY d.delivery_date DESC 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute(["%$itemName%", "%$schoolName%"]);
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$delivery) {
        echo json_encode(['success' => false, 'message' => 'No matching delivery found']);
        exit;
    }

    $deliveryId = $delivery['delivery_id'];

    // 2. Fetch all items in this delivery (from delivery_items table NOT supply)
    // The user asked "what the hell the purpose of delivery_items", so let's use it!
    $sqlItems = "SELECT di.delivery_item_id as supply_id, 
                        di.item_name as item, 
                        di.quantity, 
                        COALESCE(i.unit, di.unit) as unit, 
                        di.unit_cost, 
                        di.total_cost, 
                        COALESCE(i.property_classification, di.property_classification) as property_classification 
                 FROM delivery_items di
                 LEFT JOIN items i ON di.item_name = i.item_name
                 WHERE di.delivery_id = ? 
                 ORDER BY di.item_name ASC";
    $stmtItems = $conn->prepare($sqlItems);
    $stmtItems->execute([$deliveryId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'delivery' => $delivery,
        'items' => $items
    ]);

} catch (Exception $e) {
    error_log("API Error (get_delivery_details): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
