<?php
require_once __DIR__ . '/includes/security.php';
initSecureSession();
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/mail.php';

$db = new Database();
$pdo = $db->getConnection();

// Self-setup: Create table if not exists (for local dev convenience)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Silently continue if it fails (driver issues etc)
}

$error = '';
$success = '';
$simulatedLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $error = "Please enter your email address.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if email exists in admin table
            try {
                $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();

                if ($admin) {
                    $pin = sprintf("%06d", mt_rand(0, 999999));
                    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                    // Store PIN
                    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$email, $pin, $expires]);

                    // Store email in session for verification page
                    $_SESSION['recovery_email'] = $email;
                    
                    // Attempt to send actual email
                    sendRecoveryPIN($email, $pin);
                    
                    logSecurityEvent("Password recovery PIN generated for email: $email", "INFO");
                    
                    // Direct redirect to verification page
                    header("Location: verify_pin");
                    exit;
                } else {
                    // Standard practice: don't reveal if email is registered
                    $success = "If this email is registered, you will receive a reset link shortly.";
                    logSecurityEvent("Password reset attempted for unregistered email: $email", "WARNING");
                }
            } catch (PDOException $e) {
                $error = "An error occurred. Please try again later.";
                error_log("Password Reset error: " . $e->getMessage());
            }
        }
    }
}
?>

<?php 
// Robust root path calculation
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir);
$root = rtrim($scriptDir, '/') . '/';
$urlRoot = str_replace(' ', '%20', $root);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Inventory System</title>
    <link rel="stylesheet" href="<?php echo $urlRoot; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: #022c22 !important;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        .recovery-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        .hero-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            background: #10b981;
            animation: floatOrb 20s infinite alternate ease-in-out;
        }

        .orb-1 { width: 500px; height: 500px; top: -150px; left: -100px; }
        .orb-2 { width: 400px; height: 400px; bottom: -100px; right: -50px; background: #34d399; }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(60px, 120px) scale(1.1); }
        }

        .recovery-card {
            background: rgba(2, 44, 34, 0.7);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 10;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: cardAppear 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-header {
            padding: 50px 40px 30px;
            text-align: center;
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .icon-wrapper {
            position: relative;
            width: 80px;
            height: 80px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: #34d399;
            font-size: 2.2rem;
        }

        .icon-glow {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: inherit;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .card-header h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .card-header p {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .card-body {
            padding: 40px;
        }

        .input-group {
            margin-bottom: 24px;
        }

        .input-group label {
            display: block;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .field-icon {
            position: absolute;
            left: 20px;
            color: #64748b;
            font-size: 1.1rem;
        }

        .premium-input {
            width: 100%;
            background: rgba(2, 44, 34, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.1);
            padding: 16px 20px 16px 52px;
            border-radius: 16px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .premium-input:focus {
            outline: none;
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.05);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
        }

        .btn-recovery {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-recovery:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.5);
        }

        .alert-banner {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #d1fae5;
        }

        .simulated-box {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px dashed rgba(16, 185, 129, 0.5);
        }

        .simulated-link {
            color: #34d399;
            word-break: break-all;
            font-size: 0.85rem;
            text-decoration: underline;
        }

        .footer-links {
            margin-top: 30px;
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .back-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: white;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="recovery-wrapper">
        <div class="hero-backdrop">
            <div class="floating-orb orb-1"></div>
            <div class="floating-orb orb-2"></div>
        </div>

        <div class="recovery-card">
            <div class="card-header">
                <div class="icon-wrapper">
                    <i class="fas fa-key"></i>
                    <div class="icon-glow"></div>
                </div>
                <h2>Recover Password</h2>
                <p>Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert-banner alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-banner alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!$success || ($success && $simulatedLink)): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="input-group">
                            <label>Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope field-icon"></i>
                                <input type="email" name="email" class="premium-input" 
                                       required placeholder="Enter your registered email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn-recovery">
                            <span>Send Reset Link</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                <?php endif; ?>

                <div class="footer-links">
                    <a href="login" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Login</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
