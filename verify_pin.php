<?php
require_once __DIR__ . '/includes/security.php';
initSecureSession();
require_once __DIR__ . '/db/database.php';

$db = new Database();
$pdo = $db->getConnection();

$error = '';
$email = $_SESSION['recovery_email'] ?? '';

if (empty($email)) {
    header("Location: forgot_password");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
        $pin = implode('', $_POST['pin'] ?? []);

        if (strlen($pin) !== 6) {
            $error = "Please enter the full 6-digit PIN.";
        } else {
            try {
                // Check PIN validity (most recent for this email)
                $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email, $pin, date('Y-m-d H:i:s')]);
                $isValid = $stmt->fetch();

                if ($isValid) {
                    // Generate a one-time reset token for the final step
                    $resetToken = bin2hex(random_bytes(32));
                    
                    // Update the record with this new token (re-using the table for simplicity)
                    $stmt = $pdo->prepare("UPDATE password_resets SET token = ?, expires_at = ? WHERE id = ?");
                    $stmt->execute([$resetToken, date('Y-m-d H:i:s', strtotime('+30 minutes')), $isValid['id']]);
                    
                    logSecurityEvent("PIN verified successfully for email: $email", "INFO");
                    header("Location: reset_password?token=" . $resetToken);
                    exit;
                } else {
                    $error = "Invalid or expired PIN. Please check your email or request a new one.";
                    logSecurityEvent("Invalid PIN entry for email: $email", "WARNING");
                }
            } catch (PDOException $e) {
                $error = "A system error occurred. Please try again later.";
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
    <title>Verify PIN - Inventory System</title>
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

        .verify-wrapper {
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

        .verify-card {
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

        .pin-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 30px;
        }

        .pin-input {
            width: 50px;
            height: 65px;
            background: rgba(2, 44, 34, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1.8rem;
            font-weight: 800;
            text-align: center;
            transition: all 0.3s ease;
        }

        .pin-input:focus {
            outline: none;
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
            transform: translateY(-2px);
        }

        .btn-verify {
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

        .btn-verify:hover {
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

        .footer-links {
            margin-top: 30px;
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .resend-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .resend-link:hover {
            color: white;
            text-decoration: underline;
        }

        .email-display {
            color: #10b981;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="verify-wrapper">
        <div class="hero-backdrop">
            <div class="floating-orb orb-1"></div>
            <div class="floating-orb orb-2"></div>
        </div>

        <div class="verify-card">
            <div class="card-header">
                <div class="icon-wrapper">
                    <i class="fas fa-user-check"></i>
                    <div class="icon-glow"></div>
                </div>
                <h2>Verify Code</h2>
                <p>We've sent a 6-digit PIN to <br><span class="email-display"><?php echo htmlspecialchars($email); ?></span></p>
            </div>

            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert-banner alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="verifyForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="pin-container">
                        <input type="text" name="pin[]" class="pin-input" maxlength="1" pattern="\d*" inputmode="numeric" required autofocus>
                        <input type="text" name="pin[]" class="pin-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                        <input type="text" name="pin[]" class="pin-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                        <input type="text" name="pin[]" class="pin-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                        <input type="text" name="pin[]" class="pin-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                        <input type="text" name="pin[]" class="pin-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                    </div>

                    <button type="submit" class="btn-verify">
                        <span>Verify PIN</span>
                        <i class="fas fa-check-double"></i>
                    </button>
                </form>

                <div class="footer-links">
                    <p style="color: #64748b; margin-bottom: 10px;">Didn't receive the code?</p>
                    <a href="forgot_password" class="resend-link">
                        <i class="fas fa-redo"></i> Resend Code
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.pin-input');
        
        inputs.forEach((input, index) => {
            // Handle number only and auto-focus next
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                if (!/^\d$/.test(value)) {
                    e.target.value = '';
                    return;
                }
                
                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                
                // Auto submit if all filled
                const allFilled = Array.from(inputs).every(i => i.value);
                if (allFilled) {
                    // Slight delay for visual satisfaction
                    setTimeout(() => {
                        // document.getElementById('verifyForm').submit();
                    }, 500);
                }
            });

            // Handle backspace
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            // Handle paste
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').slice(0, 6);
                if (!/^\d+$/.test(pasteData)) return;
                
                const digits = pasteData.split('');
                digits.forEach((digit, i) => {
                    if (index + i < inputs.length) {
                        inputs[index + i].value = digit;
                    }
                });
                
                const nextToFocus = index + digits.length;
                if (nextToFocus < inputs.length) {
                    inputs[nextToFocus].focus();
                } else {
                    inputs[inputs.length - 1].focus();
                }
            });
        });
    </script>
</body>
</html>
