<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../../includes/security.php';
    initSecureSession();
    requireAuth();

    require_once __DIR__ . '/../../model/requisitionModel.php';
    $reqModel = new RequisitionModel();
    $reqStats = $reqModel->getRequisitionStats();
    $pendingCount = $reqStats['pending'] ?? 0;
} catch (Throwable $e) {
    die("<h1>Error Loading Controlled Assets</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>");
}

// Root path calculation
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir);
// If we are in view/controlled_assests/ index.php, we need to go up 2 levels
if (basename($scriptDir) === 'controlled_assets') {
    $scriptDir = dirname(dirname($scriptDir));
}
$root = rtrim($scriptDir, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controlled Assets - Inventory System</title>
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
        .asset-hero {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(30, 58, 138, 0.2);
            position: relative;
            overflow: hidden;
        }
        .asset-hero::after {
            content: "\f507";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 12rem;
            opacity: 0.1;
            transform: rotate(-15deg);
        }
        .asset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .asset-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 15px;
        }
        .asset-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            background: white;
        }
        .asset-card i {
            font-size: 2.5rem;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        .asset-card h3 {
            margin: 0;
            color: #1e293b;
            font-size: 1.25rem;
        }
        .asset-card p {
            color: #64748b;
            font-size: 0.9rem;
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
            <li><a href="<?php echo $root; ?>controlled_assets" class="active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo $root; ?>controlled_assets/deliveries"><i class="fas fa-box"></i> <span>Deliveries</span></a></li>
            <li><a href="<?php echo $root; ?>controlled_assets/reports"><i class="fas fa-file-alt"></i> <span>Reports</span></a></li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>inventory" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-arrow-left"></i> <span>Back to Inventory</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Controlled Assets</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/../includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>

        <div class="asset-hero">
            <h2 style="margin: 0 0 10px 0; font-size: 2rem;">Controlled Assets Management</h2>
            <p style="margin: 0; opacity: 0.9; font-size: 1.1rem; max-width: 600px;">Track, manage, and monitor high-value or restricted items with professional delivery and status monitoring workflows.</p>
        </div>

        <div class="asset-grid">
            <div class="asset-card">
                <i class="fas fa-laptop"></i>
                <h3>IT Equipment</h3>
                <p>Laptops, tablets, and specialized hardware assets.</p>
            </div>
            <div class="asset-card">
                <i class="fas fa-tools"></i>
                <h3>Specialized Tools</h3>
                <p>Maintenance and educational technical equipment.</p>
            </div>
            <div class="asset-card">
                <i class="fas fa-video"></i>
                <h3>AV Equipment</h3>
                <p>Projectors, cameras, and audio systems.</p>
            </div>
        </div>
    </div>
    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <?php include_once __DIR__ . '/../../includes/logout_modal.php'; ?>
</body>
</html>
