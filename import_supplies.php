<?php
require_once __DIR__ . '/db/database.php';

$jsonFile = 'final_excel_data.json';
if (!file_exists($jsonFile)) {
    die("JSON file not found: $jsonFile\n");
}

$content = file_get_contents($jsonFile);
$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    // Try removing BOM if present
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Failed to decode JSON data even after BOM removal: " . json_last_error_msg() . "\n");
    }
}

$db = new Database();
$conn = $db->getConnection();

// Find a valid employee_id for updated_by
$stmt = $conn->query("SELECT employee_id FROM employee ORDER BY employee_id ASC LIMIT 1");
$employeeId = $stmt->fetchColumn();
if (!$employeeId) {
    $employeeId = 1; // Fallback if table is somehow empty
}

echo "Found Employee ID: $employeeId\n";

$sql = "INSERT INTO supply (stock_no, item, category, unit, quantity, unit_cost, total_cost, status, property_classification, updated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

$insertedCount = 0;
$skippedCount = 0;

foreach ($data as $row) {
    $qty = (float)str_replace(',', '', $row['Quantity']);
    if ($qty <= 0) {
        $skippedCount++;
        continue;
    }

    $stockNo = $row['StockNo'];
    $item = $row['ItemDescription'];
    $category = $row['Category'];
    $unit = $row['Unit'];
    $unitCost = (float)str_replace(',', '', $row['UnitCost']);
    $totalCost = $qty * $unitCost;
    $status = 'Available';
    $propertyClassification = ''; // Could be inferred from category if needed

    try {
        $stmt->execute([
            $stockNo,
            $item,
            $category,
            $unit,
            $qty,
            $unitCost,
            $totalCost,
            $status,
            $propertyClassification,
            $employeeId
        ]);
        $insertedCount++;
    } catch (PDOException $e) {
        echo "Error inserting item {$stockNo}: " . $e->getMessage() . "\n";
    }
}

echo "\nImport finished.\n";
echo "Inserted: $insertedCount\n";
echo "skipped (zero qty): $skippedCount\n";
?>
