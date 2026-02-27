<?php
require_once 'db/database.php';
$db = new Database();
$pdo = $db->getConnection();

function syncSupplyTotals($pdo) {
    echo "--- Checking Supply Table RPCI Columns ---\n";
    $stmt = $pdo->query("SELECT supply_id, item, requisition, issuance, quantity FROM supply WHERE requisition > 0 OR issuance > 0 LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No items with requisition or issuance values found.\n";
    } else {
        foreach ($rows as $row) {
            echo "ID: {$row['supply_id']}, Item: {$row['item']}, Acq (req): {$row['requisition']}, Iss: {$row['issuance']}, Qty: {$row['quantity']}\n";
        }
    }

    echo "\n--- Syncing Monthly Totals from Transactions ---\n";
    $currentMonth = date('Y-m');
    
    // CRITICAL: Reset all totals to 0 first to ensure accuracy
    echo "Resetting all monthly totals to 0...\n";
    $pdo->exec("UPDATE supply SET requisition = 0, issuance = 0");
    
    // Calculate Acquisition totals (from supply_history where type is 'Receipt' and created in this month)
    $sqlAcq = "SELECT supply_id, SUM(add_stock) as total_acq
               FROM supply_history
               WHERE type = 'Receipt'
               AND created_at LIKE '$currentMonth%'
               GROUP BY supply_id";
    $acqData = $pdo->query($sqlAcq)->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Issuance totals (from request_item where status is 'Issued' and approved in this month)
    $sqlIss = "SELECT ri.supply_id, SUM(ri.issued_quantity) as total_iss
               FROM request_item ri
               JOIN requisition r ON ri.requisition_id = r.requisition_id
               WHERE r.approved_date LIKE '$currentMonth%'
               AND r.status = 'Approved'
               GROUP BY ri.supply_id";
    $issData = $pdo->query($sqlIss)->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($acqData) . " items with current month Acquisitions.\n";
    echo "Found " . count($issData) . " items with current month Issuances.\n";

    // Perform Sync
    $syncCount = 0;
    foreach ($acqData as $acq) {
        $stmt = $pdo->prepare("UPDATE supply SET requisition = ? WHERE supply_id = ?");
        $stmt->execute([$acq['total_acq'], $acq['supply_id']]);
        $syncCount++;
    }
    foreach ($issData as $iss) {
        $stmt = $pdo->prepare("UPDATE supply SET issuance = ? WHERE supply_id = ?");
        $stmt->execute([$iss['total_iss'], $iss['supply_id']]);
        $syncCount++;
    }
    echo "Synced $syncCount records.\n";
}

try {
    syncSupplyTotals($pdo);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
