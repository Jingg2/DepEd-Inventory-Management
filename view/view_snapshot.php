<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\view\view_snapshot.php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../model/snapshotModel.php';

initSecureSession();
requireAuth();

$month = $_GET['month'] ?? '';
if (empty($month)) {
    die("Month parameter is required.");
}

$model = new SnapshotModel();
$items = $model->getSnapshotData($month);
$monthDisplay = date('F Y', strtotime($month . '-01'));

$root = '../';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <div class="header-section">
        <h1>
            <i class="fas fa-history"></i>
            Inventory Snapshot: <?php echo $monthDisplay; ?>
        </h1>
        <div class="header-actions">
            <a href="reports.php" class="cancel-btn">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
            <a href="<?php echo $root; ?>api/export_supply_excel.php?month=<?php echo $month; ?>" class="download-btn">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="standard-table">
            <thead>
                <tr>
                    <th>Stock No.</th>
                    <th>Item/Description</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Prev. Bal</th>
                    <th>Acq.</th>
                    <th>Iss.</th>
                    <th>Ending Bal</th>
                    <th>Unit Cost</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="10" style="text-align: center;">No data found for this snapshot.</td></tr>
                <?php else: ?>
                    <?php 
                    $grandTotal = 0;
                    foreach ($items as $item): 
                        $grandTotal += (float)$item['total_cost'];
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['stock_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['item'] . ($item['description'] ? ' - ' . $item['description'] : '')); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td align="center"><?php echo (float)$item['previous_month']; ?></td>
                            <td align="center"><?php echo (float)$item['requisition']; ?></td>
                            <td align="center"><?php echo (float)$item['issuance']; ?></td>
                            <td align="center" style="font-weight: bold;"><?php echo (float)$item['quantity']; ?></td>
                            <td align="right"><?php echo number_format($item['unit_cost'], 2); ?></td>
                            <td align="right" style="font-weight: bold;"><?php echo number_format($item['total_cost'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f8fafc; font-weight: bold;">
                        <td colspan="9" align="right">GRAND TOTAL:</td>
                        <td align="right" style="color: var(--primary-emerald); font-size: 1.1rem;">
                            ₱<?php echo number_format($grandTotal, 2); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
