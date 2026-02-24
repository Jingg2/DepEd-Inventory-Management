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

            <!-- RPCI Inventory Report -->
            <div class="report-card">
                <div class="report-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <h3>RPCI Inventory Report</h3>
                <p>Report on the Physical Count of Inventory (Appendix 66). Comprehensive stock balance and discrepancy report.</p>
                <a href="<?php echo $root; ?>api/export_supply_excel.php" class="download-btn" id="supply-download-btn">
                    <i class="fas fa-download"></i>
                    Export RPCI Excel
                </a>
            </div>

            <!-- RPCI (PPE & Semi-Expendable) -->
            <div class="report-card">
                <div class="report-icon" style="background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>RPCI (PPE & Semi-Expendable)</h3>
                <p>Report on the Physical Count of Property, Plant, Equipment & Semi-Expendable items (High Value - Appendix 66).</p>
                <a href="<?php echo $root; ?>api/export_ppe_report.php" class="download-btn" id="ppe-download-btn" style="background: var(--gradient-warning); box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2);">
                    <i class="fas fa-download"></i>
                    Export RPCI (PPE)
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

        // Update when office changes
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
    });

    function loadAvailableSnapshots() {
        fetch('<?php echo $root; ?>api/get_snapshots.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const selector = document.getElementById('month-selector');
                    const currentMonth = data.current_month;
                    
                    // Add snapshot options
                    data.snapshots.forEach(snapshot => {
                        const option = document.createElement('option');
                        option.value = snapshot.snapshot_month;
                        const date = new Date(snapshot.snapshot_month + '-01');
                        const monthName = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                        option.textContent = monthName + ' (Snapshot - ' + snapshot.item_count + ' items)';
                        selector.appendChild(option);
                    });
                    
                    // Show info
                    if (data.snapshots.length > 0) {
                        document.getElementById('snapshot-info').textContent = 
                            data.snapshots.length + ' snapshot(s) available';
                    }
                }
            })
            .catch(error => console.error('Error loading snapshots:', error));
    }

    function updateDownloadLinks() {
        const selectedMonth = document.getElementById('month-selector').value;
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        const deptId = document.getElementById('dept-selector').value;
        const basePath = '<?php echo $root; ?>api/';
        
        let supplyUrl = basePath + 'export_supply_excel.php';
        let rsmiUrl = basePath + 'export_rsmi_excel.php';
        let risOfficeUrl = basePath + 'export_ris_by_office.php';
        let wmrUrl = basePath + 'export_wmr_excel.php';
        let ppeUrl = basePath + 'export_ppe_report.php';
        
        let commonParams = [];
        if (startDate && endDate) {
            commonParams.push(`start_date=${startDate}`);
            commonParams.push(`end_date=${endDate}`);
        } else if (selectedMonth !== 'current') {
            commonParams.push(`month=${selectedMonth}`);
        }

        if (commonParams.length > 0) {
            const paramStr = '?' + commonParams.join('&');
            supplyUrl += paramStr;
            rsmiUrl += paramStr;
            risOfficeUrl += paramStr;
            wmrUrl += paramStr;
            ppeUrl += paramStr;
        }

        // Add department parameter to risOfficeUrl if selected
        if (deptId) {
            risOfficeUrl += (risOfficeUrl.includes('?') ? '&' : '?') + `dept_id=${deptId}`;
        }
        
        document.getElementById('supply-download-btn').href = supplyUrl;
        document.getElementById('rsmi-download-btn').href = rsmiUrl;
        document.getElementById('ris-office-download-btn').href = risOfficeUrl;
        document.getElementById('wmr-download-btn').href = wmrUrl;
        if (document.getElementById('ppe-download-btn')) {
            document.getElementById('ppe-download-btn').href = ppeUrl;
        }
        
        // Filter the requisitions table
        const table = document.querySelector('.requisition-table');
        const rows = document.querySelectorAll('.requisition-table tbody tr');
        
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
