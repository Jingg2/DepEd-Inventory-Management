<?php
ob_start();
require_once __DIR__ . '/../model/supplyModel.php';
require_once __DIR__ . '/../includes/security.php';

// Ensure session for auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized Access");
}

$schoolName = $_GET['school'] ?? null;

if (!$schoolName) {
    die("School not specified");
}

$model = new SupplyModel();
$items = $model->getSchoolInventory($schoolName);
$schoolInfo = $model->getSchoolByName($schoolName);

// Log export
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logModel->log("EXPORT_SCHOOL_INV", "Exported Inventory for " . $schoolName);

$filename = "Inventory_" . preg_replace('/[^a-zA-Z0-9]/', '_', $schoolName) . "_" . date('Ymd_His') . ".xls";

ob_clean();
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=' . $filename);
header('Pragma: no-cache');
header('Expires: 0');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 11pt; }
        .title { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 5px; }
        .subtitle { font-size: 12pt; text-align: center; margin-bottom: 20px; }
        .header-section { margin-bottom: 20px; }
        .info-row { margin-bottom: 5px; font-weight: bold; }
        .grid-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .grid-header { border: 1px solid #000; background-color: #f0f0f0; font-weight: bold; text-align: center; padding: 8px; }
        .grid-cell { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="title">SCHOOL PROPERTY INVENTORY</div>
    <div class="subtitle"><?php echo htmlspecialchars($schoolName); ?></div>

    <div class="header-section">
        <?php if ($schoolInfo): ?>
            <div class="info-row">Address: <?php echo htmlspecialchars($schoolInfo['address']); ?></div>
            <div class="info-row">Contact: <?php echo htmlspecialchars($schoolInfo['contact_no']); ?></div>
        <?php endif; ?>
        <div class="info-row">Generated Date: <?php echo date('F d, Y'); ?></div>
    </div>

    <table class="grid-table">
        <tr>
            <th class="grid-header">Stock No.</th>
            <th class="grid-header">Item Name</th>
            <th class="grid-header">Description</th>
            <th class="grid-header">Unit</th>
            <th class="grid-header">Quantity</th>
            <th class="grid-header">Unit Cost</th>
            <th class="grid-header">Total Cost</th>
            <th class="grid-header">Condition</th>
            <th class="grid-header">Date Acquired</th>
        </tr>
        <?php 
        $grandTotal = 0;
        foreach ($items as $item): 
            $qty = (int)$item['quantity'];
            $totalCost = $item['unit_cost'] * $qty;
            $grandTotal += $totalCost;
            $condition = $item['item_condition'] ?? 'Functional';
            
            $conditionStyle = "";
            if (stripos($condition, 'repair') !== false) $conditionStyle = "color: orange;";
            if (stripos($condition, 'condemned') !== false) $conditionStyle = "color: red;";
            if (stripos($condition, 'lost') !== false) $conditionStyle = "color: red;";
        ?>
            <tr>
                <td class="grid-cell text-center"><?php echo htmlspecialchars($item['stock_no']); ?></td>
                <td class="grid-cell"><?php echo htmlspecialchars($item['item']); ?></td>
                <td class="grid-cell"><?php echo htmlspecialchars($item['description']); ?></td>
                <td class="grid-cell text-center"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="grid-cell text-center"><?php echo $qty; ?></td>
                <td class="grid-cell text-right"><?php echo number_format($item['unit_cost'], 2); ?></td>
                <td class="grid-cell text-right"><?php echo number_format($totalCost, 2); ?></td>
                <td class="grid-cell text-center" style="<?php echo $conditionStyle; ?>"><?php echo htmlspecialchars($condition); ?></td>
                <td class="grid-cell text-center"><?php echo date('M d, Y', strtotime($item['delivery_date'] ?? $item['updated_at'])); ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="6" class="grid-cell text-right" style="font-weight: bold;">GRAND TOTAL</td>
            <td class="grid-cell text-right" style="font-weight: bold;"><?php echo number_format($grandTotal, 2); ?></td>
            <td colspan="2" class="grid-cell"></td>
        </tr>
    </table>

    <br><br>
    <div style="width: 100%;">
        <div style="width: 45%; display: inline-block;">
            <b>Prepared by:</b><br><br><br>
            __________________________<br>
            Property Custodian
        </div>
        <div style="width: 45%; display: inline-block; text-align: right;">
            <b>Noted by:</b><br><br><br>
            __________________________<br>
            School Head
        </div>
    </div>
</body>
</html>
