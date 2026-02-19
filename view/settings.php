<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();
    require_once __DIR__ . '/../db/database.php';

    $db = new Database();
    $pdo = $db->getConnection();

    require_once __DIR__ . '/../model/settingsModel.php';
    require_once __DIR__ . '/../model/SystemLogModel.php';
    $settingsModel = new SettingsModel();
    $logModel = new SystemLogModel();

    $message = '';
    $messageType = '';

    // Handle Clear Logs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_system_logs'])) {
        if ($logModel->clearLogs()) {
            $message = "System logs have been permanently cleared.";
            $messageType = "success";
        } else {
            $message = "Failed to clear system logs. Please try again.";
            $messageType = "error";
        }
    }

    // Handle Form Submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_admin'])) {
            $new_username = sanitizeInput($_POST['new_username']);
            $new_email = sanitizeInput($_POST['new_email']);
            $new_first_name = sanitizeInput($_POST['new_first_name']);
            $new_last_name = sanitizeInput($_POST['new_last_name']);
            $new_phone = sanitizeInput($_POST['new_phone']);
            $new_password = $_POST['new_password'];
            $confirm_new_password = $_POST['confirm_new_password'];
            $new_role = $_POST['new_role'] ?? 'Admin';

            if ($new_password !== $confirm_new_password) {
                $message = "Passwords do not match.";
                $messageType = "error";
            } else {
                // Check if username exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = ?");
                $stmt->execute([$new_username]);
                if ($stmt->fetchColumn() > 0) {
                    $message = "Username already exists.";
                    $messageType = "error";
                } else {
                    $hashed = hash('sha256', $new_password);
                    $stmt = $pdo->prepare("INSERT INTO admin (username, email, password, role, status, first_name, last_name, phone) VALUES (?, ?, ?, ?, 'Active', ?, ?, ?)");
                    $stmt->execute([$new_username, $new_email, $hashed, $new_role, $new_first_name, $new_last_name, $new_phone]);
                    $message = "New administrator added successfully!";
                    $messageType = "success";

                    require_once __DIR__ . '/../model/SystemLogModel.php';
                    $logModel = new SystemLogModel();
                    $logModel->log("ADD_ADMIN", "Created new administrator account: $new_username");
                }
            }
        }

        if (isset($_POST['change_password'])) {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];

            if ($new !== $confirm) {
                $message = "New passwords do not match.";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT password, username FROM admin WHERE admin_id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                $user = $stmt->fetch();

                if ($user && hash('sha256', $current) === $user['password']) {
                    $newHashed = hash('sha256', $new);
                    $updateStmt = $pdo->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
                    if ($updateStmt->execute([$newHashed, $_SESSION['admin_id']])) {
                        $message = "Password updated successfully!";
                        $messageType = "success";
                        
                        require_once __DIR__ . '/../model/SystemLogModel.php';
                        $logModel = new SystemLogModel();
                        $logModel->log("SECURITY_UPDATE", "Administrator " . $user['username'] . " changed their password.");
                    } else {
                        $message = "Error updating password.";
                        $messageType = "error";
                    }
                } else {
                    $message = "Incorrect current password.";
                    $messageType = "error";
                }
            }
        }

        if (isset($_POST['import_database'])) {
            if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
                require_once __DIR__ . '/../model/BackupModel.php';
                $backupModel = new BackupModel();
                $sqlContent = file_get_contents($_FILES['backup_file']['tmp_name']);
                $result = $backupModel->restoreBackup($sqlContent);
                
                $message = $result['message'];
                $messageType = $result['success'] ? "success" : "error";
                
                if ($result['success']) {
                    require_once __DIR__ . '/../model/SystemLogModel.php';
                    $logModel = new SystemLogModel();
                    $logModel->log("DATABASE_RESTORE", "Restored database from uploaded backup file.");
                }
            } else {
                $message = "Please select a valid backup file.";
                $messageType = "error";
            }
        }

        if (isset($_POST['clear_system_logs'])) {
            require_once __DIR__ . '/../model/BackupModel.php';
            $backupModel = new BackupModel();
            if ($backupModel->clearLogs()) {
                $message = "System logs cleared successfully!";
                $messageType = "success";
                
                require_once __DIR__ . '/../model/SystemLogModel.php';
                $logModel = new SystemLogModel();
                $logModel->log("CLEAR_LOGS", "Manually cleared all system logs.");
            } else {
                $message = "Error clearing system logs.";
                $messageType = "error";
            }
        }
    }

    // Fetch current admin data
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminData) {
        $adminData = [
            'username' => 'Admin',
            'email' => 'admin@inventory.com',
            'role' => 'Administrator',
            'first_name' => 'System',
            'last_name' => 'Admin'
        ];
    }

    $username = $adminData['username'] ?? 'Admin';
    $email = $adminData['email'] ?? 'admin@inventory.com';
    $role = $adminData['role'] ?? 'Administrator';

    // Fetch inventory settings
    $defaultLow = $settingsModel->getSetting('default_low_stock', 10);
    $defaultCritical = $settingsModel->getSetting('default_critical_stock', 5);

    // Fetch all supplies for selection
    require_once __DIR__ . '/../model/supplyModel.php';
    $supplyModel = new SupplyModel();
    $allSupplies = $supplyModel->getAllSupplies();

    require_once __DIR__ . '/../model/requisitionModel.php';
    $reqModelNotify = new RequisitionModel();
    $pendingStats = $reqModelNotify->getRequisitionStats();
    $pendingCount = $pendingStats['pending'] ?? 0;

} catch (Throwable $e) {
    die("<h1>Error Loading Settings</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p><p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
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
    <title>Inventory System - Settings</title>
    <link rel="stylesheet" href="<?php echo $root; ?>css/dashboard.css?v=<?php echo time(); ?>">
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
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 550px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            position: relative;
            animation: modalSlideUp 0.3s ease-out;
            overflow: hidden;
        }
        @keyframes modalSlideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            background: #2A4D88;
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 1.25rem; }
        .close-btn { 
            cursor: pointer; 
            font-size: 1.5rem; 
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .close-btn:hover { opacity: 1; }
        .modal-body { padding: 20px; }
        
        .management-option {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            margin-bottom: 15px;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .management-option:hover {
            border-color: #2A4D88;
            background: #f8fbff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(42,77,136,0.08);
        }
        .option-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .option-details h4 { margin: 0 0 5px 0; color: #2c3e50; }
        .option-details p { margin: 0; font-size: 0.85rem; color: #6c757d; line-height: 1.4; }
        
        .bg-export { background: #e8f0fe; color: #1a73e8; }
        .bg-import { background: #e6f4ea; color: #1e8e3e; }
        .bg-clear { background: #fce8e6; color: #d93025; }
        
        .import-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px dashed #dee2e6;
        }

        .quick-link {
            font-size: 0.85rem;
            color: #2A4D88;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .quick-link:hover {
            text-decoration: underline;
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
            <h1>Settings</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="settings-container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="padding: 15px; border-radius: 10px; margin-bottom: 20px; <?php echo $messageType === 'success' ? 'background: #e6f4ea; color: #1e8e3e; border: 1px solid #ceead6;' : 'background: #fce8e6; color: #d93025; border: 1px solid #fad2cf;'; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Overview Card (Button-like) -->
            <div class="settings-card profile-summary-card">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-details">
                        <h2><?php echo htmlspecialchars($adminData['first_name'] . ' ' . $adminData['last_name']); ?></h2>
                        <p><?php echo htmlspecialchars($username); ?> &bull; <?php echo htmlspecialchars($email); ?> &bull; <span style="text-transform: uppercase; font-size: 0.8rem; font-weight: 700; opacity: 0.8;"><?php echo htmlspecialchars($role); ?></span></p>
                    </div>
                </div>
                <button class="btn-edit-profile">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </button>
            </div>

            <!-- Focused Security Section -->
            <div class="settings-card security-card">
                <div class="card-header">
                    <h3><i class="fas fa-key"></i> Security Settings</h3>
                    <p>Update your password to keep your account secure</p>
                </div>
                
                <form class="focused-form" method="POST" action="">
                    <div class="input-row">
                        <div class="input-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="input-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters" required>
                        </div>
                        <div class="input-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" name="change_password" class="btn-primary-settings">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Add Admin Account Section -->
            <div class="settings-card admin-registration-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> Add New Administrator</h3>
                    <p>Register a new administrator account with SHA256 security</p>
                </div>
                
                <form class="focused-form" method="POST" action="">
                    <div class="input-row">
                        <div class="input-group">
                            <label for="new_first_name">First Name</label>
                            <input type="text" id="new_first_name" name="new_first_name" placeholder="First Name" required>
                        </div>
                        <div class="input-group">
                            <label for="new_last_name">Last Name</label>
                            <input type="text" id="new_last_name" name="new_last_name" placeholder="Last Name" required>
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="input-group">
                            <label for="new_username">User Name</label>
                            <input type="text" id="new_username" name="new_username" placeholder="e.g. jdoe_admin" required>
                        </div>
                        <div class="input-group">
                            <label for="new_email">Email Address</label>
                            <input type="email" id="new_email" name="new_email" placeholder="e.g. admin@inventory.com" required>
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="input-group">
                            <label for="new_phone">Phone Number</label>
                            <input type="text" id="new_phone" name="new_phone" placeholder="e.g. 09123456789" required>
                        </div>
                        <div class="input-group">
                            <label for="new_role">Account Role</label>
                            <select id="new_role" name="new_role" style="padding: 12px; border-radius: 10px; border: 2px solid #e9ecef;">
                                <option value="Admin">Admin</option>
                                <option value="Super Admin">Super Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-row">
                        <div class="input-group">
                            <label for="new_password">Set Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Use a strong password" required>
                        </div>
                        <div class="input-group">
                            <label for="confirm_new_password">Confirm Password</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" placeholder="Confirm the password" required>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" name="add_admin" class="btn-primary-settings" style="background: #1a73e8;">
                            <i class="fas fa-plus"></i> Register Administrator
                        </button>
                    </div>
                </form>
            </div>



            <!-- Other Settings Grid -->
            <div class="other-settings-grid">
                <div class="settings-card small-card">
                    <h3><i class="fas fa-database"></i> Data Management</h3>
                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Maintenance and backups</p>
                    <button class="btn-secondary-settings" onclick="openDataManagementModal()">Backup & Restore</button>
                </div>
                <div class="settings-card small-card">
                    <h3><i class="fas fa-history"></i> System Logs</h3>
                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">View administrative activity</p>
                    <a href="<?php echo $root; ?>system_logs" class="btn-secondary-settings" style="text-decoration: none; display: block; text-align: center;">View Logs</a>
                </div>
                <div class="settings-card small-card" style="border-top: 4px solid #2A4D88;">
                    <h3><i class="fas fa-question-circle"></i> Help Center</h3>
                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Guides & Step-by-step instructions</p>
                    <a href="<?php echo $root; ?>help_center" class="btn-primary-settings" style="text-decoration: none; display: block; text-align: center; width: 100%; padding: 10px;">Open Help Center</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 30px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .profile-summary-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #2A4D88 0%, #1a3a6b 100%);
            color: white;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .profile-summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(42, 77, 136, 0.3);
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar i {
            font-size: 50px;
            opacity: 0.9;
        }

        .profile-details h2 {
            margin: 0;
            font-size: 1.5rem;
            color: white;
        }

        .profile-details p {
            margin: 5px 0 0 0;
            opacity: 0.8;
            font-size: 0.95rem;
        }

        .btn-edit-profile {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-edit-profile:hover {
            background: white;
            color: #2A4D88;
        }

        .card-header {
            margin-bottom: 30px;
        }

        .card-header h3 {
            margin: 0 0 10px 0;
            color: #0d2137;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header p {
            color: #6c757d;
            margin: 0;
            font-size: 0.95rem;
        }

        .focused-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .input-row { grid-template-columns: 1fr; }
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }

        .input-group input {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .input-group input:focus {
            border-color: #2A4D88;
            outline: none;
            box-shadow: 0 0 0 4px rgba(42, 77, 136, 0.1);
        }

        .form-footer {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-primary-settings {
            background: #2A4D88;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary-settings:hover {
            background: #1a3a6b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(42, 77, 136, 0.3);
        }

        .other-settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .small-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 15px;
        }

        .small-card h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #0d2137;
        }

        .btn-secondary-settings {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 10px 20px;
            border-radius: 8px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary-settings:hover {
            background: #e9ecef;
            color: #0d2137;
        }
    </style>
    <!-- Data Management Modal -->
    <div id="dataManagementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-database"></i> Data Management</h3>
                <span class="close-btn" onclick="closeDataManagementModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Export Option -->
                <a href="<?php echo $root; ?>api/backup.php" class="management-option">
                    <div class="option-icon bg-export">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <div class="option-details">
                        <h4>Export Database (Backup)</h4>
                        <p>Generate and download a full SQL backup of the system database.</p>
                    </div>
                </a>

                <!-- Import Option -->
                <div class="management-option" onclick="toggleImportForm()">
                    <div class="option-icon bg-import">
                        <i class="fas fa-file-import"></i>
                    </div>
                    <div class="option-details">
                        <h4>Import Database (Restore)</h4>
                        <p>Upload a previously exported SQL file to restore system data.</p>
                    </div>
                </div>

                <!-- Hidden Import Form -->
                <div id="importForm" class="import-form">
                    <form id="restore-db-form" method="POST" enctype="multipart/form-data" onsubmit="event.preventDefault(); confirmRestore();">
                        <p style="font-size: 0.8rem; color: #d93025; margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-exclamation-triangle"></i> WARNING: This will overwrite CURRENT data.
                        </p>
                        <input type="file" name="backup_file" accept=".sql" required style="margin-bottom: 10px; width: 100%;">
                        <button type="submit" name="import_database" class="btn-primary-settings" style="width: 100%; padding: 10px; background: #1e8e3e;">
                            Start Restoration
                        </button>
                    </form>
                </div>

                <!-- Clear Logs Option -->
                <form id="clear-logs-form" method="POST" onsubmit="event.preventDefault(); confirmClearLogs()" style="margin: 0;">
                    <input type="hidden" name="clear_system_logs" value="1">
                    <button type="submit" class="management-option" style="width: 100%; background: none; border: 1px solid #fce8e6; text-align: left; font-family: inherit;">
                        <div class="option-icon bg-clear">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                        <div class="option-details">
                            <h4>Clear System Logs</h4>
                            <p>Permanently remove all activity logs. Use this to free up space.</p>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
    <script>
        function openDataManagementModal() {
            document.getElementById('dataManagementModal').classList.add('show');
        }

        function closeDataManagementModal() {
            document.getElementById('dataManagementModal').classList.remove('show');
            document.getElementById('importForm').style.display = 'none';
        }

        function toggleImportForm() {
            const form = document.getElementById('importForm');
            form.style.display = (form.style.display === 'block') ? 'none' : 'block';
        }

        function confirmRestore() {
            showConfirm("CRITICAL WARNING: This will DELETE all current data and replace it with the backup content. This action CANNOT be undone. Are you absolutely sure?", function(result) {
                if (result) {
                    document.getElementById('restore-db-form').submit();
                }
            });
        }

        function confirmClearLogs() {
            showConfirm("Are you sure you want to PERMANENTLY delete all system activity logs?", function(result) {
                if (result) {
                    document.getElementById('clear-logs-form').submit();
                }
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('dataManagementModal');
            if (event.target == modal) {
                closeDataManagementModal();
            }
        }
        function filterThresholdTable() {
            const query = document.getElementById('threshold_item_search').value.toLowerCase();
            const rows = document.querySelectorAll('#threshold_table tbody tr');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const stock = row.getAttribute('data-stock');
                if (name.includes(query) || stock.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleAllThresholds(source) {
            const checkboxes = document.querySelectorAll('.item-threshold-checkbox');
            checkboxes.forEach(cb => {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = source.checked;
                }
            });
            updateBulkUI();
        }

        function updateBulkUI() {
            const selected = document.querySelectorAll('.item-threshold-checkbox:checked');
            const area = document.getElementById('item_update_area');
            const countDiv = document.getElementById('selection_count');
            const countText = document.getElementById('items_selected_text');
            const summary = document.getElementById('selected_items_summary');
            const header = document.getElementById('update_header_text');
            const idContainer = document.getElementById('hidden_ids_container');
            const lowInput = document.getElementById('item_low_stock');
            const critInput = document.getElementById('item_critical_stock');

            idContainer.innerHTML = '';
            
            if (selected.length > 0) {
                area.style.display = 'block';
                countDiv.style.display = 'block';
                countText.textContent = `${selected.length} items selected`;
                
                if (selected.length === 1) {
                    header.textContent = `Update Item: ${selected[0].getAttribute('data-name')}`;
                    summary.textContent = `Modifying threshold for a single item. Current: Low ${selected[0].getAttribute('data-low')}, Crit ${selected[0].getAttribute('data-critical')}`;
                    lowInput.value = selected[0].getAttribute('data-low');
                    critInput.value = selected[0].getAttribute('data-critical');
                } else {
                    header.textContent = `Bulk Update (${selected.length} Items)`;
                    summary.textContent = `You are updating thresholds for ${selected.length} different supplies simultaneously. All selected items will receive the same values.`;
                    // Clear values for bulk to avoid confusion, or set to first one
                    lowInput.value = '';
                    critInput.value = '';
                    lowInput.placeholder = "Enter new low threshold";
                    critInput.placeholder = "Enter new critical threshold";
                }

                selected.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'item_ids[]';
                    input.value = cb.value;
                    idContainer.appendChild(input);
                });

                area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                area.style.display = 'none';
                countDiv.style.display = 'none';
            }
        }

        function selectSingleForThreshold(id, name, low, crit) {
            // Uncheck others
            document.querySelectorAll('.item-threshold-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select_all_threshold').checked = false;
            
            // Check this one
            const targetCb = document.querySelector(`.item-threshold-checkbox[value="${id}"]`);
            if (targetCb) targetCb.checked = true;
            
            updateBulkUI();
        }

        function cancelSelection() {
            document.querySelectorAll('.item-threshold-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select_all_threshold').checked = false;
            updateBulkUI();
        }
    </script>
    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
</body>
</html>