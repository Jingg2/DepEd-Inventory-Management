<?php
require_once __DIR__ . '/../includes/security.php';
initSecureSession();
requireAuth();

require_once __DIR__ . '/../model/supplyModel.php';
$supplyModel = new SupplyModel();
$returnedSupplies = $supplyModel->getReturnedSupplies();

// Root path calculation
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir);
$root = rtrim($scriptDir, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returned Items Report - Inventory System</title>
    <link rel="stylesheet" href="<?php echo $root; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .report-title h2 {
            margin: 0;
            color: var(--navy-900);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .report-table-container {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .returned-table {
            width: 100%;
            border-collapse: collapse;
        }
        .returned-table th {
            text-align: left;
            padding: 15px;
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #f1f5f9;
        }
        .returned-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.95rem;
        }
        .returned-table tr:hover {
            background: #f8fafc;
        }
        .qty-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .qty-returned {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .qty-new {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="<?php echo $root; ?>images/deped_bogo_logo.png" alt="Logo">
            <h2>Inventory System</h2>
        </div>
        <ul>
            <li><a href="<?php echo $root; ?>inventory" style="background: rgba(66, 167, 106, 0.1); color: var(--primary-emerald);"><i class="fas fa-arrow-left"></i> <span>Back to Inventory</span></a></li>
            <li class="divider"></li>
            <li><a href="#" class="active"><i class="fas fa-recycle"></i> <span>Returned Items</span></a></li>
            <li><a href="<?php echo $root; ?>controlled_assets/deliveries"><i class="fas fa-truck"></i> <span>Deliveries</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1 style="color: var(--navy-900); font-weight: 800;"><i class="fas fa-file-invoice" style="color: var(--primary-emerald); margin-right: 12px;"></i> Returned/Used Items Report</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="report-header">
            <div class="report-title">
                <h2><i class="fas fa-list-ul" style="color: var(--primary-emerald);"></i> Inventory Overview</h2>
                <p style="margin: 5px 0 0 0; color: #64748b; font-size: 0.9rem;">Listing all items with returned or used stock quantities</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="view-request-btn" id="admin-view-requisition" style="background: #0d2137; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-clipboard-list"></i> Requisition Slip (0)
                </button>
                <button onclick="window.print()" class="add-btn" style="background: var(--navy-700);">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <div class="report-table-container">
            <?php if (empty($returnedSupplies)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Returned Items Found</h3>
                    <p>There are currently no items in the inventory with a returned quantity.</p>
                </div>
            <?php else: ?>
                <table class="returned-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th style="text-align: center;">Stock</th>
                            <th style="text-align: center;">Returned/Used</th>
                            <th style="text-align: center;">Total Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returnedSupplies as $item): 
                            $newQty = (int)$item['quantity'];
                            $retQty = (int)$item['returned_quantity'];
                            $total = $newQty + $retQty;
                        ?>
                            <tr class="supply-card" data-id="<?php echo htmlspecialchars($item['supply_id']); ?>" data-name="<?php echo htmlspecialchars($item['item']); ?>" data-returned-quantity="<?php echo $retQty; ?>" data-stock-no="<?php echo htmlspecialchars($item['stock_no'] ?? ''); ?>" data-unit="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>" data-description="<?php echo htmlspecialchars($item['description'] ?? ''); ?>" data-quantity="<?php echo $newQty; ?>">
                                <td style="font-weight: 600; color: var(--navy-800);"><?php echo htmlspecialchars($item['item']); ?></td>
                                <td><span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem;"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                <td style="max-width: 300px; color: #64748b; font-size: 0.85rem;"><?php echo htmlspecialchars($item['description'] ?: '-'); ?></td>
                                <td style="text-align: center;">
                                    <span class="qty-badge qty-new"><?php echo $newQty; ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="qty-badge qty-returned">
                                        <i class="fas fa-recycle"></i> <?php echo $retQty; ?>
                                    </span>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: var(--navy-900);">
                                    <?php echo $total; ?>
                                </td>
                                <td style="text-align: center;">
                                    <button class="add-btn btn-admin-request-returned" style="padding: 6px 12px; font-size: 0.8rem; background: #10b981;">
                                        <i class="fas fa-plus"></i> Request
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ . '/includes/admin_requisition_modal.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var basePath = '<?php echo addslashes($root); ?>';
    </script>
    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/supply_modals.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/admin_requisition.js?v=<?php echo time(); ?>"></script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
</body>
</html>
