<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();
    
    require_once __DIR__ . '/../controller/requestController.php';
    require_once __DIR__ . '/../model/departmentModel.php';
    
    $controller = new RequestController();
    $deptModel = new DepartmentModel();
    $departments = $deptModel->getAllDepartments();
    $data = $controller->handleRequest();
    
    $requisitions = $data['requisitions'] ?? [];
    $stats = $data['stats'] ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
    $pendingCount = $stats['pending'] ?? 0;
    
} catch (Throwable $e) {
    die("<h1>Error Loading Page</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p><p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
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
    <title>Inventory System - Request Management</title>
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            overflow-y: auto; /* Enable outer scroll */
            padding-bottom: 50px;
        }

        .modal-content {
            background: white;
            margin: 2% auto; /* Move closer to top */
            width: 90%; /* Responsive width */
            max-width: 950px; /* Larger for better visibility */
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: visible; /* Prevent inner cutting */
            color: var(--text-main);
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: var(--gradient-navy);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-body {
            padding: 25px;
            background-color: white;
            border-radius: 0 0 12px 12px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }

        .items-table th, .items-table td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #edf2f7;
        }

        .items-table th {
            background-color: #f7fafc;
            color: #2d3748;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .items-table td {
            color: #4a5568;
            font-size: 0.95rem;
        }

        .btn-view-items:hover { opacity: 0.8; }
        .btn-action-req:hover { opacity: 0.8; }

        /* Item Name Link Style */
        .item-link {
            color: var(--primary-emerald);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        .item-link:hover {
            text-decoration: underline;
            color: var(--secondary-emerald);
        }

        /* Supply Detail Modal Styles (from supply.php) */
        .supply-modal-content {
            max-width: 500px;
            padding: 24px;
        }
        #modal-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            margin: 20px auto;
            display: block;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 3px solid #f7fafc;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin: 20px 0;
        }
        .details-grid p { margin: 0; font-size: 0.95rem; color: #4a5568; }
        .details-grid p strong { color: #2d3748; min-width: 130px; display: inline-block; }
        .header h1 {
            color: var(--navy-900) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin: 0;
            font-weight: 800;
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
            <h1 style="color: var(--navy-900); font-weight: 800;"><i class="fas fa-file-invoice" style="color: var(--primary-emerald); margin-right: 12px;"></i> Request Management</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>
        <div class="stats">
            <div class="stat-card" style="position: relative; overflow: hidden;">
                <i class="fas fa-clock" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: var(--warning);"></i>
                <h3 style="color: var(--slate-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; font-weight: 700;">Pending Requests</h3>
                <p style="color: var(--warning); font-size: 2.2rem; font-weight: 800; margin: 0; line-height: 1;"><?php echo $stats['pending'] ?? 0; ?></p>
            </div>
            <div class="stat-card" style="position: relative; overflow: hidden; border-top: 4px solid var(--success);">
                <i class="fas fa-check-circle" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: var(--success);"></i>
                <h3 style="color: var(--slate-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; font-weight: 700;">Approved Requests</h3>
                <p style="color: var(--success); font-size: 2.2rem; font-weight: 800; margin: 0; line-height: 1;"><?php echo $stats['approved'] ?? 0; ?></p>
            </div>
            <div class="stat-card" style="position: relative; overflow: hidden; border-top: 4px solid var(--danger);">
                <i class="fas fa-times-circle" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: var(--danger);"></i>
                <h3 style="color: var(--slate-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; font-weight: 700;">Rejected Requests</h3>
                <p style="color: var(--danger); font-size: 2.2rem; font-weight: 800; margin: 0; line-height: 1;"><?php echo $stats['rejected'] ?? 0; ?></p>
            </div>
            <div class="stat-card" style="position: relative; overflow: hidden; border-top: 4px solid var(--primary-emerald);">
                <i class="fas fa-file-invoice" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: var(--primary-emerald);"></i>
                <h3 style="color: var(--slate-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; font-weight: 700;">Total Requests</h3>
                <p style="color: var(--navy-900); font-size: 2.2rem; font-weight: 800; margin: 0; line-height: 1;"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
        </div>

        <?php
        $activeRequisitions = array_filter($requisitions, function($req) {
            return in_array($req['status'], ['Pending', 'Processing']);
        });
        $approvedRequests = array_filter($requisitions, function($req) {
            return $req['status'] === 'Approved';
        });
        $rejectedRequests = array_filter($requisitions, function($req) {
            return $req['status'] === 'Rejected';
        });
        ?>

        <!-- Summary Actions -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 25px;">
            <a href="<?php echo $urlRoot; ?>api/export_rsmi_excel.php" id="btnExportRSMI" class="btn-primary" title="Download Summary RSMI" style="padding: 10px 24px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px; height: 45px; background: #217346;">
                <i class="fas fa-file-excel"></i> RSMI Summary Report
            </a>
            <a href="<?php echo $urlRoot; ?>api/export_ris_by_office.php" id="btnExportRISOffice" class="btn-primary" title="Download RIS Categorized by Office" style="padding: 10px 24px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px; height: 45px; background: var(--gradient-navy);">
                <i class="fas fa-building"></i> RIS by Office Report
            </a>
        </div>

        <!-- Filter Section -->
        <div class="search-filter-container" style="background: rgba(255, 255, 255, 0.5); padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); backdrop-filter: blur(5px); margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="start_date" style="color: var(--slate-600); font-weight: 600;">From:</label>
                <input type="date" id="start_date" style="border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); padding: 8px 12px;">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="end_date" style="color: var(--slate-600); font-weight: 600;">To:</label>
                <input type="date" id="end_date" style="border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); padding: 8px 12px;">
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="dept_filter" style="color: var(--slate-600); font-weight: 600;">Office:</label>
                <select id="dept_filter" style="border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); padding: 8px 12px;">
                    <option value="">All Offices</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button id="btnApplyFilter" class="btn-primary" style="padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <button id="btnResetFilter" class="cancel-btn" style="padding: 10px 15px; border-radius: 8px; font-weight: 600;">
                    Reset
                </button>
            </div>
        </div>

        <div class="table-section" style="background: white; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--navy-900); display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-clock" style="color: var(--primary-emerald);"></i> Active Requisitions
                </h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span class="badge" style="background: var(--bg-emerald-light); color: var(--primary-emerald); padding: 6px 16px; border-radius: 20px; font-weight: 700;">
                        <?php echo count($activeRequisitions); ?> Pending/Processing
                    </span>
                </div>
            </div>
            <div class="table-container" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <table class="standard-table request-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Requisition No</th>
                        <th>Employee Name</th>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Quantity</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activeRequisitions)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px; color: #999;">No active requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeRequisitions as $req): ?>
                                <tr>
                                    <td><?php echo $req['requisition_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($req['requisition_no']); ?></strong></td>
                                    <td data-dept-id="<?php echo $req['department_id']; ?>"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                    <td title="<?php echo htmlspecialchars($req['item_list'] ?? ''); ?>">
                                        <a href="javascript:void(0)" class="item-list-link" 
                                           data-id="<?php echo $req['requisition_id']; ?>"
                                           style="color: inherit; text-decoration: none;">
                                            <?php echo htmlspecialchars(strlen($req['item_list']??'') > 25 ? substr($req['item_list'], 0, 25) . '...' : ($req['item_list']??'N/A')); ?>
                                        </a>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($req['description_list'] ?? ''); ?>">
                                        <?php echo htmlspecialchars(strlen($req['description_list']??'') > 25 ? substr($req['description_list'], 0, 25) . '...' : ($req['description_list']??'N/A')); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($req['quantity_list'] ?? ''); ?>">
                                        <?php echo htmlspecialchars(strlen($req['quantity_list']??'') > 25 ? substr($req['quantity_list'], 0, 25) . '...' : ($req['quantity_list']??'N/A')); ?>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo strtolower($req['status']); ?>">
                                            <?php echo htmlspecialchars($req['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($req['request_date'])); ?></td>
                                    <td>
                                        <button class="btn-view-items" 
                                            data-id="<?php echo $req['requisition_id']; ?>" 
                                            data-no="<?php echo $req['requisition_no']; ?>" 
                                            data-requester="<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>"
                                            data-emp-id="<?php echo htmlspecialchars($req['employee_id']); ?>"
                                            data-dept="<?php echo htmlspecialchars($req['department_name']); ?>"
                                            data-purpose="<?php echo htmlspecialchars($req['purpose'] ?? 'N/A'); ?>"
                                            data-date="<?php echo date('Y-m-d', strtotime($req['request_date'])); ?>"
                                            data-status="<?php echo $req['status']; ?>"
                                            data-semi="<?php echo $req['semi_expendable_count'] ?? 0; ?>"
                                            title="View Items" style="background: var(--gradient-primary); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-open-approval" 
                                            data-id="<?php echo $req['requisition_id']; ?>" 
                                            data-no="<?php echo $req['requisition_no']; ?>" 
                                            data-requester="<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>"
                                            data-emp-id="<?php echo htmlspecialchars($req['employee_id']); ?>"
                                            data-dept="<?php echo htmlspecialchars($req['department_name']); ?>"
                                            data-purpose="<?php echo htmlspecialchars($req['purpose'] ?? 'N/A'); ?>"
                                            data-date="<?php echo date('Y-m-d', strtotime($req['request_date'])); ?>"
                                            title="View Details & Approve" 
                                            style="background: #27ae60; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; margin-left: 5px; font-weight: bold;">
                                            <i class="fas fa-check-circle"></i> Approve
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-section" style="background: white; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h2 style="margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--navy-900); display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-history" style="color: var(--primary-emerald);"></i> Requisition History (Approved)
                </h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button id="btnViewRejected" class="btn-primary" style="background: var(--danger); border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: 0.85rem;">
                        <i class="fas fa-ban"></i> Rejected (<?php echo count($rejectedRequests); ?>)
                    </button>
                    <span class="badge" style="background: var(--slate-100); color: var(--slate-600); padding: 6px 16px; border-radius: 20px; font-weight: 700;">
                        Completed Records
                    </span>
                </div>
            </div>
            <div class="table-container" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <table class="standard-table request-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Requisition No</th>
                        <th>Employee Name</th>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Status</th>
                            <th>Request Date</th>
                            <th style="text-align: center;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approvedRequests)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #999;">No approved requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvedRequests as $index => $req): ?>
                                <tr<?php echo $index >= 5 ? ' class="history-row-hidden" style="display: none;"' : ''; ?>>
                                    <td><?php echo $req['requisition_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($req['requisition_no']); ?></strong></td>
                                    <td data-dept-id="<?php echo $req['department_id']; ?>"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                    <td title="<?php echo htmlspecialchars($req['item_list'] ?? 'N/A'); ?>">
                                        <?php echo htmlspecialchars(strlen($req['item_list']??'') > 25 ? substr($req['item_list'], 0, 25) . '...' : ($req['item_list']??'N/A')); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($req['description_list'] ?? 'N/A'); ?>">
                                        <?php echo htmlspecialchars(strlen($req['description_list']??'') > 25 ? substr($req['description_list'], 0, 25) . '...' : ($req['description_list']??'N/A')); ?>
                                    </td>
                                    <td>
                                        <span class="status-<?php echo strtolower($req['status']); ?>">
                                            <?php echo htmlspecialchars($req['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($req['request_date'])); ?></td>
                                    <td style="text-align: center;">
                                        <button class="btn-view-items" 
                                            data-id="<?php echo $req['requisition_id']; ?>" 
                                            data-no="<?php echo $req['requisition_no']; ?>" 
                                            data-requester="<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>"
                                            data-emp-id="<?php echo htmlspecialchars($req['employee_id']); ?>"
                                            data-dept="<?php echo htmlspecialchars($req['department_name']); ?>"
                                            data-purpose="<?php echo htmlspecialchars($req['purpose'] ?? 'N/A'); ?>"
                                            data-date="<?php echo date('Y-m-d', strtotime($req['request_date'])); ?>"
                                            data-status="<?php echo $req['status']; ?>"
                                            data-semi="<?php echo $req['semi_expendable_count'] ?? 0; ?>"
                                            title="View Items" style="background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <a href="<?php echo $urlRoot; ?>api/export_ris_excel.php?id=<?php echo $req['requisition_id']; ?>" class="btn-export-excel" title="Download Excel (RIS)" style="background: #1d6f42; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; margin-left: 5px; font-size: 0.85rem; display: inline-block;">
                                            <i class="fas fa-file-excel"></i> Download RIS
                                        </a>
                                        <?php if (($req['semi_expendable_count'] ?? 0) > 0): ?>
                                        <a href="<?php echo $urlRoot; ?>api/export_ics_excel.php?id=<?php echo $req['requisition_id']; ?>" class="btn-export-excel" title="Download Borrowing Form (ICS)" style="background: #a87e00; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; margin-left: 5px; font-size: 0.85rem; display: inline-block;">
                                            <i class="fas fa-address-card"></i> Download ICS
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (count($approvedRequests) > 5): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <button id="btnToggleHistory" class="btn-primary" style="padding: 10px 20px; font-size: 0.9rem; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);">
                        <i class="fas fa-chevron-down"></i> View All (<?php echo count($approvedRequests); ?> records)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Items Modal (Read Only) -->
    <div id="itemsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--gradient-primary); padding: 25px 35px; border-radius: 12px 12px 0 0;">
                <h2 style="color: white; margin:0; font-weight: 800; font-size: 1.5rem;"><i class="fas fa-list"></i> Requisition Items (<span id="modalRequisitionNo"></span>)</h2>
                <span class="close-modal" style="color: white; opacity: 0.8;">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Requisition Details Header -->
                <div id="viewDetailsHeader" style="background: #f8f9fa; border: 1px solid #e1e4e8; border-radius: 8px; padding: 15px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 0.9rem;">
                    <div>
                        <span style="color: #666;">Requester:</span> <strong id="viewRequester"></strong> (<span id="viewEmpId" style="color: var(--primary-emerald); font-weight: 700;"></span>)
                    </div>
                    <div>
                        <span style="color: #666;">Department:</span> <strong id="viewDept"></strong>
                    </div>
                    <div>
                        <span style="color: #666;">Date:</span> <strong id="viewDate"></strong>
                    </div>
                    <div style="grid-column: span 2;">
                        <span style="color: #666;">Purpose:</span> <p id="viewPurpose" style="margin: 5px 0 0 0; display: inline;"></p>
                    </div>
                </div>

                <div id="modalLoading" style="text-align: center; padding: 20px; display: none;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading items...</p>
                </div>
                <table class="items-table" id="modalItemsTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Issued</th>
                            <th>Unit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsBody">
                        <!-- Items will be loaded here -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer" id="itemsModalFooter" style="padding: 20px 25px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 12px; background: #f8faff; border-radius: 0 0 12px 12px;">
                <!-- Requisition-specific download buttons will be injected here via JS -->
                 <span id="exportLabel" style="margin-right: auto; align-self: center; color: #64748b; font-weight: 600; font-size: 0.9rem;">
                    <i class="fas fa-file-export"></i> EXPORT REQUISITION:
                 </span>
            </div>
        </div>
    </div>

    <!-- Rejected Records Modal -->
    <div id="rejectedModal" class="modal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header" style="background: var(--danger); padding: 25px 35px; border-radius: 12px 12px 0 0;">
                <h2 style="color: white; margin:0; font-weight: 800; font-size: 1.5rem;"><i class="fas fa-ban"></i> Rejected Requisitions</h2>
                <span class="close-rejected-modal" style="color: white; opacity: 0.8; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            <div class="modal-body">
                <div class="table-container">
                    <table class="request-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th>ID</th>
                                <th>Requisition No</th>
                                <th>Employee</th>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Request Date</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rejectedRequests)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: #999;">No rejected requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rejectedRequests as $req): ?>
                                    <tr>
                                        <td><?php echo $req['requisition_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($req['requisition_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                        <td title="<?php echo htmlspecialchars($req['item_list'] ?? 'N/A'); ?>">
                                            <a href="javascript:void(0)" class="item-list-link" 
                                               data-id="<?php echo $req['requisition_id']; ?>"
                                               data-no="<?php echo $req['requisition_no']; ?>"
                                               data-requester="<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>"
                                            data-emp-id="<?php echo htmlspecialchars($req['employee_id']); ?>"
                                               data-dept="<?php echo htmlspecialchars($req['department_name']); ?>"
                                               data-purpose="Rejected Request"
                                               data-date="<?php echo date('Y-m-d', strtotime($req['created_at'])); ?>"
                                               style="color: inherit; text-decoration: none;">
                                                <?php echo htmlspecialchars(strlen($req['item_list']??'') > 25 ? substr($req['item_list'], 0, 25) . '...' : ($req['item_list']??'N/A')); ?>
                                            </a>
                                        </td>
                                        <td title="<?php echo htmlspecialchars($req['description_list'] ?? 'N/A'); ?>">
                                            <?php echo htmlspecialchars(strlen($req['description_list']??'') > 25 ? substr($req['description_list'], 0, 25) . '...' : ($req['description_list']??'N/A')); ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($req['request_date'])); ?></td>
                                        <td style="text-align: center;">
                                            <button class="btn-view-items" 
                                                data-id="<?php echo $req['requisition_id']; ?>" 
                                                data-no="<?php echo $req['requisition_no']; ?>" 
                                                data-requester="<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>"
                                            data-emp-id="<?php echo htmlspecialchars($req['employee_id']); ?>"
                                                data-dept="<?php echo htmlspecialchars($req['department_name']); ?>"
                                                data-purpose="<?php echo htmlspecialchars($req['purpose'] ?? 'N/A'); ?>"
                                                data-date="<?php echo date('Y-m-d', strtotime($req['request_date'])); ?>"
                                                title="View Items" style="background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Comprehensive Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content" style="width: 950px; margin: 2% auto;">
            <div class="modal-header" style="background: var(--gradient-primary); padding: 25px 35px; border-radius: 12px 12px 0 0;">
                <h2 style="color: white; margin:0; font-weight: 800; font-size: 1.5rem;"><i class="fas fa-clipboard-check"></i> Requisition Approval & Issuance</h2>
                <span class="close-approval-modal" style="color: white; opacity: 0.8; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Request Header Details -->
                <div class="request-details-card" style="background: #f8f9fa; border: 1px solid #e1e4e8; border-radius: 8px; padding: 20px; margin-bottom: 25px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <div>
                        <label style="display: block; color: #666; font-size: 0.8rem; margin-bottom: 4px;">Requisition No</label>
                        <strong id="appReqNo" style="color: var(--primary-emerald); font-size: 1.1rem;"></strong>
                    </div>
                    <div>
                        <label style="display: block; color: #666; font-size: 0.8rem; margin-bottom: 4px;">Requester</label>
                        <strong id="appRequester"></strong> (<span id="appEmpId" style="color: var(--primary-emerald);"></span>)
                    </div>
                    <div>
                        <label style="display: block; color: #666; font-size: 0.8rem; margin-bottom: 4px;">Department</label>
                        <strong id="appDept"></strong>
                    </div>
                    <div style="grid-column: span 2;">
                        <label style="display: block; color: #666; font-size: 0.8rem; margin-bottom: 4px;">Purpose</label>
                        <p id="appPurpose" style="margin: 0; font-size: 0.95rem; line-height: 1.4;"></p>
                    </div>
                    <div>
                        <label style="display: block; color: #666; font-size: 0.8rem; margin-bottom: 4px;">Request Date</label>
                        <strong id="appDate"></strong>
                    </div>
                </div>

                <form id="approvalForm">
                    <input type="hidden" id="appRequisitionId">
                    <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: var(--primary-emerald);"><i class="fas fa-boxes"></i> Requested Items & Stock Availability</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th style="text-align: center;">Requested</th>
                                <th style="text-align: center;">In Stock</th>
                                <th style="text-align: center; width: 120px;">Issue Qty</th>
                                <th>Remarks / Comments</th>
                            </tr>
                        </thead>
                        <tbody id="approvalItemsBody">
                            <!-- Items will be loaded here -->
                        </tbody>
                    </table>

                    <div style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 20px;">
                        <button type="button" id="btnRejectFull" style="padding: 12px 25px; border-radius: 6px; border: 2px solid #e74c3c; background: #fff; color: #e74c3c; cursor: pointer; font-weight: bold; transition: all 0.2s;">
                            <i class="fas fa-times-circle"></i> Reject Entire Request
                        </button>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="close-approval-modal" style="padding: 12px 25px; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer;">Cancel</button>
                            <button type="submit" id="btnApproveFinal" style="padding: 12px 35px; border-radius: 6px; border: none; background: #27ae60; color: white; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2);">
                                <i class="fas fa-check-double"></i> Complete Approval & Issue
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Supply Item Detail Modal (Read Only) -->
    <div id="itemDetailModal" class="modal">
        <div class="modal-content supply-modal-content">
            <span class="close-item-detail" style="position: absolute; right: 20px; top: 15px; font-size: 28px; cursor: pointer;">&times;</span>
            <h2 id="modal-item-title" style="background: var(--gradient-navy); color: white; margin: -24px -24px 20px -24px; padding: 25px 35px; text-align: left; border-radius: 12px 12px 0 0; font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px;">Item Details</h2>
            <img id="modal-item-img" src="" alt="Item Image">
            
            <div class="details-grid">
                <p><strong>Stock Number:</strong> <span id="modal-item-stock-no"></span></p>
                <p><strong>Item Name:</strong> <span id="modal-item-name"></span></p>
                <p><strong>Category:</strong> <span id="modal-item-category"></span></p>
                <p><strong>Unit:</strong> <span id="modal-item-unit"></span></p>
                <p><strong>Current Stock:</strong> <span id="modal-item-stock"></span></p>
                <p><strong>Unit Cost:</strong> ₱<span id="modal-item-cost"></span></p>
                <p><strong>Status:</strong> <span id="modal-item-status"></span></p>
                <p><strong>Classification:</strong> <span id="modal-item-class"></span></p>
                <p><strong>Description:</strong> <span id="modal-item-description"></span></p>
            </div>
        </div>
    </div>

    <script src="<?php echo $root; ?>js/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal Elements
            const itemsModal = document.getElementById('itemsModal');
            const approvalModal = document.getElementById('approvalModal');
            const rejectedModal = document.getElementById('rejectedModal');
            const itemDetailModal = document.getElementById('itemDetailModal');
            
            // Global storage for current loaded items to show details
            let currentItems = [];

            function showItemDetails(supplyId) {
                const item = currentItems.find(i => (i.supply_id == supplyId || i.request_item_id == supplyId));
                if (!item) return;

                document.getElementById('modal-item-title').textContent = item.item_name + ' Details';
                document.getElementById('modal-item-img').src = item.image_base64 || '../img/Bogo_City_logo.png';
                document.getElementById('modal-item-stock-no').textContent = item.stock_no || 'N/A';
                document.getElementById('modal-item-name').textContent = item.item_name;
                document.getElementById('modal-item-category').textContent = item.category || 'N/A';
                document.getElementById('modal-item-unit').textContent = item.unit || 'N/A';
                document.getElementById('modal-item-stock').textContent = item.current_stock || '0';
                document.getElementById('modal-item-cost').textContent = item.unit_cost ? parseFloat(item.unit_cost).toFixed(2) : '0.00';
                document.getElementById('modal-item-status').textContent = item.item_status || 'N/A';
                document.getElementById('modal-item-class').textContent = item.property_classification || 'N/A';
                document.getElementById('modal-item-description').textContent = item.description || 'No description';

                itemDetailModal.style.display = 'block';
                itemDetailModal.style.zIndex = '2000'; // Make sure it's above other modals
            }

            // Reusable logic for closing modals
            document.querySelectorAll('.close-modal, .close-approval-modal, .close-rejected-modal, .close-item-detail').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.classList.contains('close-item-detail')) {
                        itemDetailModal.style.display = 'none';
                    } else {
                        itemsModal.style.display = 'none';
                        approvalModal.style.display = 'none';
                        rejectedModal.style.display = 'none';
                    }
                });
            });

            // Open Rejected Modal
            document.getElementById('btnViewRejected')?.addEventListener('click', () => {
                rejectedModal.style.display = 'block';
            });

            // Filter Logic
            const btnApplyFilter = document.getElementById('btnApplyFilter');
            const btnResetFilter = document.getElementById('btnResetFilter');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const deptFilterSelect = document.getElementById('dept_filter');
            const btnExportRSMI = document.getElementById('btnExportRSMI');
            const btnExportRISOffice = document.getElementById('btnExportRISOffice');

            function applyFilters() {
                const start = startDateInput.value;
                const end = endDateInput.value;
                const deptId = deptFilterSelect.value;
                const apiPath = basePath + 'api/';

                // Update Download Links
                let params = [];
                if (start && end) {
                    params.push(`start_date=${start}`);
                    params.push(`end_date=${end}`);
                }
                
                let rsmiUrl = apiPath + 'export_rsmi_excel.php';
                let risUrl = apiPath + 'export_ris_by_office.php';
                
                if (params.length > 0) {
                    rsmiUrl += '?' + params.join('&');
                    risUrl += '?' + params.join('&');
                }

                if (deptId) {
                    risUrl += (risUrl.includes('?') ? '&' : '?') + `dept_id=${deptId}`;
                }

                btnExportRSMI.href = rsmiUrl;
                btnExportRISOffice.href = risUrl;

                // Filter Tables
                filterTable('.request-table', start, end, deptId);
            }

            function filterTable(tableSelector, start, end, deptId) {
                const tables = document.querySelectorAll(tableSelector);
                tables.forEach(table => {
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const deptCell = row.cells[2];
                        if (!deptCell) return;

                        const rowDeptId = deptCell.getAttribute('data-dept-id');
                        const rowDateStr = row.cells[7].innerText; // Request Date column
                        if (!rowDateStr || rowDateStr === 'Action' || rowDateStr === 'Request Date') return;
                        
                        const rowDate = new Date(rowDateStr);
                        rowDate.setHours(0,0,0,0);

                        let show = true;

                        // Date Filter
                        if (start && end) {
                            const startDate = new Date(start);
                            const endDate = new Date(end);
                            startDate.setHours(0,0,0,0);
                            endDate.setHours(0,0,0,0);
                            if (rowDate < startDate || rowDate > endDate) {
                                show = false;
                            }
                        }

                        // Dept Filter
                        if (deptId && rowDeptId !== deptId) {
                            show = false;
                        }

                        if (row.cells.length > 1) { // Don't hide "No items found" rows if visible
                            row.style.display = show ? '' : 'none';
                        }
                    });
                });
            }

            btnApplyFilter.addEventListener('click', applyFilters);
            btnResetFilter.addEventListener('click', () => {
                startDateInput.value = '';
                endDateInput.value = '';
                deptFilterSelect.value = '';
                applyFilters();
            });

            // View Items Modal (Read Only)
            document.querySelectorAll('.btn-view-items').forEach(btn => {
                btn.addEventListener('click', function() {
                    const reqId = this.getAttribute('data-id');
                    const reqNo = this.getAttribute('data-no');
                    const requester = this.getAttribute('data-requester');
                    const empId = this.getAttribute('data-emp-id');
                    const dept = this.getAttribute('data-dept');
                    const purpose = this.getAttribute('data-purpose');
                    const date = this.getAttribute('data-date');
                    const status = this.getAttribute('data-status');
                    const semiCount = parseInt(this.getAttribute('data-semi') || 0);

                    document.getElementById('modalRequisitionNo').textContent = reqNo;
                    document.getElementById('viewRequester').textContent = requester;
                    document.getElementById('viewEmpId').textContent = empId;
                    document.getElementById('viewDept').textContent = dept;
                    document.getElementById('viewPurpose').textContent = purpose;
                    document.getElementById('viewDate').textContent = date;

                    // Update modal footer with download buttons
                    const footer = document.getElementById('itemsModalFooter');
                    const exportLabel = document.getElementById('exportLabel');
                    
                    // Clear previous buttons but keep label
                    footer.innerHTML = '';
                    footer.appendChild(exportLabel);

                    if (status === 'Approved') {
                        exportLabel.style.display = 'block';
                        // Add RIS button
                        const risBtn = document.createElement('a');
                        risBtn.href = `${basePath}api/export_ris_excel.php?id=${reqId}`;
                        risBtn.className = 'btn-primary';
                        risBtn.style.cssText = 'background: #1d6f42; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem;';
                        risBtn.innerHTML = '<i class="fas fa-file-excel"></i> Download RIS';
                        footer.appendChild(risBtn);

                        // Add ICS button if semi-expendable items exist
                        if (semiCount > 0) {
                            const icsBtn = document.createElement('a');
                            icsBtn.href = `${basePath}api/export_ics_excel.php?id=${reqId}`;
                            icsBtn.className = 'btn-primary';
                            icsBtn.style.cssText = 'background: #a87e00; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem;';
                            icsBtn.innerHTML = '<i class="fas fa-address-card"></i> Download ICS';
                            footer.appendChild(icsBtn);
                        }
                    } else {
                        exportLabel.style.display = 'none';
                    }

                    const body = document.getElementById('modalItemsBody');
                    const loading = document.getElementById('modalLoading');
                    const table = document.getElementById('modalItemsTable');
                    
                    body.innerHTML = '';
                    loading.style.display = 'block';
                    table.style.display = 'none';
                    itemsModal.style.display = 'block';

                    fetch(`${basePath}api/get_requisition_items_with_stock.php?id=${reqId}`)
                        .then(r => r.json())
                        .then(data => {
                            loading.style.display = 'none';
                            table.style.display = 'table';
                            if (data.success && data.items.length > 0) {
                                currentItems = data.items; // Store for details view
                                data.items.forEach(item => {
                                    const tr = document.createElement('tr');
                                    const displayDesc = item.description || 'N/A';
                                    tr.innerHTML = `
                                        <td><a class="item-link" data-id="${item.supply_id}">${item.item_name}</a></td>
                                        <td>${displayDesc}</td>
                                        <td>${item.quantity}</td>
                                        <td><strong>${item.issued_quantity || 0}</strong></td>
                                        <td>${item.unit}</td>
                                        <td><span class="status-${item.status.toLowerCase()}">${item.status}</span></td>
                                    `;
                                    body.appendChild(tr);
                                });
                                // Add click listeners for item links
                                body.querySelectorAll('.item-link').forEach(link => {
                                    link.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        showItemDetails(this.getAttribute('data-id'));
                                    });
                                });
                            } else {
                                body.innerHTML = '<tr><td colspan="5" style="text-align: center;">No items found.</td></tr>';
                            }
                        });
                });
            });

            // Approval Modal Logic
            document.querySelectorAll('.btn-open-approval').forEach(btn => {
                btn.addEventListener('click', function() {
                    const reqId = this.getAttribute('data-id');
                    const reqNo = this.getAttribute('data-no');
                    const requester = this.getAttribute('data-requester');
                    const empId = this.getAttribute('data-emp-id');
                    const dept = this.getAttribute('data-dept');
                    const purpose = this.getAttribute('data-purpose');
                    const date = this.getAttribute('data-date');

                    // Set Header Info
                    document.getElementById('appReqNo').textContent = reqNo;
                    document.getElementById('appRequester').textContent = requester;
                    document.getElementById('appEmpId').textContent = empId;
                    document.getElementById('appDept').textContent = dept;
                    document.getElementById('appPurpose').textContent = purpose;
                    document.getElementById('appDate').textContent = date;
                    document.getElementById('appRequisitionId').value = reqId;

                    const body = document.getElementById('approvalItemsBody');
                    body.innerHTML = '<tr><td colspan="5" style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Loading stock details...</td></tr>';
                    approvalModal.style.display = 'block';

                    fetch(`${basePath}api/get_requisition_items_with_stock.php?id=${reqId}`)
                        .then(r => r.json())
                        .then(data => {
                            body.innerHTML = '';
                            if (data.success && data.items.length > 0) {
                                currentItems = data.items; // Store for details view
                                    data.items.forEach(item => {
                                        const tr = document.createElement('tr');
                                        const defaultIssued = Math.min(item.quantity, item.current_stock);
                                        const stockColor = item.current_stock <= 0 ? '#e74c3c' : (item.current_stock < item.quantity ? '#f39c12' : '#27ae60');
                                        
                                        const lowThreshold = parseInt(item.low_stock_threshold || 10);
                                        const criticalThreshold = parseInt(item.critical_stock_threshold || 5);

                                        const displayDesc = item.description || 'N/A';
                                        tr.innerHTML = `
                                            <td>
                                                <a class="item-link" data-id="${item.supply_id}">${item.item_name}</a><br>
                                                <small style="color: #666;">Unit: ${item.unit}</small>
                                            </td>
                                            <td>${displayDesc}</td>
                                            <td style="text-align: center; font-size: 1.1rem;">${item.quantity}</td>
                                            <td style="text-align: center;">
                                                <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; background: ${stockColor}15; color: ${stockColor}; font-weight: bold; border: 1px solid ${stockColor}30;">
                                                    ${item.current_stock}
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <input type="number" value="${defaultIssued}" min="0" max="${item.current_stock}" 
                                                 class="issue-qty-input" 
                                                 data-id="${item.request_item_id}" 
                                                 data-name="${item.item_name}"
                                                 data-requested="${item.quantity}" 
                                                 data-stock="${item.current_stock}"
                                                 data-low="${lowThreshold}"
                                                 data-critical="${criticalThreshold}"
                                                 style="width: 80px; padding: 8px; border: 1px solid #ccc; border-radius: 6px; text-align: center; font-weight: bold;">
                                                <div class="stock-alert-msg" style="font-size: 0.75rem; font-weight: bold; margin-top: 4px; display: none;"></div>
                                            </td>
                                            <td>
                                                <textarea class="issue-remarks-input" placeholder="Add optional remarks for this item..." 
                                                 style="width: 100%; padding: 8px; border: 1px solid #eee; border-radius: 6px; height: 35px; resize: none;"></textarea>
                                            </td>
                                        `;
                                        body.appendChild(tr);
                                       });
                                
                                // Real-time Stock Alert Logic
                                body.querySelectorAll('.issue-qty-input').forEach(input => {
                                    let prevState = 'normal'; // Track state to avoid sound spam

                                    const validateStock = () => {
                                        const qty = parseInt(input.value) || 0;
                                        const stock = parseInt(input.getAttribute('data-stock'));
                                        const requested = parseInt(input.getAttribute('data-requested'));
                                        const low = parseInt(input.getAttribute('data-low'));
                                        const critical = parseInt(input.getAttribute('data-critical'));
                                        const msgDiv = input.parentElement.querySelector('.stock-alert-msg');
                                        const remaining = stock - qty;

                                        let newState = 'normal';
                                        input.style.borderColor = '#ccc'; // Reset

                                        if (qty > requested) {
                                            newState = 'invalid';
                                            msgDiv.textContent = `❌ Exceeds requested: ${requested}`;
                                            msgDiv.style.color = '#e74c3c';
                                            msgDiv.style.display = 'block';
                                            input.style.borderColor = '#e74c3c';
                                        } else if (remaining <= critical && remaining >= 0) {
                                            newState = 'critical';
                                            msgDiv.textContent = `⚠️ Critical: ${remaining} left!`;
                                            msgDiv.style.color = '#e74c3c';
                                            msgDiv.style.display = 'block';
                                            
                                            // Trigger sound only if entering critical state
                                            if (prevState !== 'critical' && typeof playNotificationSound === 'function') {
                                                playNotificationSound();
                                            }
                                        } else if (remaining <= low && remaining >= 0) {
                                            newState = 'low';
                                            msgDiv.textContent = `⚠️ Low Stock: ${remaining} left!`;
                                            msgDiv.style.color = '#f39c12';
                                            msgDiv.style.display = 'block';
                                        } else {
                                            msgDiv.style.display = 'none';
                                        }
                                        prevState = newState;
                                    };

                                    input.addEventListener('input', validateStock);
                                    // Run once for initial values
                                    validateStock();
                                });

                                // Add click listeners for item links
                                body.querySelectorAll('.item-link').forEach(link => {
                                    link.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        showItemDetails(this.getAttribute('data-id'));
                                    });
                                });
                            } else {
                                body.innerHTML = '<tr><td colspan="5" style="text-align: center;">No items found.</td></tr>';
                            }
                        });
                });
            });

            // Approval Form Submission (Approve & Issue)
            document.getElementById('approvalForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const reqId = document.getElementById('appRequisitionId').value;
                const items = [];
                let hasError = false;

                document.querySelectorAll('.issue-qty-input').forEach(input => {
                    const qty = parseInt(input.value);
                    const stock = parseInt(input.getAttribute('data-stock'));
                    const requested = parseInt(input.getAttribute('data-requested'));
                    const riId = input.getAttribute('data-id');
                    const remarks = input.closest('tr').querySelector('.issue-remarks-input').value;

                    if (qty > requested) {
                        showModal(`Cannot issue more than the requested quantity (${requested}).`, 'error');
                        hasError = true;
                        input.focus();
                        return;
                    }
                    if (qty > stock) {
                        showModal(`Cannot issue more than available stock for an item.`, 'error');
                        hasError = true;
                        input.focus();
                        return;
                    }
                    if (qty < 0) {
                        showModal(`Issued quantity cannot be negative.`, 'error');
                        hasError = true;
                        input.focus();
                        return;
                    }

                    items.push({
                        request_item_id: riId,
                        issued_quantity: qty,
                        remarks: remarks
                    });
                });

                if (hasError) return;

                showConfirm("Finalize approval and issue these quantities? Stock levels will be updated immediately.", function(result) {
                    if (!result) return;

                    const btn = document.getElementById('btnApproveFinal');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Approval...';

                    fetch(`${basePath}api/submit_issuance.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: reqId, items: items })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showModal(data.message, 'success', () => window.location.reload());
                        } else {
                            showModal("Error: " + data.message, 'error');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-check-double"></i> Complete Approval & Issue';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showModal("An error occurred during submission.", 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check-double"></i> Complete Approval & Issue';
                    });
                });
            });

            // Reject Full Requisition from inside modal
            document.getElementById('btnRejectFull').addEventListener('click', function() {
                const reqId = document.getElementById('appRequisitionId').value;
                const btn = this;

                showConfirm("Are you sure you want to REJECT this entire requisition? This action cannot be undone.", function(result) {
                    if (!result) return;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejecting...';

                    fetch(`${basePath}api/update_requisition_status.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: reqId, action: 'Rejected' })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showModal(data.message, 'success', () => window.location.reload());
                        } else {
                            showModal("Error: " + data.message, 'error');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-times-circle"></i> Reject Entire Requisition';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showModal("An error occurred during submission.", 'error');
                        btn.disabled = false;
                    });
                });
            });

            window.addEventListener('click', (e) => {
                if (e.target == itemsModal) itemsModal.style.display = 'none';
                if (e.target == approvalModal) approvalModal.style.display = 'none';
                if (e.target == rejectedModal) rejectedModal.style.display = 'none';
                if (e.target == itemDetailModal) itemDetailModal.style.display = 'none';
            });

            // Handle Item List Links (the comma separated names in the main tables)
            document.querySelectorAll('.item-list-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Just trigger the "View Items" button for this row
                    const reqId = this.getAttribute('data-id');
                    const btn = document.querySelector(`.btn-view-items[data-id="${reqId}"]`);
                    if (btn) btn.click();
                });
             });

            // Deep-link from notification
            const urlParams = new URLSearchParams(window.location.search);
            const reqIdParam = urlParams.get('id');
            if (reqIdParam) {
                const approveBtn = document.querySelector(`.btn-open-approval[data-id="${reqIdParam}"]`);
                if (approveBtn) {
                    approveBtn.click();
                } else {
                    const viewBtn = document.querySelector(`.btn-view-items[data-id="${reqIdParam}"]`);
                    if (viewBtn) viewBtn.click();
                }
            }

            // Toggle History Button
            const btnToggleHistory = document.getElementById('btnToggleHistory');
            if (btnToggleHistory) {
                btnToggleHistory.addEventListener('click', function() {
                    const hiddenRows = document.querySelectorAll('.history-row-hidden');
                    const isExpanded = hiddenRows[0]?.style.display !== 'none';
                    
                    hiddenRows.forEach(row => {
                        row.style.display = isExpanded ? 'none' : '';
                    });
                    
                    if (isExpanded) {
                        this.innerHTML = '<i class="fas fa-chevron-down"></i> View All (<?php echo count($approvedRequests); ?> records)';
                    } else {
                        this.innerHTML = '<i class="fas fa-chevron-up"></i> Show Less';
                    }
                });
            }
        });
    </script>
    <script src="<?php echo $urlRoot; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $urlRoot; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
</body>
</html>
