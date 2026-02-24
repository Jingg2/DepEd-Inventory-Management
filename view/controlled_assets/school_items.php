<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\view\controlled_assests\school_items.php
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
    require_once __DIR__ . '/../../model/settingsModel.php';
    
    $supplyModel = new SupplyModel();
    $settingsModel = new SettingsModel();
    
    $schoolName = trim($_GET['school'] ?? '');
    if (empty($schoolName)) {
        header('Location: ' . $root . 'controlled_assets/deliveries');
        exit;
    }
    
    $items = $supplyModel->getItemsBySchool($schoolName);
    
    // Group items by receipt number
    $groupedItems = [];
    foreach ($items as $item) {
        $receiptNo = $item['receipt_no'] ?: 'Other / Uncategorized';
        if (!isset($groupedItems[$receiptNo])) {
            $groupedItems[$receiptNo] = [
                'receipt_no' => $receiptNo,
                'delivery_date' => $item['delivery_date'],
                'items' => []
            ];
        }
        $groupedItems[$receiptNo]['items'][] = $item;
    }

    
    $schoolDetails = $supplyModel->getSchoolByName($schoolName);
    
    $settings = $settingsModel->getAllSettings();
    $defaultLow = $settings['default_low_stock'] ?? 10;
    $defaultCritical = $settings['default_critical_stock'] ?? 5;

    // Calculate Condition Summary
    $conditionSummary = [
        'functional' => 0,
        'repair' => 0,
        'condemned' => 0,
        'lost' => 0,
        'total' => count($items)
    ];

    foreach ($items as $item) {
        $condition = strtolower($item['item_condition'] ?? 'functional');
        if (strpos($condition, 'repair') !== false) {
            $conditionSummary['repair']++;
        } elseif (strpos($condition, 'condemned') !== false) {
            $conditionSummary['condemned']++;
        } elseif (strpos($condition, 'lost') !== false) {
            $conditionSummary['lost']++;
        } else {
            $conditionSummary['functional']++;
        }
    }
} catch (Throwable $e) {
    die("<h1>Error Loading School Items</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($schoolName); ?> - Items - Inventory System</title>
    <link rel="stylesheet" href="<?php echo $root; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        window.basePath = '<?php echo $root; ?>';
    </script>
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
        .back-btn {
            background: var(--primary-emerald);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .back-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--primary-glow);
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
        .receipt-group {
            margin-bottom: 40px;
        }
        .receipt-header {
            background: #f8fafc;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 5px solid var(--primary-emerald);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .receipt-header h2 {
            margin: 0;
            font-size: 1.1rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .receipt-header .receipt-date {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
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
            <h1><?php echo htmlspecialchars($schoolName); ?> - Items</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/../includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <a href="<?php echo $root; ?>controlled_assets/deliveries" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Schools
            </a>
            <div style="display: flex; gap: 10px;">
                <button class="add-btn" onclick="openSchoolMonitoringModal()" style="background: var(--secondary-emerald);">
                    <i class="fas fa-chart-pie"></i> Monitor Status
                </button>
                <button class="add-btn" onclick="openEditSchoolModal()" style="background: var(--primary-emerald);">
                    <i class="fas fa-edit"></i> Edit School Info
                </button>
            </div>
        </div>

        <div class="search-filter-container" style="display: flex; gap: 15px; margin-bottom: 25px; background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div style="flex: 1; position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" id="search" placeholder="Search items in this school..." style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
            </div>
            <select id="filter-category" style="padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; min-width: 200px; box-sizing: border-box;">
                <option value="">All Categories</option>
                <?php 
                $uniqueCategories = $supplyModel->getAllCategories();
                foreach ($uniqueCategories as $cat): 
                ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="items-container">
            <?php if (empty($groupedItems)): ?>
                <div class="no-results" style="text-align: center; padding: 40px; color: #64748b; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                    No items found for this school.
                </div>
            <?php else: ?>
                <?php foreach ($groupedItems as $receiptNo => $group): ?>
                    <div class="receipt-group">
                        <div class="receipt-header">
                            <h2>
                                <i class="fas fa-file-invoice" style="color: var(--primary-emerald);"></i> 
                                Receipt #: <?php echo htmlspecialchars($receiptNo); ?>
                            </h2>
                            <?php if ($group['delivery_date']): ?>
                                <div class="receipt-date">
                                    <i class="far fa-calendar-alt"></i> 
                                    Delivery Date: <?php echo date('M d, Y', strtotime($group['delivery_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="supply-cards">
                            <?php foreach ($group['items'] as $supply): 
                                $qty = (int)$supply['quantity'];
                                
                                // Determine status badge based on CONDITION
                                $condition = $supply['item_condition'] ?? 'Functional';
                                $badgeClass = '';
                                $badgeText = $condition;
                                
                                if (stripos($condition, 'repair') !== false) { $badgeClass = 'status-critical'; } // Orange/Red
                                elseif (stripos($condition, 'condemned') !== false) { $badgeClass = 'status-out'; } // Red/Gray
                                elseif (stripos($condition, 'lost') !== false) { $badgeClass = 'status-out'; }
                                else { $badgeClass = 'status-in-stock'; } // Green

                                $isSemiExpendable = (stripos($supply['property_classification'] ?? '', 'Semi-Expendable') !== false);
                                $cardIcon = $isSemiExpendable ? 'fa-address-card' : 'fa-file-invoice';
                                $cardTitle = $isSemiExpendable ? 'View Property Card' : 'View Stock Card';
                            ?>
                                <div class="supply-card" 
                                     data-id="<?php echo htmlspecialchars($supply['supply_id']); ?>" 
                                     data-name="<?php echo htmlspecialchars($supply['item']); ?>" 
                                     data-category="<?php echo htmlspecialchars($supply['category']); ?>" 
                                     data-quantity="<?php echo $qty; ?>" 
                                     data-stock-no="<?php echo htmlspecialchars($supply['stock_no']); ?>" 
                                     data-unit="<?php echo htmlspecialchars($supply['unit']); ?>" 
                                     data-description="<?php echo htmlspecialchars($supply['description']); ?>" 
                                     data-unit-cost="<?php echo htmlspecialchars($supply['unit_cost']); ?>" 
                                     data-total-cost="<?php echo htmlspecialchars($supply['total_cost']); ?>" 
                                     data-status="<?php echo htmlspecialchars($supply['status']); ?>" 
                                     data-property-classification="<?php echo htmlspecialchars($supply['property_classification']); ?>"
                                     data-school="<?php echo htmlspecialchars($supply['school']); ?>"
                                     data-receipt-no="<?php echo htmlspecialchars($supply['receipt_no'] ?? ''); ?>"
                                     data-delivery-date="<?php echo htmlspecialchars($supply['delivery_date'] ?? ''); ?>"
                                     data-condition="<?php echo htmlspecialchars($condition); ?>"
                                     data-image="<?php echo !empty($supply['image']) ? 'data:image/jpeg;base64,' . base64_encode($supply['image']) : 'assets/default-item.png'; ?>">
                                    
                                    <div class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeText); ?></div>
                                    <i class="fas <?php echo ($isSemiExpendable ? 'fa-tools' : 'fa-building'); ?>" style="font-size: 3rem; margin: 20px auto; display: block; color: #cbd5e1; opacity: 0.5;"></i>
                                    
                                    <h3><?php echo htmlspecialchars($supply['item']); ?></h3>
                                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 5px;">
                                        <strong>Classification:</strong> <?php echo htmlspecialchars($supply['property_classification']); ?>
                                    </p>
                                    <p>Description: <?php echo htmlspecialchars($supply['description']); ?></p>
                                    
                                    <div class="qty-display <?php echo $badgeClass; ?>">
                                        <i class="fas fa-cubes"></i>
                                        Quantity: <span class="qty-value"><?php echo $qty; ?></span>
                                    </div>
                                    
                                    <div class="actions">
                                        <i class="fas fa-eye icon view-icon" title="View Details"></i>
                                        <i class="fas fa-clipboard-check icon update-condition-icon" title="Update Condition" style="color: var(--secondary-emerald);"></i>
                                        <i class="fas <?php echo $cardIcon; ?> icon stock-card-icon" title="<?php echo $cardTitle; ?>"></i>
                                        <!-- <i class="fas fa-edit icon edit-icon" title="Edit"></i> -->
                                        <!-- <i class="fas fa-trash icon delete-icon" title="Delete"></i> -->
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reusing Modals -->
    <?php include_once __DIR__ . '/../includes/add_supply_modal.php'; ?>
    <?php include_once __DIR__ . '/../includes/edit_supply_modal.php'; ?>
    <?php include_once __DIR__ . '/../supply_details_modal.php'; ?>
    <?php include_once __DIR__ . '/../stock_card_modal.php'; ?>
    <?php include_once __DIR__ . '/../includes/edit_school_modal.php'; ?>
    <?php include_once __DIR__ . '/school_monitoring_modal.php'; ?>
    <?php include_once __DIR__ . '/update_condition_modal.php'; ?>
    <?php include_once __DIR__ . '/../../includes/logout_modal.php'; ?>

    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/supply_modals.js?v=<?php echo time(); ?>"></script>
    <script>
        // School Monitoring Logic (Condition Based)
        function openSchoolMonitoringModal() {
            const modal = document.getElementById('school-monitoring-modal');
            if (!modal) return;

            // Data from PHP
            const stats = <?php echo json_encode($conditionSummary); ?>;
            const items = <?php echo json_encode($items); ?>;
            
            // Populate Counts
            document.getElementById('monitor-functional').textContent = stats.functional;
            document.getElementById('monitor-repair').textContent = stats.repair;
            document.getElementById('monitor-condemned').textContent = stats.condemned;
            document.getElementById('monitor-lost').textContent = stats.lost;
            document.getElementById('monitor-total').textContent = stats.total;

            // Update Progress Bars (Percentage Calculation)
            const total = stats.total > 0 ? stats.total : 1;
            document.getElementById('bar-functional').style.width = (stats.functional / total * 100) + '%';
            document.getElementById('bar-repair').style.width = (stats.repair / total * 100) + '%';
            document.getElementById('bar-condemned').style.width = (stats.condemned / total * 100) + '%';
            document.getElementById('bar-lost').style.width = (stats.lost / total * 100) + '%';

            // --- Render Table ---
            const tableBody = document.getElementById('monitor-table-body');
            const searchInput = document.getElementById('monitor-search');
            
            function renderTable(itemsToRender) {
                tableBody.innerHTML = '';
                if (itemsToRender.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px; color: #64748b;">No items found</td></tr>';
                    return;
                }

                itemsToRender.forEach(item => {
                    const condition = (item.item_condition || 'Functional');
                    let badgeClass = 'status-in-stock';
                    if (condition.toLowerCase().includes('repair')) badgeClass = 'status-critical';
                    else if (condition.toLowerCase().includes('condemned')) badgeClass = 'status-out';
                    else if (condition.toLowerCase().includes('lost')) badgeClass = 'status-out';

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; color: #1e293b;">${item.item}</td>
                        <td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; color: #475569;">${item.stock_no || '-'}</td>
                        <td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; color: #475569;">${item.delivery_date ? new Date(item.delivery_date).toLocaleDateString() : '-'}</td>
                        <td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: center;">
                            <span class="status-badge ${badgeClass}" style="font-size: 0.75rem;">${condition}</span>
                        </td>
                        <td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: center;">
                            <button class="small-btn update-btn" 
                                data-id="${item.supply_id}" 
                                data-condition="${condition}"
                                style="background: var(--primary-emerald); color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">
                                <i class="fas fa-edit"></i> Update
                            </button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });

                // Attach Event Listeners to new buttons
                document.querySelectorAll('.update-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                         // Mocking a click on the card icon to reuse existing logic
                         const id = this.getAttribute('data-id');
                         const condition = this.getAttribute('data-condition');
                         
                         // We need to pass data to the modal. Since reusing the existing logic is tricky without a card element,
                         // let's manually trigger the update modal logic here.
                         const updateModal = document.getElementById('update-condition-modal');
                         if (updateModal) {
                             document.getElementById('update-condition-id').value = id;
                             document.getElementById('item-condition-select').value = condition;
                             
                             updateModal.style.zIndex = '100001'; // Ensure it's above the monitoring modal
                             updateModal.style.display = 'block';
                             requestAnimationFrame(() => updateModal.classList.add('active'));
                         }
                    });
                });
            }

            renderTable(items);

            // Search Filter
            searchInput.onkeyup = function() {
                const term = this.value.toLowerCase();
                const filtered = items.filter(item => 
                    (item.item || '').toLowerCase().includes(term) || 
                    (item.stock_no || '').toLowerCase().includes(term)
                );
                renderTable(filtered);
            };

            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('active');
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
            }, 10);
        }

        function closeSchoolMonitoringModal() {
            const modal = document.getElementById('school-monitoring-modal');
            if (modal) {
                modal.classList.remove('active');
                modal.style.opacity = '0';
                modal.style.visibility = 'hidden';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
        }

        // Close on outside click
        window.onclick = function(event) {
            const monitorModal = document.getElementById('school-monitoring-modal');
            if (event.target == monitorModal) {
                closeSchoolMonitoringModal();
            }
            // ... existing modal close logic ...
        }

        // Populate edit school modal
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($schoolDetails): ?>
            const details = <?php echo json_encode($schoolDetails); ?>;
            console.log("School details found:", details);
            
            const fields = {
                'edit-school-id': details.id,
                'edit-school-school-id': details.school_id,
                'edit-school-name': details.school_name,
                'edit-school-address': details.address,
                'edit-school-contact': details.contact_no
            };
            
            for (const [id, value] of Object.entries(fields)) {
                const el = document.getElementById(id);
                if (el) el.value = value || '';
            }
            <?php else: ?>
            console.warn("No school details found for '<?php echo addslashes($schoolName); ?>'");
            <?php endif; ?>
        });
    </script>
</body>
</html>
