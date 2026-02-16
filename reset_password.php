<?php
require_once __DIR__ . '/includes/security.php';
initSecureSession();
require_once __DIR__ . '/db/database.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$isValidToken = false;
$email = '';

if (empty($token)) {
    $error = "Invalid reset token. Please request a new PIN.";
} else {
    try {
        // Validate token
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1");
        $stmt->execute([$token, date('Y-m-d H:i:s')]);
        $resetRequest = $stmt->fetch();

        if ($resetRequest) {
            $isValidToken = true;
            $email = $resetRequest['email'];
        } else {
            $error = "This reset link or PIN has expired. Please request a new one.";
        }
    } catch (PDOException $e) {
        $error = "A system error occurred. Please try again later.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            try {
                // Update Admin Password (SHA256 as used in login.php)
                $hashed = hash('sha256', $newPassword);
                $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE email = ?");
                $stmt->execute([$hashed, $email]);

                // Delete all reset tokens for this email
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$email]);

                // Clear recovery session
                unset($_SESSION['recovery_email']);

                $success = "Your password has been successfully reset! You can now log in with your new password.";
                $isValidToken = false; // Hide form on success
                
                logSecurityEvent("Password successfully reset for email: $email", "INFO");
            } catch (PDOException $e) {
                $error = "An error occurred while updating your password. Please try again.";
            }
        }
    }
}
?>

<?php 
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
    <title>Reset Password - Inventory System</title>
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

        .reset-wrapper {
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

        .reset-card {
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

        .btn-reset {
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

        .btn-reset:hover {
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

        .footer-links {
            margin-top: 30px;
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .btn-login {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="reset-wrapper">
        <div class="hero-backdrop">
            <div class="floating-orb orb-1"></div>
            <div class="floating-orb orb-2"></div>
        </div>

        <div class="reset-card">
            <div class="card-header">
                <div class="icon-wrapper">
                    <i class="fas fa-shield-alt"></i>
                    <div class="icon-glow"></div>
                </div>
                <h2>Reset Password</h2>
                <?php if ($isValidToken): ?>
                    <p>Enter a new strong password for your account.</p>
                <?php endif; ?>
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
                    
                    <div class="footer-links">
                        <a href="login" class="btn-login">
                            Go to Login Page
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($isValidToken): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="input-group">
                            <label>New Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock field-icon"></i>
                                <input type="password" name="password" class="premium-input" 
                                       required placeholder="Min. 8 characters" minlength="8">
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Confirm Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-check-double field-icon"></i>
                                <input type="password" name="confirm_password" class="premium-input" 
                                       required placeholder="Repeat your new password">
                            </div>
                        </div>

                        <button type="submit" class="btn-reset">
                            <span>Update Password</span>
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </form>
                <?php elseif (!$success && $error && (strpos($error, 'expired') !== false || strpos($error, 'Invalid') !== false)): ?>
                    <div class="footer-links" style="border-top: none; padding-top: 0;">
                        <a href="forgot_password" class="btn-login">
                            Request New Link
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <div class="footer-links">
                    <a href="login" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">
                        Back to Login
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
