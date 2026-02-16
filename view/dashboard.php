<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();

    require_once __DIR__ . '/../model/requisitionModel.php';
    $reqModel = new RequisitionModel();
    $reqStats = $reqModel->getRequisitionStats();
    
    // Ensure critical variables are always set
    $pendingCount = $reqStats['pending'] ?? 0;
    $totalRequisitions = $reqStats['total'] ?? 0;
} catch (Throwable $e) {
    die("<h1>Error Loading Dashboard</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p><p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
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
    <title>Inventory System Dashboard</title>
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
            <li><a href="<?php echo $root; ?>logout" style="color: #ef5350;" onclick="showLogoutModal(event, this.href);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <!-- Debug Panel -->
        <div id="debugPanel" style="background:#fee2e2; border:2px solid #ef4444; padding:15px; margin:20px; font-family:monospace; font-size:12px; display:none;">
            <strong style="color:#b91c1c;">🔍 DEBUG INFO:</strong><br>
            <div id="debugContent" style="color:#1f2937;">Checking...</div>
        </div>
        <?php
        require_once __DIR__ . '/../model/supplyModel.php';
        require_once __DIR__ . '/../model/employeeModel.php';
        $supplyModel = new SupplyModel();
        $employeeModel = new EmployeeModel();
        $allSupplies = $supplyModel->getAllSupplies();
        $totalItems = count($allSupplies);
        $totalEmployees = count($employeeModel->getAllEmployees());

        $totalInventoryCost = 0;
        foreach($allSupplies as $s) {
            $totalInventoryCost += isset($s['total_cost']) ? (float)$s['total_cost'] : 0;
        }
        ?>
        <div class="header">
            <h1>Dashboard</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>
        <?php
        $lowStockTotal = 0;
        $outOfStockTotal = 0;
        foreach($allSupplies as $s) {
            $q = isset($s['quantity']) ? (int)$s['quantity'] : 0;
            if ($q <= 0) {
                $outOfStockTotal++;
            } elseif ($q <= 10) {
                $lowStockTotal++;
            }
        }
        ?>
        <?php
        $totalRequisitions = $reqStats['total'] ?? 0;
        ?>
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-box stat-card-bg-icon" style="color: var(--primary-emerald);"></i>
                <h3>Total Products</h3>
                <p><?php echo $totalItems; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-coins stat-card-bg-icon" style="color: #2ecc71;"></i>
                <h3>Total Cost</h3>
                <p>₱<?php echo number_format($totalInventoryCost, 2); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users stat-card-bg-icon" style="color: #764ba2;"></i>
                <h3>Total Employee</h3>
                <p><?php echo $totalEmployees; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-invoice stat-card-bg-icon" style="color: #10b981;"></i>
                <h3>Total Requisitions</h3>
                <p><?php echo $totalRequisitions; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle stat-card-bg-icon" style="color: #ed8936;"></i>
                <h3>Low Stock Items</h3>
                <p><?php echo $lowStockTotal; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle stat-card-bg-icon" style="color: #ef5350;"></i>
                <h3>Total Out of Stock</h3>
                <p><?php echo $outOfStockTotal; ?></p>
            </div>
        </div>

        <div class="charts-grid">
            <!-- Stock Levels Analysis -->
            <div class="chart-container full-width">
                <h3><i class="fas fa-chart-bar"></i> Stock Levels Analysis</h3>
                <div class="chart-scroll-wrapper">
                    <div class="chart-inner-wrapper">
                        <canvas id="stockLevelsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Stock Distribution -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-pie"></i> Stock Distribution</h3>
                <canvas id="distributionChart"></canvas>
            </div>

            <!-- Inventory Value -->
            <div class="chart-container">
                <h3><i class="fas fa-hand-holding-usd"></i> Inventory Value Analysis</h3>
                <canvas id="valueChart"></canvas>
            </div>

            <!-- Employee Requisitions -->
            <div class="chart-container full-width">
                <h3><i class="fas fa-user-tag"></i> Top Employee Requisitions</h3>
                <canvas id="trendChart"></canvas>
            </div>

            <!-- Turnover Analysis -->
            <div class="chart-container">
                <h3><i class="fas fa-sync-alt"></i> Turnover Analysis</h3>
                <div class="chart-scroll-wrapper">
                    <div class="chart-inner-wrapper">
                        <canvas id="turnoverChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Urgency Heatmap (Simulated with Bar) -->
            <div class="chart-container">
                <h3><i class="fas fa-exclamation-triangle"></i> Alert Urgency</h3>
                <canvas id="urgencyChart"></canvas>
            </div>
        </div>
        <h2>Recent Inventory</h2>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Unit Cost</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $recentItems = array_slice($allSupplies, 0, 5);
                foreach ($recentItems as $item): 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['item']); ?></td>
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td>
                        <span class="badge <?php echo ($item['quantity'] <= 10) ? 'badge-danger' : 'badge-success'; ?>">
                            <?php echo $item['quantity']; ?>
                        </span>
                    </td>
                    <td>₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                    <td>₱<?php echo number_format($item['total_cost'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            var container = document.querySelector('.main-content') || document.body;
            var errorBox = document.createElement('div');
            errorBox.style.background = '#fee2e2';
            errorBox.style.color = '#b91c1c';
            errorBox.style.padding = '20px';
            errorBox.style.margin = '20px';
            errorBox.style.border = '2px solid #ef4444';
            errorBox.style.borderRadius = '8px';
            errorBox.style.fontFamily = 'monospace';
            errorBox.style.zIndex = '9999';
            errorBox.innerHTML = '<strong>JavaScript Error:</strong><br>' + msg + '<br>Line: ' + lineNo;
            container.prepend(errorBox);
            return false;
        };
    </script>
    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debug panel removed - charts are working
        // const panel = document.getElementById('debugPanel');
        // const content = document.getElementById('debugContent');
        // panel.style.display = 'block';
        
        // Check Chart.js
        if(typeof Chart === 'undefined') {
            alert('Chart.js failed to load. Please check internet connection.');
            return;
        }
        
        // Fetch data
        const apiUrl = '<?php echo $root; ?>api/get_dashboard_charts_data.php';
        
        fetch(apiUrl)
            .then(r => {
                if(!r.ok) throw new Error('API returned status ' + r.status);
                return r.text();
            })
            .then(text => {
                const result = JSON.parse(text);
                
                if(!result.success) {
                    console.error('API Error:', result.message);
                    return;
                }
                
                if(!result.data || !result.data.stockLevels) {
                    console.error('No data received from API');
                    return;
                }
                
                const data = result.data;
                
                // Render all charts
                if(data.stockLevels && data.stockLevels.length > 0) {
                    const canvas = document.getElementById('stockLevelsChart');
                    const chartWrapper = canvas.parentElement;
                    // Dynamic height: 40px per item for better spacing
                    const height = Math.max(600, data.stockLevels.length * 40);
                    chartWrapper.style.height = height + 'px';
                    canvas.height = height;
                    
                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.stockLevels.map(i => i.name),
                            datasets: [{
                                label: 'Quantity',
                                data: data.stockLevels.map(i => i.qty),
                                backgroundColor: data.stockLevels.map(i => {
                                    if(i.urgency === 'Out of Stock') return '#ef5350';
                                    if(i.urgency === 'Critical') return '#ff9800';
                                    if(i.urgency === 'Caution') return '#ffc107';
                                    return '#10b981';
                                }),
                                borderWidth: 0,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1f2937',
                                    padding: 12,
                                    titleFont: { size: 14, weight: 'bold' },
                                    bodyFont: { size: 13 }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: { color: '#e5e7eb' },
                                    ticks: { font: { size: 12 } }
                                },
                                y: {
                                    grid: { display: false },
                                    ticks: { font: { size: 13, weight: '500' } }
                                }
                            }
                        }
                    });
                }
                
                if(data.categoryDistribution) {
                    new Chart(document.getElementById('distributionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(data.categoryDistribution),
                            datasets: [{
                                data: Object.values(data.categoryDistribution),
                                backgroundColor: [
                                    '#6366f1', // Indigo
                                    '#0ea5e9', // Sky
                                    '#10b981', // Emerald
                                    '#f59e0b', // Amber
                                    '#ef4444', // Red
                                    '#ec4899', // Pink
                                    '#8b5cf6', // Violet
                                    '#64748b'  // Slate
                                ],
                                borderWidth: 0,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        padding: 20,
                                        font: { size: 12 }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#1f2937',
                                    padding: 12,
                                    titleFont: { size: 14, weight: 'bold' },
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return ` ${context.label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            },
                            cutout: '60%'
                        }
                    });
                }
                
                if(data.inventoryValue) {
                    const canvas = document.getElementById('valueChart');
                    const chartWrapper = canvas.parentElement;
                    const itemsCount = Object.keys(data.inventoryValue).length;
                    // Dynamic height: 35px per category
                    const height = Math.max(300, itemsCount * 35);
                    chartWrapper.style.height = height + 'px';
                    canvas.height = height;

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(data.inventoryValue),
                            datasets: [{
                                label: 'Value (₱)',
                                data: Object.values(data.inventoryValue),
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                                barThickness: 20
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1f2937',
                                    callbacks: {
                                        label: function(c) {
                                            return ' ₱' + c.parsed.x.toLocaleString(undefined, {minimumFractionDigits: 2});
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: '#f3f4f6' },
                                    ticks: {
                                        callback: function(value) { return '₱' + value.toLocaleString(); }
                                    }
                                },
                                y: {
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
                
                if(data.employeeRequisitions && data.employeeRequisitions.length > 0) {
                    const canvas = document.getElementById('trendChart');
                    const chartWrapper = canvas.parentElement;
                    // Dynamic height: 35px per employee
                    const height = Math.max(400, data.employeeRequisitions.length * 35);
                    chartWrapper.style.height = height + 'px';
                    canvas.height = height;

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.employeeRequisitions.map(e => e.first_name + ' ' + e.last_name),
                            datasets: [{
                                label: 'Requisitions',
                                data: data.employeeRequisitions.map(e => e.requisition_count),
                                backgroundColor: '#8b5cf6',
                                borderRadius: 4,
                                barThickness: 20
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { backgroundColor: '#1f2937' }
                            },
                            scales: {
                                x: {
                                    grid: { color: '#f3f4f6' },
                                    beginAtZero: true
                                },
                                y: {
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
                
                if(data.turnover && data.turnover.length > 0) {
                    const canvas = document.getElementById('turnoverChart');
                    const chartWrapper = canvas.parentElement;
                    // Dynamic height: 35px per item
                    const height = Math.max(500, data.turnover.length * 35);
                    chartWrapper.style.height = height + 'px';
                    canvas.height = height;
                    
                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.turnover.map(t => t.name),
                            datasets: [
                                {
                                    label: 'Issued',
                                    data: data.turnover.map(t => t.issued),
                                    backgroundColor: '#ec4899',
                                    borderWidth: 0,
                                    borderRadius: 5
                                },
                                {
                                    label: 'In Stock',
                                    data: data.turnover.map(t => t.stock),
                                    backgroundColor: '#64748b',
                                    borderWidth: 0,
                                    borderRadius: 5
                                }
                            ]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        font: { size: 13, weight: '500' },
                                        padding: 15,
                                        usePointStyle: true,
                                        pointStyle: 'circle'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#1f2937',
                                    padding: 12,
                                    titleFont: { size: 14, weight: 'bold' },
                                    bodyFont: { size: 13 }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: false,
                                    beginAtZero: true,
                                    grid: { color: '#e5e7eb' },
                                    ticks: { font: { size: 12 } }
                                },
                                y: {
                                    stacked: false,
                                    grid: { display: false },
                                    ticks: { font: { size: 12, weight: '500' } }
                                }
                            }
                        }
                    });
                }
                
                if(data.lowStockUrgency) {
                    new Chart(document.getElementById('urgencyChart'), {
                        type: 'polarArea',
                        data: {
                            labels: ['Out of Stock', 'Critical', 'Caution'],
                            datasets: [{
                                data: [
                                    data.lowStockUrgency.out || 0,
                                    data.lowStockUrgency.critical || 0,
                                    data.lowStockUrgency.caution || 0
                                ],
                                backgroundColor: [
                                    'rgba(239, 68, 68, 0.8)',   // Red
                                    'rgba(249, 115, 22, 0.8)',  // Orange
                                    'rgba(245, 158, 11, 0.8)'   // Amber
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { usePointStyle: true, padding: 20 }
                                }
                            },
                            scales: {
                                r: {
                                    ticks: { display: false },
                                    grid: { color: '#e5e7eb' }
                                }
                            }
                        }
                    });
                }
                
                console.log('All charts rendered successfully');
            })
            .catch(err => {
                console.error('Chart error:', err);
            });
    });
    </script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
</body>
</html>
