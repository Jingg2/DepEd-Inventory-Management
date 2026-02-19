<?php
require_once __DIR__ . '/db/database.php';
$db = new Database();
$conn = $db->getConnection();

echo "Total items in 'items' table: ";
$stmt = $conn->query("SELECT COUNT(*) FROM items");
echo $stmt->fetchColumn() . "\n";

echo "Distinct property_classification in 'items':\n";
$stmt = $conn->query("SELECT property_classification, COUNT(*) as count FROM items GROUP BY property_classification");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['property_classification'] . ": " . $row['count'] . "\n";
}

echo "\nTotal items in 'supply' table: ";
$stmt = $conn->query("SELECT COUNT(*) FROM supply");
echo $stmt->fetchColumn() . "\n";

echo "\nJoin test (s.item = i.item_name):\n";
$sql = "SELECT i.property_classification, COUNT(*) as count 
        FROM supply s
        JOIN items i ON s.item = i.item_name
        GROUP BY i.property_classification";
$stmt = $conn->query($sql);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['property_classification'] . ": " . $row['count'] . "\n";
}

echo "\nItems that would be caught by getPPESemiExpendableItems():\n";
$sql = "SELECT s.supply_id, s.item, i.property_classification
        FROM supply s
        JOIN items i ON s.item = i.item_name
        WHERE (i.property_classification LIKE 'Semi-Expendable%' AND i.property_classification NOT LIKE '%Low Value%')
           OR i.property_classification LIKE 'PPE%'
           OR i.property_classification LIKE 'Property%'";
$stmt = $conn->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($results) . "\n";
foreach(array_slice($results, 0, 10) as $res) {
    echo "- " . $res['item'] . " (" . $res['property_classification'] . ")\n";
}
?>
