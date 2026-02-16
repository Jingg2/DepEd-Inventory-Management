<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();
    require_once __DIR__ . '/../controller/employeeController.php';
    $controller = new EmployeeController();
    $data = $controller->handleRequest();
    $employees = $data['employees'];
    $departments = $data['departments'];
    $message = $data['message'] ?? '';

    require_once __DIR__ . '/../model/requisitionModel.php';
    $reqModelNotify = new RequisitionModel();
    $pendingStats = $reqModelNotify->getRequisitionStats();
    $pendingCount = $pendingStats['pending'] ?? 0;
} catch (Throwable $e) {
    die("<h1>Error Loading Employee Page</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p><p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Employee Management</title>
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
        .add-employee-btn {
            background-color: #2A4D88;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(42, 77, 136, 0.2);
        }

        .add-employee-btn:hover {
            background-color: #1e3a6a;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(42, 77, 136, 0.3);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000; /* Increased to be above everything */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            overflow-y: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 2% auto;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: visible;
            animation: slideDown 0.4s ease-out;
            position: relative;
            z-index: 2001; /* Ensure content is above overlay */
            pointer-events: auto; /* Enable interaction */
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background-color: #2A4D88;
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close-modal:hover {
            color: #ccc;
        }

        .modal-body {
            padding: 20px;
            max-height: 70vh; /* Limit height of body */
            overflow-y: auto; /* Enable scrolling inside body */
        }

        .error-message {
            background-color: #fceaea;
            color: #e74c3c;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #2A4D88;
            outline: none;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .modal-footer {
            padding: 12px 20px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #eee;
        }

        .btn-cancel {
            background-color: #7f8c8d;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-save {
            background-color: #2A4D88;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-save:hover {
            background-color: #1e3a6a;
        }
        
        .status-active {
            color: green;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
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
            <li><a href="dashboard" class="<?php echo ($currentRoute == '/dashboard') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="inventory" class="<?php echo ($currentRoute == '/inventory') ? 'active' : ''; ?>"><i class="fas fa-box"></i> <span>Supply</span></a></li>
            <li class="divider"></li>
            <li>
                <a href="requests" class="<?php echo ($currentRoute == '/requests') ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> <span>Request</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="sidebar-badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="divider"></li>
            <li><a href="employees" class="<?php echo ($currentRoute == '/employees') ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Employee</span></a></li>
            <li><a href="reports" class="<?php echo ($currentRoute == '/reports') ? 'active' : ''; ?>"><i class="fas fa-file-excel"></i> <span>Reports</span></a></li>
            <li class="divider"></li>
            <li><a href="settings" class="<?php echo ($currentRoute == '/settings') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="<?php echo $root; ?>logout" style="color: #ef5350;" onclick="showLogoutModal(event, this.href);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Employee Management</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-users" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: var(--primary-emerald);"></i>
                <h3>Total Employees</h3>
                <p><?php echo count($employees); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-building" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: #764ba2;"></i>
                <h3>Total Departments</h3>
                <p><?php echo count($departments); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-shield" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: #2ecc71;"></i>
                <h3>Active Staff</h3>
                <p><?php 
                    $activeCount = 0;
                    foreach($employees as $e) if(($e['status'] ?? '') === 'Active') $activeCount++;
                    echo $activeCount; 
                ?></p>
            </div>
        </div>

        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <button class="add-employee-btn" id="openModalBtn">
                <i class="fas fa-user-plus"></i> Register Employee
            </button>
        </div>

        <div class="table-section">
            <h2><i class="fas fa-users"></i> Employee List</h2>
            <div style="overflow-x: auto;">
                <table class="standard-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Position</th>
                        <th>Dept ID</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No employees found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($emp['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department_name'] ?? $emp['department_id']); ?></td>
                                <td><?php echo htmlspecialchars($emp['role']); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($emp['status']); ?>">
                                        <?php echo htmlspecialchars($emp['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($emp['created_at'])); ?></td>
                                <td>
                                    <i class="fas fa-edit" style="color: #2A4D88; cursor: pointer; margin-right: 10px;" title="Edit"></i>
                                    <i class="fas fa-trash" style="color: #e74c3c; cursor: pointer;" title="Delete"></i>
                                    <?php if (($emp['held_items_count'] ?? 0) > 0): ?>
                                        <a href="../api/export_ics_excel.php?employee_id=<?php echo urlencode($emp['employee_id']); ?>" 
                                           title="Download Borrowing History (ICS)" 
                                           style="color: #a87e00; margin-left: 10px; font-size: 1.1rem; text-decoration: none;">
                                            <i class="fas fa-address-card"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <!-- Registration Modal -->
    <div id="registrationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> New Employee</h2>
                <span class="close-modal" id="closeModalBtn">&times;</span>
            </div>
            <div class="modal-body">
                <?php if (!empty($message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <form class="registration-form" id="employeeForm" method="POST" action="employee.php">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Employee ID</label>
                        <input type="text" name="employee_id" placeholder="Enter Employee ID" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> First Name</label>
                            <input type="text" name="first_name" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" name="last_name" placeholder="Enter last name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-briefcase"></i> Position</label>
                            <input type="text" name="position" placeholder="e.g. IT Technician" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Department</label>
                            <select name="department_id" id="departmentSelect" onchange="toggleDeptField(this.value)" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Other" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == 'Other') ? 'selected' : ''; ?>>Other (Create New)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Department Name - High Visibility -->
                    <div class="form-group" id="customDepartmentGroup" style="display: <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == 'Other') ? 'block' : 'none'; ?>; border: 2px solid #2A4D88; padding: 15px; border-radius: 8px; background-color: #f0f4f8; margin-top: 15px;">
                        <label style="color: #2A4D88;"><i class="fas fa-plus-circle"></i> New Department Name</label>
                        <input type="text" name="custom_department" id="customDepartment" placeholder="Type new department name here..." value="<?php echo htmlspecialchars($_POST['custom_department'] ?? ''); ?>">
                        <small style="color: #666; margin-top: 5px; display: block;">This department will be created automatically.</small>
                    </div>

                    <script>
                        function toggleDeptField(value) {
                            var group = document.getElementById('customDepartmentGroup');
                            var input = document.getElementById('customDepartment');
                            if (value === 'Other') {
                                group.style.display = 'block';
                                input.required = true;
                                setTimeout(function() { input.focus(); }, 100);
                            } else {
                                group.style.display = 'none';
                                input.required = false;
                                input.value = '';
                            }
                        }
                    </script>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user-tag"></i> Role</label>
                            <select name="role">
                                <option value="Manager">Manager</option>
                                <option value="Staff" selected>Staff</option>
                                <option value="Supervisor">Supervisor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-info-circle"></i> Status</label>
                            <select name="status">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                <button type="submit" form="employeeForm" class="btn-save">Register</button>
            </div>
        </div>
    </div>

    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/employee.js"></script>
    <?php if (isset($_GET['success'])): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showModal('Employee registered successfully!', 'success'));</script>
    <?php endif; ?>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
</body>
</html>