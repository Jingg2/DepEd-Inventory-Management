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
    $supplyModel = new SupplyModel();
    $schools = $supplyModel->getSchoolsList();
    
} catch (Throwable $e) {
    die("<h1>Error Loading Reports</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controlled Assets Reports - Inventory System</title>
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
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            height: 100%;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .report-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: white;
            box-shadow: 0 4px 10px var(--primary-glow);
        }

        .report-card h3 {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .report-card p {
            color: #64748b;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 0.9rem;
            flex: 1;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary-emerald);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: auto;
        }

        .download-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .filter-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }

        .filter-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
        }

        .filter-select, .filter-date {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 10px;
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
            <li><a href="<?php echo $root; ?>controlled_assets/deliveries"><i class="fas fa-box"></i> <span>Deliveries</span></a></li>
            <li><a href="<?php echo $root; ?>controlled_assets/reports" class="active"><i class="fas fa-file-alt"></i> <span>Reports</span></a></li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>inventory" style="background: rgba(66, 167, 106, 0.1); color: var(--primary-emerald);"><i class="fas fa-arrow-left"></i> <span>Back to Inventory</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Controlled Assets Reports</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/../includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="reports-grid">
            <!-- Delivery Summary Report -->
            <div class="report-card">
                <div class="report-icon">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <h3>Delivery Summary Report</h3>
                <p>Generate a comprehensive log of all assets delivered to schools. Filter by date range to track recent deliveries.</p>
                
                <div class="filter-section">
                    <label class="filter-label">Date Range (Optional)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="date" id="delivery-start" class="filter-date">
                        <input type="date" id="delivery-end" class="filter-date">
                    </div>
                </div>

                <a href="#" onclick="downloadDeliveryReport()" class="download-btn">
                    <i class="fas fa-download"></i> Download Report
                </a>
            </div>

            <!-- School Inventory Report -->
            <div class="report-card">
                <div class="report-icon">
                    <i class="fas fa-school"></i>
                </div>
                <h3>School Inventory Report</h3>
                <p>Export a detailed inventory sheet for a specific school. Includes item condition, quantity, and specifications.</p>
                
                <div class="filter-section">
                    <label class="filter-label">Select School</label>
                    <select id="school-select" class="filter-select">
                        <option value="">-- Choose School --</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?php echo htmlspecialchars($school['school']); ?>">
                                <?php echo htmlspecialchars($school['school']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <a href="#" onclick="downloadSchoolInventory()" class="download-btn">
                    <i class="fas fa-file-excel"></i> Export Inventory
                </a>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../../includes/logout_modal.php'; ?>

    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script>
        function downloadDeliveryReport() {
            const start = document.getElementById('delivery-start').value;
            const end = document.getElementById('delivery-end').value;
            
            let url = '<?php echo $root; ?>api/export_delivery_summary.php';
            const params = [];
            
            if (start) params.push('start_date=' + start);
            if (end) params.push('end_date=' + end);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        }

        function downloadSchoolInventory() {
            const school = document.getElementById('school-select').value;
            
            if (!school) {
                alert('Please select a school first.');
                return;
            }
            
            const url = '<?php echo $root; ?>api/export_school_inventory.php?school=' + encodeURIComponent(school);
            window.location.href = url;
        }
    </script>
</body>
</html>
