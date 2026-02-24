<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();
    require_once __DIR__ . '/../db/database.php';

    require_once __DIR__ . '/../model/requisitionModel.php';
    $reqModelNotify = new RequisitionModel();
    $pendingStats = $reqModelNotify->getRequisitionStats();
    $pendingCount = $pendingStats['pending'] ?? 0;

} catch (Throwable $e) {
    die("<h1>Error Loading Help Center</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>");
}

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
    <title>Inventory System - Help Center</title>
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
        .help-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Help Navigation Sidebar */
        .help-nav {
            position: sticky;
            top: 100px;
            height: fit-content;
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #edf2f7;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .help-nav h3 {
            margin: 0 0 15px 0;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }
        .help-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .help-nav li {
            margin-bottom: 5px;
        }
        .help-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #475569;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .help-nav a:hover {
            background: var(--bg-light);
            color: var(--primary-emerald);
        }
        .help-nav a.active {
            background: var(--bg-emerald-light);
            color: var(--primary-emerald);
        }

        /* Help Content Areas */
        .help-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .help-section.active {
            display: block;
        }
        
        .section-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        .section-header h2 {
            font-size: 1.8rem;
            color: #1e293b;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .section-header p {
            color: #64748b;
            font-size: 1.1rem;
            margin: 0;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }
        .help-card {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid #edf2f7;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .help-card h4 {
            margin: 0;
            color: #1e293b;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .help-card h4 i {
            color: var(--primary-emerald);
            font-size: 1rem;
        }

        .help-steps {
            margin: 0;
            padding: 0 0 0 20px;
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.7;
        }
        .help-steps li {
            margin-bottom: 10px;
        }
        .help-steps li strong {
            color: #1e293b;
        }

        .alert-info {
            background: #f0f7ff;
            border-left: 4px solid var(--primary-emerald);
            padding: 15px;
            border-radius: 8px;
            color: #1e40af;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1024px) {
            .help-layout { grid-template-columns: 1fr; }
            .help-nav { position: static; margin-bottom: 20px; }
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
            <li><a href="dashboard"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="inventory"><i class="fas fa-box"></i> <span>Supply</span></a></li>
            <li class="divider"></li>
            <li>
                <a href="requests">
                    <i class="fas fa-file-invoice"></i> <span>Request</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="sidebar-badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="divider"></li>
            <li><a href="employees"><i class="fas fa-users"></i> <span>Employee</span></a></li>
            <li><a href="reports"><i class="fas fa-file-excel"></i> <span>Reports</span></a></li>
            <li class="divider"></li>
            <li><a href="settings" class="active"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="<?php echo $root; ?>logout" style="color: #ef5350;" onclick="showLogoutModal(event, this.href);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="settings" class="back-link" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back</a>
                <h1>Help Center</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="help-layout">
            <aside class="help-nav">
                <h3>Categories</h3>
                <ul>
                    <li><a href="#inventory" class="active" onclick="showSection('inventory', this)"><i class="fas fa-boxes"></i> Inventory & Assets</a></li>
                    <li><a href="#employees" onclick="showSection('employees', this)"><i class="fas fa-users"></i> Staff & Employees</a></li>
                    <li><a href="#requisitions" onclick="showSection('requisitions', this)"><i class="fas fa-clipboard-list"></i> Requisitions (RIS)</a></li>
                    <li><a href="#reports" onclick="showSection('reports', this)"><i class="fas fa-file-chart-line"></i> Reports & Exports</a></li>
                    <li><a href="#admin" onclick="showSection('admin', this)"><i class="fas fa-user-shield"></i> Administration</a></li>
                </ul>
            </aside>

            <main class="help-content">
                <!-- INVENTORY SECTION -->
                <section id="inventory" class="help-section active">
                    <div class="section-header">
                        <h2><i class="fas fa-boxes"></i> Inventory & Asset Management</h2>
                        <p>Manage your supplies, equipment, and semi-expendable assets.</p>
                    </div>

                    <div class="help-grid">
                        <div class="help-card">
                            <h4><i class="fas fa-plus-circle"></i> Adding New Supplies</h4>
                            <ol class="help-steps">
                                <li>Navigate to <strong>Supply</strong> and click <strong>Add New Supply</strong>.</li>
                                <li>Fill in <strong>Item Name</strong>, <strong>Category</strong>, and <strong>Unit</strong>.</li>
                                <li>Enter <strong>Unit Cost</strong> and <strong>Current Stock</strong>.</li>
                                <li>Select <strong>Property Classification</strong> (Essential for reporting).</li>
                                <li>Click <strong>Submit</strong> to save.</li>
                            </ol>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-edit"></i> Editing or Deleting</h4>
                            <ol class="help-steps">
                                <li>Find the item in the supply table.</li>
                                <li>Click the <i class="fas fa-edit" style="color: #2196F3;"></i> icon to update descriptions or costs.</li>
                                <li>Click the <i class="fas fa-trash" style="color: #F44336;"></i> icon to remove an item.</li>
                            </ol>
                            <div class="alert-info">
                                <i class="fas fa-exclamation-triangle"></i> Deleting an item will permanently remove its history and associated requisitions.
                            </div>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-history"></i> Stock Card & Balance</h4>
                            <ol class="help-steps">
                                <li>Click the <i class="fas fa-layer-group" style="color: #607D8B;"></i> (layers) icon on any item.</li>
                                <li>The <strong>Stock Card</strong> shows every "In" and "Out" transaction.</li>
                                <li>You can view the <strong>Running Balance</strong> at any point in time.</li>
                                <li>Use <strong>Audit History</strong> to see who processed each transaction.</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- EMPLOYEES SECTION -->
                <section id="employees" class="help-section">
                    <div class="section-header">
                        <h2><i class="fas fa-users"></i> Staff & Employees</h2>
                        <p>Manage employee records and registration requirements.</p>
                    </div>

                    <div class="help-grid">
                        <div class="help-card">
                            <h4><i class="fas fa-user-plus"></i> Registering Employees</h4>
                            <ol class="help-steps">
                                <li>Go to the <strong>Employee</strong> page and click <strong>Add New Employee</strong>.</li>
                                <li>Enter the <strong>Employee ID</strong> (Must be exactly 7 digits).</li>
                                <li>Complete the <strong>First Name</strong>, <strong>Last Name</strong>, and <strong>Position</strong>.</li>
                                <li>Select their <strong>Department/Office</strong>.</li>
                            </ol>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-id-card"></i> 7-Digit ID Validation</h4>
                            <p>To maintain data integrity, the system strictly enforces a 7-digit numeric format for all Employee IDs.</p>
                            <ol class="help-steps">
                                <li>IDs must be <strong>exactly 7 digits</strong> (e.g., 1234567).</li>
                                <li>Alphabetical characters are not allowed.</li>
                                <li>If an ID is invalid, the registration form will highlight the error and block submission.</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- REQUISITIONS SECTION -->
                <section id="requisitions" class="help-section">
                    <div class="section-header">
                        <h2><i class="fas fa-clipboard-list"></i> Requisitions (RIS)</h2>
                        <p>The core process for requesting and issuing inventory items.</p>
                    </div>

                    <div class="help-grid">
                        <div class="help-card">
                            <h4><i class="fas fa-pen-nib"></i> Creating a Requisition</h4>
                            <ol class="help-steps">
                                <li>In <strong>Supply</strong>, click <strong>Requisition Slip</strong> or the <i class="fas fa-clipboard-list"></i> icon.</li>
                                <li>Choose the <strong>Employee</strong> who will receive the items.</li>
                                <li>Select items and set <strong>Requested Quantity</strong>.</li>
                                <li>The system verifies available stock before allowing submission.</li>
                            </ol>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-check-double"></i> Approval Process</h4>
                            <ol class="help-steps">
                                <li>Navigate to the <strong>Request</strong> page.</li>
                                <li>Review pending requests at the top.</li>
                                <li>Click <strong>Approve</strong> <i class="fas fa-check"></i> to finalize and deduct stock.</li>
                                <li>Click <strong>Decline</strong> <i class="fas fa-times"></i> to reject the request.</li>
                            </ol>
                            <div class="alert-info">
                                <i class="fas fa-info-circle"></i> Once approved, the stock is automatically updated and a transaction ID is generated.
                            </div>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-undo"></i> Returning Items</h4>
                            <ol class="help-steps">
                                <li>Go to the <strong>Employee</strong> page.</li>
                                <li>Click <strong>View Issued Items</strong> <i class="fas fa-eye"></i> on the specific staff member.</li>
                                <li>Locate the item and click <strong>Return</strong> <i class="fas fa-redo"></i>.</li>
                                <li>Returned items are added back to stock unless marked as <strong>Waste/Damaged</strong>.</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- REPORTS SECTION -->
                <section id="reports" class="help-section">
                    <div class="section-header">
                        <h2><i class="fas fa-file-chart-line"></i> Reports & Exports</h2>
                        <p>Generate administrative documents and Excel accountability reports.</p>
                    </div>

                    <div class="help-grid">
                        <div class="help-card">
                            <h4><i class="fas fa-file-excel"></i> Basic Inventory Reports</h4>
                            <ol class="help-steps">
                                <li><strong>RPCI (Appendix 66)</strong>: Full physical count report with current stock levels.</li>
                                <li><strong>RSMI</strong>: Summary of all supplies and materials issued for a specific period.</li>
                                <li><strong>RIS by Office</strong>: Grouped report showing distributions categorized by department.</li>
                            </ol>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-shield-alt"></i> Accountability Reports</h4>
                            <ol class="help-steps">
                                <li><strong>ICS (Inventory Custodian Slip)</strong>: Issues for semi-expendable items that require staff signature.</li>
                                <li><strong>PPE Report</strong>: Property, Plant, and Equipment report tracking larger assets.</li>
                                <li><strong>WMR (Waste Materials Report)</strong>: Documentation for disposals and unserviceable items.</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- ADMIN SECTION -->
                <section id="admin" class="help-section">
                    <div class="section-header">
                        <h2><i class="fas fa-user-shield"></i> Administration</h2>
                        <p>Security, data maintenance, and system configurations.</p>
                    </div>

                    <div class="help-grid">
                        <div class="help-card">
                            <h4><i class="fas fa-user-lock"></i> Security & Passwords</h4>
                            <ol class="help-steps">
                                <li>In <strong>Settings</strong>, you can update your administrator password.</li>
                                <li>Passwords are hashed using <strong>SHA256</strong> for maximum security.</li>
                                <li>Register new administrators carefully to maintain system access control.</li>
                            </ol>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-database"></i> Database Management</h4>
                            <ol class="help-steps">
                                <li>Use <strong>Backup & Restore</strong> to download a full SQL copy of the system.</li>
                                <li>System Logs track every login, addition, and security change.</li>
                                <li>Regular backups are recommended before performing major data deletions.</li>
                            </ol>
                        </div>
                        <div class="help-card">
                            <h4><i class="fas fa-key"></i> Password Recovery</h4>
                            <p>If you lose access to your administrator account, follow these steps to regain entry:</p>
                            <ol class="help-steps">
                                <li>On the <strong>Login Page</strong>, click the <strong>Forgot Password</strong> link.</li>
                                <li>Enter your <strong>registered email address</strong>.</li>
                                <li>Check your inbox for a <strong>6-digit PIN</strong> (Validity: 5 minutes).</li>
                                <li>Enter the PIN on the <strong>Verification Page</strong>.</li>
                                <li>Set a <strong>new password</strong> to complete the recovery.</li>
                            </ol>
                            <div class="alert-info">
                                <i class="fas fa-shield-alt"></i> For security, the system will not disclose if an email is registered unless a PIN is successfully generated.
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
    
    <script>
        function showSection(sectionId, element) {
            // Hide all sections
            document.querySelectorAll('.help-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            document.getElementById(sectionId).classList.add('active');
            
            // Update active link
            document.querySelectorAll('.help-nav a').forEach(link => {
                link.classList.remove('active');
            });
            element.classList.add('active');
            
            // Scroll to top of content
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Handle direct URL hashes on load
        window.addEventListener('load', () => {
            const hash = window.location.hash.replace('#', '');
            if (hash) {
                const targetLink = document.querySelector(`.help-nav a[href="#${hash}"]`);
                if (targetLink) targetLink.click();
            }
        });
    </script>
</body>
</html>
