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

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$model = new SupplyModel();
$items = $model->getDeliverySummary($startDate, $endDate);

// Log export
require_once __DIR__ . '/../model/SystemLogModel.php';
$logModel = new SystemLogModel();
$rangeStr = ($startDate && $endDate) ? " ($startDate to $endDate)" : " (All Time)";
$logModel->log("EXPORT_DELIVERY", "Exported Controlled Assets Delivery Summary" . $rangeStr);

$filename = "Delivery_Summary_" . date('Ymd_His') . ".xls";

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
        body { font-family: 'Arial', sans-serif; font-size: 10pt; }
        .title { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .header-cell { background-color: #1e3a8a; color: white; border: 1px solid #000; font-weight: bold; text-align: center; padding: 10px; }
        .data-cell { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .text-center { text-align: center; }
        .date_range { text-align: center; margin-bottom: 10px; font-style: italic; }
    </style>
</head>
<body>
    <div class="title">CONTROLLED ASSETS DELIVERY SUMMARY</div>
    <?php if ($startDate && $endDate): ?>
        <div class="date_range">Period: <?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?></div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <th class="header-cell">School</th>
            <th class="header-cell">Address</th>
            <th class="header-cell">Delivery Date</th>
            <th class="header-cell">Item</th>
            <th class="header-cell">Description</th>
            <th class="header-cell">Stock No.</th>
            <th class="header-cell">Unit</th>
            <th class="header-cell">Quantity</th>
            <th class="header-cell">Unit Cost</th>
            <th class="header-cell">Total Cost</th>
            <th class="header-cell">Property Classification</th>
        </tr>
        <?php foreach ($items as $item): 
            $totalCost = $item['unit_cost'] * $item['quantity'];
        ?>
            <tr>
                <td class="data-cell"><?php echo htmlspecialchars($item['school_name']); ?></td>
                <td class="data-cell"><?php echo htmlspecialchars($item['address']); ?></td>
                <td class="data-cell text-center"><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></td>
                <td class="data-cell"><?php echo htmlspecialchars($item['item']); ?></td>
                <td class="data-cell"><?php echo htmlspecialchars($item['description']); ?></td>
                <td class="data-cell text-center"><?php echo htmlspecialchars($item['stock_no']); ?></td>
                <td class="data-cell text-center"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="data-cell text-center"><?php echo $item['quantity']; ?></td>
                <td class="data-cell text-center"><?php echo number_format($item['unit_cost'], 2); ?></td>
                <td class="data-cell text-center"><?php echo number_format($totalCost, 2); ?></td>
                <td class="data-cell text-center"><?php echo htmlspecialchars($item['property_classification']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
