<?php
// filepath: c:\xampp\htdocs\OJT DEVELOPMENT\Inventory_System\api\backup.php
require_once __DIR__ . '/../includes/security.php';
initSecureSession();
requireAuth();

require_once __DIR__ . '/../model/BackupModel.php';
require_once __DIR__ . '/../model/SystemLogModel.php';

$backupModel = new BackupModel();
$logModel = new SystemLogModel();

$sql = $backupModel->generateBackup();

// Filename with timestamp
$filename = 'inventory_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Log the action
$logModel->log("DATABASE_BACKUP", "Generated and downloaded a database backup file.");

// Set headers for download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($sql));

echo $sql;
exit();
