<?php
/**
 * Security Utilities
 * Provides CSRF protection, input sanitization, rate limiting, and session security
 */

// ============================================
// CSRF PROTECTION
// ============================================

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// INPUT SANITIZATION
// ============================================

function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

function validateStringLength($str, $minLength = 1, $maxLength = 255) {
    $len = strlen($str);
    return $len >= $minLength && $len <= $maxLength;
}

// ============================================
// RATE LIMITING
// ============================================

function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'last_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    $attempts = $data['attempts'];
    $lastAttempt = $data['last_attempt'];
    
    // Tiered wait logic
    $wait = 0;
    if ($attempts >= 15) $wait = 900;      // 15 minutes
    elseif ($attempts >= 10) $wait = 60;   // 1 minute
    elseif ($attempts >= 5) $wait = 30;    // 30 seconds

    if ($wait > 0 && (time() - $lastAttempt) < $wait) {
        return false;
    }

    // If enough time has passed since the last attempt/lockout started, allow next attempt
    // If it's been more than 15 minutes since the last attempt, reset to tier 1
    if ((time() - $lastAttempt) > 900) {
        $_SESSION[$key]['attempts'] = 1;
    } else {
        $_SESSION[$key]['attempts']++;
    }
    
    $_SESSION[$key]['last_attempt'] = time();
    return true;
}

function resetRateLimit($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
}

function getRateLimitWaitTime($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $data = $_SESSION[$key];
    $attempts = $data['attempts'];
    $lastAttempt = $data['last_attempt'];
    
    $wait = 0;
    if ($attempts >= 15) $wait = 900;
    elseif ($attempts >= 10) $wait = 60;
    elseif ($attempts >= 5) $wait = 30;
    
    $elapsed = time() - $lastAttempt;
    return max(0, $wait - $elapsed);
}

function isRateLimited($identifier) {
    $key = 'rate_limit_' . md5($identifier);
    if (!isset($_SESSION[$key])) return false;

    $data = $_SESSION[$key];
    $attempts = $data['attempts'];
    $lastAttempt = $data['last_attempt'];

    $wait = 0;
    if ($attempts >= 15) $wait = 900;
    elseif ($attempts >= 10) $wait = 60;
    elseif ($attempts >= 5) $wait = 30;

    return $wait > 0 && (time() - $lastAttempt) < $wait;
}

// ============================================
// SESSION SECURITY
// ============================================

function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters before starting
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

function validateSession() {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > 3600) { // 1 hour
            return false;
        }
    }
    
    if (isset($_SESSION['user_agent'])) {
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            return false;
        }
    }
    
    $_SESSION['login_time'] = time();
    return true;
}

function destroySession() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

function regenerateSessionId() {
    session_regenerate_id(true);
}

function setSessionFingerprint() {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['login_time'] = time();
}

// ============================================
// ERROR LOGGING
// ============================================

function logSecurityEvent($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $logMessage = "[$timestamp] [$level] [IP: $ip] $message" . PHP_EOL;
    
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/security.log';
    @error_log($logMessage, 3, $logFile);
}

// ============================================
// AUTHENTICATION HELPERS
// ============================================

function isAuthenticated() {
    return isset($_SESSION['admin_id']) && validateSession();
}

function requireAuth($redirectUrl = 'login') {
    if (!isAuthenticated()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Hash password using SHA256 as requested
 */
function hashPassword($password) {
    return hash('sha256', $password);
}

/**
 * Verify password using SHA256
 */
function verifyPassword($password, $hash) {
    return hash('sha256', $password) === $hash;
}
?>
