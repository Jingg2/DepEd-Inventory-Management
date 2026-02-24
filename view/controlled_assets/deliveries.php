<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Root path calculation
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir);
if (basename($scriptDir) === 'controlled_assets') {
    $scriptDir = dirname(dirname($scriptDir));
}
$root = rtrim($scriptDir, '/') . '/';

try {
    require_once __DIR__ . '/../../includes/security.php';
    initSecureSession();
    requireAuth();

    require_once __DIR__ . '/../../model/supplyModel.php';
    require_once __DIR__ . '/../../model/requisitionModel.php';
    require_once __DIR__ . '/../../model/settingsModel.php';
    
    $supplyModel = new SupplyModel();
    $reqModel = new RequisitionModel();
    $settingsModel = new SettingsModel();
    
    $schools = $supplyModel->getSchoolsList();
    $uniqueCategories = $supplyModel->getAllCategories();
    
    $settings = $settingsModel->getAllSettings();
    $defaultLow = $settings['default_low_stock'] ?? 10;
    $defaultCritical = $settings['default_critical_stock'] ?? 5;
    
    $reqStats = $reqModel->getRequisitionStats();
    $pendingCount = $reqStats['pending'] ?? 0;
    
    // Action path for the modal form - points to the main supply.php controller
    $actionPath = $root . 'supply.php';
} catch (Throwable $e) {
    die("<h1>Error Loading Controlled Assets Supply</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controlled Assets Deliveries - Inventory System</title>
    <link rel="stylesheet" href="<?php echo $root; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        var basePath = '<?php echo addslashes($root); ?>';
        (function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && window.innerWidth > 1024) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
    <style>
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .add-btn {
            background: var(--primary-emerald);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .add-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--primary-glow);
        }
        .inventory-table th {
            background: #f8fafc;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-functional { background: #dcfce7; color: #166534; }
        .status-repair { background: #fef9c3; color: #854d0e; }
        .status-not-functional { background: #fee2e2; color: #991b1b; }
        
        /* Modal compatibility fixes */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            width: 80%;
            max-width: 800px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            overflow: hidden;
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
            <li><a href="<?php echo $root; ?>controlled_assets"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo $root; ?>controlled_assets/deliveries" class="active"><i class="fas fa-box"></i> <span>Deliveries</span></a></li>
            <li><a href="<?php echo $root; ?>controlled_assets/reports"><i class="fas fa-file-alt"></i> <span>Reports</span></a></li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>inventory" style="background: rgba(66, 167, 106, 0.1); color: var(--primary-emerald);"><i class="fas fa-arrow-left"></i> <span>Back to Inventory</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Controlled Assets Deliveries</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/../includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="action-bar">
            <h2 style="margin: 0; color: var(--navy-800); font-size: 1.25rem;">Asset Inventory</h2>
            <div style="display: flex; gap: 10px;">
                <button class="add-btn" onclick="openDeliveryModal()">
                    <i class="fas fa-truck-loading"></i> New Delivery
                </button>
            </div>
        </div>

        <div class="search-filter-container" style="display: flex; gap: 15px; margin-bottom: 25px; background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div style="flex: 1; position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" id="search" placeholder="Search assets..." style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
            </div>
            <select id="filter-category" style="padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; min-width: 200px; box-sizing: border-box;">
                <option value="">All Categories</option>
                <?php foreach ($uniqueCategories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="supply-cards">
            <?php if (empty($schools)): ?>
                <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                    No schools with delivered items found.
                </div>
            <?php else: ?>
                <?php foreach ($schools as $school): 
                    $itemCount = (int)$school['item_count'];
                    $totalQty = (int)$school['total_quantity'];
                ?>
                    <div class="supply-card school-card" 
                         data-school="<?php echo htmlspecialchars($school['school']); ?>" 
                         data-item-count="<?php echo $itemCount; ?>"
                         data-total-quantity="<?php echo $totalQty; ?>">
                        
                        <i class="fas fa-school" style="font-size: 3rem; margin: 20px auto; display: block; color: var(--primary-emerald); opacity: 0.8;"></i>
                        
                        <h3><?php echo htmlspecialchars($school['school']); ?></h3>
                        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 10px;">
                            <i class="fas fa-box"></i> <strong><?php echo $itemCount; ?></strong> Item<?php echo $itemCount != 1 ? 's' : ''; ?>
                        </p>
                        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 15px;">
                            <i class="fas fa-cubes"></i> Total Quantity: <strong><?php echo $totalQty; ?></strong>
                        </p>
                        
                        <div class="actions">
                            <i class="fas fa-eye icon view-school-icon" title="View Items" style="cursor: pointer;"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reusing Modals -->
    <?php include_once __DIR__ . '/../includes/add_supply_modal.php'; ?>
    <?php include_once __DIR__ . '/../includes/delivery_modal.php'; ?>
    <?php include_once __DIR__ . '/../includes/edit_supply_modal.php'; ?>
    <?php include_once __DIR__ . '/../supply_details_modal.php'; ?>
    <?php include_once __DIR__ . '/../stock_card_modal.php'; ?>
    <?php include_once __DIR__ . '/../../includes/logout_modal.php'; ?>

    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/delivery.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/supply_modals.js?v=<?php echo time(); ?>"></script>
    <script>
        // Handle school card clicks via eye icon (Using event delegation for durability)
        document.addEventListener('DOMContentLoaded', function() {
            const cardsContainer = document.querySelector('.supply-cards');
            if (cardsContainer) {
                cardsContainer.addEventListener('click', function(e) {
                    const viewIcon = e.target.closest('.view-school-icon');
                    if (viewIcon) {
                        const card = viewIcon.closest('.school-card');
                        const schoolName = card.dataset.school;
                        if (schoolName) {
                            window.location.href = basePath + 'controlled_assets/school_items?school=' + encodeURIComponent(schoolName);
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>

