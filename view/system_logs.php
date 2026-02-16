<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();

    require_once __DIR__ . '/../model/SystemLogModel.php';
    $logModel = new SystemLogModel();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $logs = $logModel->getLogs($limit, $offset);
    $totalLogs = $logModel->getTotalCount();
    $totalPages = ceil($totalLogs / $limit);

    require_once __DIR__ . '/../model/requisitionModel.php';
    $reqModelNotify = new RequisitionModel();
    $pendingStats = $reqModelNotify->getRequisitionStats();
    $pendingCount = $pendingStats['pending'] ?? 0;
} catch (Throwable $e) {
    die("<h1>Error Loading System Logs</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p><p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
}
?>
<?php 
// Robust root calculation
$serverPath = str_replace('\\', '/', dirname(__DIR__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$scriptDir = str_replace($docRoot, '', $serverPath);
$root = rtrim($scriptDir, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Inventory System</title>
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
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <style>
        .log-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 40px;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .log-table th, .log-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f1f3f5;
        }
        .log-table th {
            background: #f8f9fa;
            font-weight: 700;
            color: #2A4D88;
        }
        .log-table tr:hover {
            background: #fdfdfd;
        }
        .action-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .action-login { background: #e6f4ea; color: #1e8e3e; }
        .action-logout { background: #fce8e6; color: #d93025; }
        .action-create { background: #e8f0fe; color: #1a73e8; }
        .action-update { background: #fef7e0; color: #b06000; }
        .action-delete { background: #fce8e6; color: #d93025; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        .pagination a {
            padding: 8px 16px;
            border-radius: 8px;
            background: white;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: #2A4D88;
            font-weight: 600;
            transition: all 0.2s;
        }
        .pagination a.active {
            background: #2A4D88;
            color: white;
            border-color: #2A4D88;
        }
        .pagination a:hover:not(.active) {
            background: #f8fafc;
        }
    </style>
</head>
    <div class="log-container">
        <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <a href="<?php echo $root; ?>settings" style="color: #10b981; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Settings
                </a>
                <h1 style="color: #022c22; font-size: 1.8rem;">System Activity Logs</h1>
            </div>
            <!-- Notification area removed -->
        </div>

        <table class="log-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #95a5a6;">No logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $actionClass = 'action-' . strtolower(explode('_', $log['action'])[0]);
                        if (!in_array($actionClass, ['action-login', 'action-logout', 'action-create', 'action-update', 'action-delete'])) {
                            $actionClass = 'action-update'; // Default
                        }
                    ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['username'] ?? 'System/Deleted'); ?></strong>
                            </td>
                            <td>
                                <span class="action-badge <?php echo $actionClass; ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td style="color: #6c757d; font-family: monospace;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
</body>
</html>
