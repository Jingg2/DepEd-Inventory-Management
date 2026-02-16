<?php
require_once __DIR__ . '/includes/security.php';
initSecureSession();

// Database Logging
require_once __DIR__ . '/model/SystemLogModel.php';
$logModel = new SystemLogModel();
$logModel->log("LOGOUT", "Administrator " . ($_SESSION['admin_username'] ?? 'Unknown') . " logged out.");

destroySession();
header("Location: ./");
exit();
