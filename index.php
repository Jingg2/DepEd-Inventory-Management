<?php
/**
 * Front Controller & Router - AI Arena
 * Centralized entry point for the Inventory Management System
 * Hardened to handle double-encoded spaces and inconsistent environment paths
 */

require_once __DIR__ . '/includes/security.php';
initSecureSession();

// Get the requested URI
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Part 1: Robust Decoding
// Browser/Server combinations can double-encode spaces (%20 -> %2520)
$decodedPath = $path;
do {
    $lastPath = $decodedPath;
    $decodedPath = urldecode($decodedPath);
} while ($decodedPath !== $lastPath);

// Part 2: Agnostic Base Path Detection
$scriptDir = urldecode(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])));
$scriptDir = rtrim($scriptDir, '/') . '/'; 
$base_path = $scriptDir; // Global for components to reference assets

$route = '/';
if (strpos($decodedPath, $scriptDir) === 0) {
    $route = '/' . trim(substr($decodedPath, strlen($scriptDir)), '/');
} else {
    $parts = explode('/Inventory_System/', $decodedPath);
    if (count($parts) > 1) {
        $route = '/' . trim($parts[1], '/');
    }
}

$currentRoute = $route; // Export for navbar active state

// Logic for routing
$routes = [
    '/' => 'home.php',
    '/home' => 'home.php',
    '/index' => 'home.php',
    '/login' => 'login.php',
    '/supplies' => 'supplies.php',
    '/logout' => 'logout.php',
    
    // View Directory routes (Admin only)
    '/dashboard' => 'view/dashboard.php',
    '/inventory' => 'supply.php',
    '/requests' => 'view/request.php',
    '/employees' => 'view/employee.php',
    '/reports' => 'view/reports.php',
    '/settings' => 'view/settings.php',
    '/help_center' => 'view/help_center.php',
    '/system_logs' => 'view/system_logs.php',
    '/controlled_assets' => 'view/controlled_assets/index.php',
    '/controlled_assets/deliveries' => 'view/controlled_assets/deliveries.php',
    '/controlled_assets/school_items' => 'view/controlled_assets/school_items.php',
    '/controlled_assets/reports' => 'view/controlled_assets/reports.php',
    
    // Password Recovery Routes
    '/forgot_password' => 'forgot_password.php',
    '/verify_pin' => 'verify_pin.php',
    '/reset_password' => 'reset_password.php'
];

// Part 3: Execution
if (array_key_exists($route, $routes)) {
    // Security check for view directory routes
    if (strpos($routes[$route], 'view/') === 0) {
        if (!isAuthenticated()) {
            header("Location: " . $scriptDir . "login");
            exit;
        }
    }
    
    $targetFile = __DIR__ . '/' . $routes[$route];
    if (file_exists($targetFile)) {
        require_once $targetFile;
    } else {
        include __DIR__ . '/404.php';
    }
} else {
    include __DIR__ . '/404.php';
}
