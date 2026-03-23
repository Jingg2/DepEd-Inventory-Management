<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();

    require_once __DIR__ . '/../model/requisitionModel.php';
    require_once __DIR__ . '/../model/departmentModel.php';
    require_once __DIR__ . '/../db/database.php';

    $model = new RequisitionModel();
    $deptModel = new DepartmentModel();
    $departments = $deptModel->getAllDepartments();
    $db = new Database();
    $conn = $db->getConnection();

    // Get all approved requisitions for RIS exports
    $sql = "SELECT r.*, 
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            d.department_name,
            GROUP_CONCAT(DISTINCT s.item SEPARATOR ', ') as item_names,
            SUM(CASE WHEN s.property_classification LIKE 'Semi-Expendable%' THEN 1 ELSE 0 END) as semi_expendable_count
            FROM requisition r
            JOIN employee e ON r.employee_id = e.employee_id
            JOIN department d ON r.department_id = d.department_id
            LEFT JOIN request_item ri ON r.requisition_id = ri.requisition_id
            LEFT JOIN supply s ON ri.supply_id = s.supply_id
            WHERE r.status = 'Approved'
            GROUP BY r.requisition_id
            ORDER BY r.approved_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $approved_requisitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pendingStats = $model->getRequisitionStats();
    $pendingCount = $pendingStats['pending'] ?? 0;
} catch (Throwable $e) {
    die("<h1>Error Loading Reports</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p><p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
}
?>
<?php 
// Robust root path calculation
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir);
if (basename($scriptDir) === 'view') {
    $scriptDir = dirname($scriptDir);
}
$root = rtrim($scriptDir, '/') . '/';
$urlRoot = str_replace(' ', '%20', $root);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory System</title>
    <link rel="stylesheet" href="<?php echo $urlRoot; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        var basePath = '<?php echo addslashes($urlRoot); ?>';
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
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .report-icon {
            width: 45px;
            height: 45px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px var(--primary-glow);
        }

        .report-icon i {
            font-size: 20px;
            color: white;
        }

        .report-card h3 {
            font-size: 1rem;
            color: #2d3748;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .report-card p {
            color: #64748b;
            margin-bottom: 15px;
            line-height: 1.4;
            font-size: 0.85rem;
            flex: 1;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--gradient-primary);
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            margin-top: auto;
            box-shadow: 0 2px 6px var(--primary-glow);
        }

        .download-btn:hover {
            opacity: 0.9;
        }

        .download-btn i {
            font-size: 12px;
        }

        /* Standardized styles moved to dashboard.css */

        .export-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-emerald);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .export-link:hover {
            color: var(--primary-hover);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: var(--bg-emerald-light);
            color: var(--navy-800);
        }

        /* Responsive - only for small screens */
        @media (max-width: 992px) {
            .reports-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
        .header h1 {
            color: var(--navy-900) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin: 0;
        }

        /* Multi-select Styles */
        .multi-select-container {
            width: 250px;
            position: relative;
            font-family: 'Inter', sans-serif;
        }

        .multi-select-trigger {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            min-height: 40px;
        }

        .multi-select-trigger:hover {
            border-color: var(--primary-emerald);
        }

        .multi-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-top: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
            max-height: 300px;
            overflow-y: auto;
        }

        .multi-select-dropdown.active {
            display: block;
        }

        .multi-select-search {
            padding: 8px;
            border-bottom: 1px solid #edf2f7;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
        }

        .multi-select-search input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .multi-select-options {
            padding: 5px 0;
        }

        .multi-option {
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.85rem;
        }

        .multi-option:hover {
            background: #f7fafc;
        }

        .multi-option input {
            margin: 0;
            cursor: pointer;
        }

        .multi-option.selected {
            background: #f0fff4;
            color: #2f855a;
        }

        .no-options {
            padding: 15px;
            text-align: center;
            color: #718096;
            font-size: 0.85rem;
            font-style: italic;
        }

        #trigger-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 5px;
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
            <li><a href="<?php echo $root; ?>dashboard" class="<?php echo ($currentRoute == '/dashboard') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo $root; ?>inventory" class="<?php echo ($currentRoute == '/inventory') ? 'active' : ''; ?>"><i class="fas fa-box"></i> <span>Supply</span></a></li>
            <li class="divider"></li>
            <li>
                <a href="<?php echo $root; ?>requests" class="<?php echo ($currentRoute == '/requests') ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> <span>Request</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="sidebar-badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>employees" class="<?php echo ($currentRoute == '/employees') ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Employee</span></a></li>
            <li><a href="<?php echo $root; ?>reports" class="<?php echo ($currentRoute == '/reports') ? 'active' : ''; ?>"><i class="fas fa-file-excel"></i> <span>Reports</span></a></li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>settings" class="<?php echo ($currentRoute == '/settings') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>logout" class="logout-link" onclick="showLogoutModal(event, this.href);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1 style="color: var(--navy-900); font-weight: 800;"><i class="fas fa-file-excel" style="color: var(--primary-emerald); margin-right: 12px;"></i> Excel Reports</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-file-circle-check" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: #2ecc71;"></i>
                <h3>Approved RIS</h3>
                <p><?php echo count($approved_requisitions); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-building" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: #764ba2;"></i>
                <h3>Unique Offices</h3>
                <p><?php 
                    $uniqueDepts = array_unique(array_column($approved_requisitions, 'department_name'));
                    echo count($uniqueDepts); 
                ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: var(--primary-emerald);"></i>
                <h3>Current Month</h3>
                <p><?php echo date('F'); ?></p>
            </div>
        </div>

        <!-- Date Range Selector -->
        <div class="search-filter-container">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="month-selector">
                    <i class="fas fa-calendar-alt" style="color: var(--primary-emerald);"></i>
                    Quick Month:
                </label>
                <select id="month-selector">
                    <option value="current">Current Month (Live)</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; border-left: 2px solid #edf2f7; padding-left: 20px;">
                <label>Range:</label>
                <input type="date" id="start-date">
                <span style="color: #666;">to</span>
                <input type="date" id="end-date">
                <button id="apply-range" class="btn-primary" style="padding: 10px 15px; font-size: 0.85rem; margin-top: 0;">
                    Apply Filter
                </button>
                <button id="reset-range" class="cancel-btn" style="padding: 10px 15px; font-size: 0.85rem;">
                    Reset
                </button>
                <button id="save-snapshot-btn" class="btn-primary" style="padding: 10px 15px; font-size: 0.85rem; background: var(--primary-emerald); border-color: var(--primary-emerald);">
                    <i class="fas fa-camera"></i> Save Snapshot
                </button>
                <button id="view-snapshot-btn" class="download-btn" style="padding: 10px 15px; font-size: 0.85rem; background: var(--gradient-navy); display: none;">
                    <i class="fas fa-eye"></i> View Saved Data
                </button>
            </div>
            
            <div style="display: flex; align-items: center; gap: 10px; border-left: 2px solid #edf2f7; padding-left: 20px;">
                <label for="dept-selector">Office:</label>
                <select id="dept-selector">
                    <option value="">All Offices</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; border-left: 2px solid #edf2f7; padding-left: 20px; position: relative;">
                <label for="request-multi-select">Request No:</label>
                <div class="multi-select-container" id="request-multi-container">
                    <div class="multi-select-trigger" id="request-trigger">
                        <span id="trigger-text">All Approved Requests</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="multi-select-dropdown" id="request-dropdown">
                        <div class="multi-select-search">
                            <input type="text" id="request-search" placeholder="Search request...">
                        </div>
                        <div class="multi-select-options" id="request-options">
                            <div class="no-options">Select an office first</div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="request-ids" value="">
            </div>
            <span id="snapshot-info" style="color: #666; font-size: 0.85rem; font-style: italic; margin-left: auto;"></span>
        </div>

        <div class="reports-grid">
            <!-- RSMI Summary Report -->
            <div class="report-card">
                <div class="report-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>RSMI Summary Report</h3>
                <p>Report of Supplies and Materials Issued - Complete summary of all approved requisitions and issued items.</p>
                <a href="<?php echo $urlRoot; ?>api/export_rsmi_excel.php" class="download-btn" id="rsmi-download-btn">
                    <i class="fas fa-download"></i>
                    Download RSMI Summary
                </a>
            </div>

            <!-- RIS Categorized by Office -->
            <div class="report-card">
                <div class="report-icon" style="background: var(--gradient-navy); box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <i class="fas fa-building"></i>
                </div>
                <h3>RIS Grouped by Office</h3>
                <p>Consolidated report of all approved requisitions, categorized and sectioned by Department/Office.</p>
                <a href="<?php echo $urlRoot; ?>api/export_ris_by_office.php" class="download-btn" id="ris-office-download-btn">
                    <i class="fas fa-download"></i>
                    Download RIS by Office
                </a>
            </div>

            <!-- Individual RIS Reports -->
            <div class="report-card">
                <div class="report-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Individual RIS Reports</h3>
                <p>Requisition and Issue Slip - Download individual reports for each approved requisition (see table below).</p>
                <a href="#requisitions" class="download-btn" style="background: var(--gradient-navy); box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <i class="fas fa-list"></i>
                    View Available Reports
                </a>
            </div>

            <!-- Monthly Inventory Report (Flow Tracking) -->
            <div class="report-card">
                <div class="report-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <h3>Monthly Inventory Report</h3>
                <p>Standard flow report tracking previous balance, acquisitions, and issuances for the month.</p>
                <a href="<?php echo $root; ?>api/export_supply_excel.php" class="download-btn" id="supply-download-btn">
                    <i class="fas fa-download"></i>
                    Download Monthly Inventory
                </a>
            </div>

            <!-- RCPI - Consumable / Expendable -->
            <div class="report-card">
                <div class="report-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 10px rgba(102,126,234,0.25);">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3>RCPI &mdash; Consumable / Expendable</h3>
                <p>Report on the Physical Count of Inventories (Appendix 66) for <strong>consumable and expendable</strong> supplies only.</p>
                <a href="<?php echo $root; ?>api/export_rcpi_consumable_excel.php" class="download-btn" id="rcpi-consumable-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 10px rgba(102,126,234,0.2);">
                    <i class="fas fa-download"></i>
                    Download RCPI (Consumable)
                </a>
            </div>

            <!-- RCPI - Semi-Expendable Low Value -->
            <div class="report-card">
                <div class="report-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); box-shadow: 0 4px 10px rgba(245,87,108,0.25);">
                    <i class="fas fa-tags"></i>
                </div>
                <h3>RCPI &mdash; Semi-Expendable (Low Value &#8369;5,000 &amp; below)</h3>
                <p>Report on the Physical Count of Inventories (Appendix 66) for <strong>semi-expendable items</strong> with unit cost <strong>&#8369;5,000 and below</strong>.</p>
                <a href="<?php echo $root; ?>api/export_rcpi_semi_lv_excel.php" class="download-btn" id="rcpi-semi-lv-btn" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); box-shadow: 0 4px 10px rgba(245,87,108,0.2);">
                    <i class="fas fa-download"></i>
                    Download RCPI (Semi-Exp LV)
                </a>
            </div>

            <!-- RPCSP - Semi-Expendable High Value -->
            <div class="report-card">
                <div class="report-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 4px 10px rgba(79,172,254,0.25);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h3>RPCSP &mdash; Semi-Expendable (Above &#8369;5,000)</h3>
                <p>Report on the Physical Count of Semi-Expendable Property (Annex A.8) for items with unit cost <strong>above &#8369;5,000</strong>. Submitted annually and by fund.</p>
                <a href="<?php echo $root; ?>api/export_rpcsp_excel.php" class="download-btn" id="rpcsp-btn" style="background: linear-gradient(135deg, #4facfe 0%, #00c6fb 100%); box-shadow: 0 4px 10px rgba(79,172,254,0.2);">
                    <i class="fas fa-download"></i>
                    Download RPCSP
                </a>
            </div>

            <!-- RPCPPE - Property Plant and Equipment -->
            <div class="report-card">
                <div class="report-icon" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); box-shadow: 0 4px 10px rgba(247,151,30,0.25);">
                    <i class="fas fa-desktop"></i>
                </div>
                <h3>RPCPPE &mdash; Property, Plant &amp; Equipment</h3>
                <p>Report on the Physical Count of Property, Plant and Equipment (Appendix 5). Submitted <strong>annually</strong> for PPE items.</p>
                <a href="<?php echo $root; ?>api/export_rpcppe_excel.php" class="download-btn" id="rpcppe-btn" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #222; box-shadow: 0 4px 10px rgba(247,151,30,0.2);">
                    <i class="fas fa-download"></i>
                    Download RPCPPE
                </a>
            </div>

            <!-- Waste Materials Report (WMR) -->
            <div class="report-card">
                <div class="report-icon" style="background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3>Waste Materials Report</h3>
                <p>Appendix 65 (WMR) - Report of unserviceable items designated for disposal or destruction.</p>
                <a href="<?php echo $root; ?>api/export_wmr_excel.php" class="download-btn" id="wmr-download-btn" style="background: var(--gradient-danger); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2);">
                    <i class="fas fa-download"></i>
                    Download WMR
                </a>
            </div>

            <!-- Monthly Acquisition Log (Stocking Tracking) -->
            <div class="report-card">
                <div class="report-icon" style="background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <h3>Monthly Acquisition Log</h3>
                <p>New acquisition tracking report - Detailed list of all stockings, deliveries, and manual restocks for the period.</p>
                <a href="<?php echo $root; ?>api/export_acquisition_excel.php" class="download-btn" id="acquisition-download-btn" style="background: linear-gradient(135deg, #4299e1 0%, #2b6cb0 100%); box-shadow: 0 4px 10px rgba(66, 153, 225, 0.2);">
                    <i class="fas fa-file-invoice"></i>
                    Download Acquisition Log
                </a>
            </div>
        </div>

        <!-- Approved Requisitions List -->
        <div class="table-section" id="requisitions">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                Approved Requisitions
            </h3>
            
            <?php if (count($approved_requisitions) > 0): ?>
                <table class="standard-table requisition-table">
                    <thead>
                        <tr>
                            <th>RIS No.</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Items</th>
                            <th>Approved Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_requisitions as $index => $req): ?>
                            <tr<?php echo $index >= 5 ? ' class="hidden-req-row" style="display:none;"' : ''; ?>>
                                <td><strong><?php echo htmlspecialchars($req['requisition_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['department_name']); ?></td>
                                <td title="<?php echo htmlspecialchars($req['item_names'] ?? ''); ?>">
                                    <?php echo htmlspecialchars(strlen($req['item_names']??'') > 40 ? substr($req['item_names'], 0, 40) . '...' : ($req['item_names']??'N/A')); ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($req['approved_date'])); ?></td>
                                <td><span class="badge badge-success"><?php echo htmlspecialchars($req['status']); ?></span></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <a href="<?php echo $urlRoot; ?>api/export_ris_excel.php?id=<?php echo $req['requisition_id']; ?>" class="export-link" style="color: #1d6f42; font-weight: 700;">
                                            <i class="fas fa-file-excel"></i>
                                            Download RIS
                                        </a>
                                        <a href="<?php echo $urlRoot; ?>api/export_rsmi_excel.php?id=<?php echo $req['requisition_id']; ?>" class="export-link" style="color: #217346; font-weight: 700;">
                                            <i class="fas fa-file-excel"></i>
                                            Download RSMI
                                        </a>
                                        <?php if (($req['semi_expendable_count'] ?? 0) > 0): ?>
                                        <a href="<?php echo $urlRoot; ?>api/export_ics_excel.php?id=<?php echo $req['requisition_id']; ?>" class="export-link" style="color: #a87e00; font-weight: 700;">
                                            <i class="fas fa-address-card"></i>
                                            Download ICS
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php if (count($approved_requisitions) > 5): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <button id="btnToggleReqs" class="btn-primary" style="padding: 10px 20px; font-size: 0.9rem;">
                        <i class="fas fa-chevron-down"></i> View All (<?php echo count($approved_requisitions); ?> records)
                    </button>
                </div>
            <?php endif; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px 0;">
                    <i class="fas fa-info-circle"></i> No approved requisitions available for export.
                </p>
            <?php endif; ?>
        <!-- Recent Snapshots Table -->
        <div class="table-container" style="margin-top: 30px;">
            <div class="table-header">
                <h3><i class="fas fa-history"></i> Recent Inventory Snapshots</h3>
                <span id="snapshot-info" class="badge badge-info">0 snapshot(s) available</span>
            </div>
            <table class="requisition-table" id="snapshots-table">
                <thead>
                    <tr>
                        <th>Snapshot Month</th>
                        <th>Created Date</th>
                        <th>Item Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="snapshots-body">
                    <!-- Loaded via JS -->
                    <tr><td colspan="4" style="text-align: center;">Loading snapshots...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <script src="<?php echo $root; ?>js/dashboard.js"></script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
    
    <script>
    // Load available snapshots on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Approved Requisitions
        const btnToggleReqs = document.getElementById('btnToggleReqs');
        if (btnToggleReqs) {
            btnToggleReqs.addEventListener('click', function() {
                const hiddenRows = document.querySelectorAll('.hidden-req-row');
                const isExpanded = hiddenRows[0]?.style.display !== 'none';
                
                hiddenRows.forEach(row => {
                    row.style.display = isExpanded ? 'none' : '';
                });
                
                if (isExpanded) {
                    this.innerHTML = '<i class="fas fa-chevron-down"></i> View All (<?php echo count($approved_requisitions); ?> records)';
                } else {
                    this.innerHTML = '<i class="fas fa-chevron-up"></i> Show Less';
                }
            });
        }

        loadAvailableSnapshots();
        
        // Update download links when month changes
        document.getElementById('month-selector').addEventListener('change', function() {
            // Reset custom dates when selecting from dropdown
            document.getElementById('start-date').value = '';
            document.getElementById('end-date').value = '';
            
            updateDownloadLinks();
        });

        // Update when office or status changes
        document.getElementById('dept-selector').addEventListener('change', function() {
            updateDownloadLinks();
        });


        // Apply custom date range
        document.getElementById('apply-range').addEventListener('click', function() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (!startDate || !endDate) {
                showModal('Please select both start and end dates.', 'warning');
                return;
            }
            
            // Set month selector to current (not snapshot) for custom ranges
            document.getElementById('month-selector').value = 'current';
            updateDownloadLinks();
        });

        // Reset filter
        document.getElementById('reset-range').addEventListener('click', function() {
            document.getElementById('start-date').value = '';
            document.getElementById('end-date').value = '';
            document.getElementById('month-selector').value = 'current';
            updateDownloadLinks();
        });

        // Save Snapshot
        document.getElementById('save-snapshot-btn').addEventListener('click', function() {
            const selectedMonth = document.getElementById('month-selector').value;
            const targetMonth = selectedMonth === 'current' ? new Date().toISOString().slice(0, 7) : selectedMonth;
            
            if (!confirm('Are you sure you want to save a snapshot for ' + targetMonth + '? Any existing snapshot for this month will be updated.')) {
                return;
            }
            
            showModal('Saving inventory state for ' + targetMonth + '...', 'info');
            
            fetch('<?php echo $urlRoot; ?>api/create_snapshot.php?month=' + targetMonth)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showModal('Snapshot saved successfully! Refreshing list...', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showModal('Error: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error saving snapshot:', error);
                    showModal('Request failed. Check console.', 'danger');
                });
        });

        // --- MULTI-SELECT REQUEST FILTER LOGIC ---
        const requestTrigger = document.getElementById('request-trigger');
        const requestDropdown = document.getElementById('request-dropdown');
        const requestSearch = document.getElementById('request-search');
        const requestIdsInput = document.getElementById('request-ids');
        const triggerText = document.getElementById('trigger-text');
        
        if (requestTrigger) {
            requestTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                requestDropdown.classList.toggle('active');
                if (requestDropdown.classList.contains('active')) {
                    requestSearch.focus();
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                requestDropdown.classList.remove('active');
            });

            requestDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Search logic
            requestSearch.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                const options = document.querySelectorAll('.multi-option');
                options.forEach(opt => {
                    const text = opt.textContent.toLowerCase();
                    opt.style.display = text.includes(term) ? 'flex' : 'none';
                });
            });
        }

        // Office / Date filter listener
        ['dept-selector', 'start-date', 'end-date'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', fetchFilteredRequests);
        });

        // Initial fetch
        fetchFilteredRequests();
    });

    function updateRequestTriggerText() {
        const selected = document.querySelectorAll('.multi-option input:checked');
        const triggerText = document.getElementById('trigger-text');
        const requestIdsInput = document.getElementById('request-ids');
        
        if (selected.length === 0) {
            triggerText.textContent = "All Approved Requests";
            requestIdsInput.value = "";
        } else if (selected.length === 1) {
            const label = selected[0].closest('.multi-option').textContent.trim();
            triggerText.textContent = label;
            requestIdsInput.value = selected[0].value;
        } else {
            triggerText.textContent = selected.length + " requests selected";
            const ids = Array.from(selected).map(i => i.value).join(',');
            requestIdsInput.value = ids;
        }
        updateDownloadLinks();
    }

    function fetchFilteredRequests() {
        const deptId = document.getElementById('dept-selector').value;
        const start = document.getElementById('start-date').value;
        const end = document.getElementById('end-date').value;
        
        const optionsContainer = document.getElementById('request-options');
        if (!optionsContainer) return;
        
        optionsContainer.innerHTML = '<div class="no-options"><i class="fas fa-spinner fa-spin"></i> Filtering...</div>';
        
        const params = new URLSearchParams({
            dept_id: deptId,
            start_date: start,
            end_date: end
        });

        fetch('<?php echo $urlRoot; ?>api/get_filtered_requests.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    optionsContainer.innerHTML = '';
                    if (data.requests.length === 0) {
                        optionsContainer.innerHTML = '<div class="no-options">No approved requests found</div>';
                    } else {
                        data.requests.forEach(req => {
                            const opt = document.createElement('div');
                            opt.className = 'multi-option';
                            opt.innerHTML = `
                                <input type="checkbox" value="${req.requisition_id}" id="req-${req.requisition_id}">
                                <label for="req-${req.requisition_id}">${req.requisition_no} - ${req.employee_name}</label>
                            `;
                            opt.addEventListener('click', function(e) {
                                if (e.target.tagName !== 'INPUT') {
                                    const checkbox = this.querySelector('input');
                                    checkbox.checked = !checkbox.checked;
                                }
                                this.classList.toggle('selected', this.querySelector('input').checked);
                                updateRequestTriggerText();
                            });
                            optionsContainer.appendChild(opt);
                        });
                    }
                } else {
                    optionsContainer.innerHTML = '<div class="no-options" style="color: red;">Error loading requests</div>';
                }
            })
            .catch(err => {
                console.error("Filter error:", err);
                if (optionsContainer) optionsContainer.innerHTML = '<div class="no-options" style="color: red;">Failed to fetch</div>';
            });
    }

    function loadAvailableSnapshots() {
        fetch('<?php echo $urlRoot; ?>api/get_snapshots.php')
            .then(response => response.json())
            .then(data => {
                const selector = document.getElementById('month-selector');
                const tbody = document.getElementById('snapshots-body');
                const info = document.getElementById('snapshot-info');
                
                if (data.success) {
                    // Update dropdown
                    selector.innerHTML = '<option value="current">Current Month (Live)</option>';
                    tbody.innerHTML = '';
                    
                    if (data.snapshots.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No snapshots found. Use "Debug Snapshot" to create one.</td></tr>';
                        info.textContent = '0 snapshot(s) available';
                        return;
                    }

                    info.textContent = data.snapshots.length + ' snapshot(s) available';

                    data.snapshots.forEach(snapshot => {
                        // Dropdown option
                        const option = document.createElement('option');
                        option.value = snapshot.snapshot_month;
                        const dateObj = new Date(snapshot.snapshot_month + '-01');
                        const monthName = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                        option.textContent = monthName + ' (Snapshot)';
                        selector.appendChild(option);

                        // Table row
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><strong>${monthName}</strong></td>
                            <td>${snapshot.created_at}</td>
                            <td>${snapshot.item_count} items</td>
                            <td>
                                <a href="view_snapshot.php?month=${snapshot.snapshot_month}" target="_blank" class="download-btn" style="background: var(--gradient-navy); padding: 5px 10px; font-size: 0.8rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="<?php echo $root; ?>api/export_supply_excel.php?month=${snapshot.snapshot_month}" class="download-btn" style="padding: 5px 10px; font-size: 0.8rem;">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading snapshots:', error);
                document.getElementById('snapshots-body').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Error loading snapshots.</td></tr>';
            });
    }

    function updateDownloadLinks() {
        const selectedMonth = document.getElementById('month-selector').value;
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const deptId = document.getElementById('dept-selector').value;
        const requestIds = document.getElementById('request-ids').value;
        const basePath = '<?php echo $root; ?>api/';
        
        let supplyUrl       = basePath + 'export_supply_excel.php';
        let rsmiUrl         = basePath + 'export_rsmi_excel.php';
        let risOfficeUrl    = basePath + 'export_ris_by_office.php';
        let wmrUrl          = basePath + 'export_wmr_excel.php';
        let ppeUrl          = basePath + 'export_ppe_report.php';
        let acquisitionUrl  = basePath + 'export_acquisition_excel.php';
        let rpciUrl         = basePath + 'export_rpci_excel.php';
        // New physical count report URLs
        let rcpiConsumableUrl = basePath + 'export_rcpi_consumable_excel.php';
        let rcpiSemiLvUrl     = basePath + 'export_rcpi_semi_lv_excel.php';
        let rpcspUrl          = basePath + 'export_rpcsp_excel.php';
        let rpcppeUrl         = basePath + 'export_rpcppe_excel.php';
        
        let commonParams = [];
        if (startDate && endDate) {
            commonParams.push(`start_date=${startDate}`);
            commonParams.push(`end_date=${endDate}`);
        } else if (selectedMonth !== 'current') {
            commonParams.push(`month=${selectedMonth}`);
        }

        // Append Request IDs if any
        if (requestIds) {
            rsmiUrl = basePath + 'export_rsmi_excel.php?id=' + requestIds;
        }

        // Append Dept ID
        if (deptId) {
            commonParams.push(`dept_id=${deptId}`);
        }

        if (commonParams.length > 0) {
            const paramStr = commonParams.join('&');
            
            if (requestIds) {
                rsmiUrl += '&' + paramStr;
            } else {
                rsmiUrl += '?' + paramStr;
            }
            
            const fullParamStr = '?' + paramStr;
            supplyUrl        += fullParamStr;
            risOfficeUrl     += fullParamStr;
            wmrUrl           += fullParamStr;
            ppeUrl           += fullParamStr;
            acquisitionUrl   += fullParamStr;
            rpciUrl          += fullParamStr;
            // Apply to new report URLs too
            rcpiConsumableUrl += fullParamStr;
            rcpiSemiLvUrl     += fullParamStr;
            rpcspUrl          += fullParamStr;
            rpcppeUrl         += fullParamStr;
        }

        const supplyBtn = document.getElementById('supply-download-btn');
        const rsmiBtn = document.getElementById('rsmi-download-btn');
        const risOfficeBtn = document.getElementById('ris-office-download-btn');
        const wmrBtn = document.getElementById('wmr-download-btn');
        const ppeBtn = document.getElementById('ppe-download-btn');
        const acquisitionBtn = document.getElementById('acquisition-download-btn');
        const rpciBtn = document.getElementById('rpci-download-btn');
        const rcpiConsumableBtn = document.getElementById('rcpi-consumable-btn');
        const rcpiSemiLvBtn = document.getElementById('rcpi-semi-lv-btn');
        const rpcspBtn = document.getElementById('rpcsp-btn');
        const rpcppeBtn = document.getElementById('rpcppe-btn');

        if (supplyBtn)        supplyBtn.href        = supplyUrl;
        if (rsmiBtn)          rsmiBtn.href          = rsmiUrl;
        if (risOfficeBtn)     risOfficeBtn.href     = risOfficeUrl;
        if (wmrBtn)           wmrBtn.href           = wmrUrl;
        if (ppeBtn)           ppeBtn.href           = ppeUrl;
        if (acquisitionBtn)   acquisitionBtn.href   = acquisitionUrl;
        if (rpciBtn)          rpciBtn.href          = rpciUrl;
        if (rcpiConsumableBtn) rcpiConsumableBtn.href = rcpiConsumableUrl;
        if (rcpiSemiLvBtn)    rcpiSemiLvBtn.href    = rcpiSemiLvUrl;
        if (rpcspBtn)         rpcspBtn.href         = rpcspUrl;
        if (rpcppeBtn)        rpcppeBtn.href        = rpcppeUrl;
        
        // Handle View Snapshot button visibility
        const viewBtn = document.getElementById('view-snapshot-btn');
        if (selectedMonth !== 'current') {
            viewBtn.style.display = 'inline-flex';
            viewBtn.onclick = function() {
                window.open('view_snapshot.php?month=' + selectedMonth, '_blank');
            };
        } else {
            viewBtn.style.display = 'none';
        }
        
        // Filter the requisitions table
        const tableContainer = document.getElementById('requisitions');
        const table = tableContainer ? tableContainer.querySelector('.requisition-table') : null;
        const rows = table ? table.querySelectorAll('tbody tr') : [];
        
        if (table && rows.length > 0) {
            rows.forEach(row => {
                // Get the date from the 5th column (index 4)
                const dateText = row.cells[4].innerText;
                const rowDate = new Date(dateText);
                
                // Set time to midnight for consistent comparisons
                rowDate.setHours(0, 0, 0, 0);
                
                if (startDate && endDate) {
                    const start = new Date(startDate);
                    const end = new Date(endDate);
                    start.setHours(0, 0, 0, 0);
                    end.setHours(0, 0, 0, 0);
                    
                    if (rowDate >= start && rowDate <= end) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                } else if (selectedMonth === 'current') {
                    row.style.display = '';
                } else {
                    const rowMonth = rowDate.getFullYear() + '-' + String(rowDate.getMonth() + 1).padStart(2, '0');
                    if (rowMonth === selectedMonth) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            // Show "No records found" message if all rows are hidden
            let visibleRows = 0;
            rows.forEach(row => { if(row.style.display !== 'none') visibleRows++; });
            
            let noRecordMsg = document.getElementById('no-filter-results');
            if (visibleRows === 0) {
                if (!noRecordMsg) {
                    noRecordMsg = document.createElement('p');
                    noRecordMsg.id = 'no-filter-results';
                    noRecordMsg.style.textAlign = 'center';
                    noRecordMsg.style.padding = '20px';
                    noRecordMsg.style.color = '#666';
                    noRecordMsg.innerHTML = '<i class="fas fa-info-circle"></i> No approved requisitions found for this month.';
                    document.querySelector('.table-section').appendChild(noRecordMsg);
                }
                table.style.display = 'none';
            } else {
                if (noRecordMsg) noRecordMsg.remove();
                table.style.display = '';
            }
        }
    }
    </script>
    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
