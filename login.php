<?php
require_once __DIR__ . '/includes/security.php';
initSecureSession();
require_once __DIR__ . '/db/database.php';

$db = new Database();
$pdo = $db->getConnection();

// If already logged in, redirect to dashboard
if (isAuthenticated()) {
    header("Location: dashboard");
    exit;
}

$error = '';
$showForm = true;

// On page load, check if user is already locked out from a previous login attempt
$ip = $_SERVER['REMOTE_ADDR'];
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'rate_limit_') === 0 && isset($value['attempts'])) {
        // Construct an identifier to check using the existing md5 key logic if needed, 
        // but we can just check if the current session has any rate_limit_ keys that are currently active.
        // The checkRateLimit uses md5($identifier), so we'll just check if THIS session is limited.
        
        // Extract username from session key if we can't reconstruct identifier easily?
        // Actually, we can just check if the current user is rate limited by iterating and checking wait times.
        
        $attempts = $value['attempts'];
        $lastAttempt = $value['last_attempt'] ?? 0;
        
        $wait = 0;
        if ($attempts >= 15) $wait = 900;
        elseif ($attempts >= 10) $wait = 60;
        elseif ($attempts >= 5) $wait = 30;

        $elapsed = time() - $lastAttempt;
        if ($wait > 0 && $elapsed < $wait) {
            $remaining = $wait - $elapsed;
            $waitMsg = ($remaining >= 60) ? ceil($remaining / 60) . " minutes" : $remaining . " seconds";
            $error = "Too many failed login attempts. Access is temporarily suspended. Please try again in $waitMsg.";
            $showForm = false;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            $identifier = $_SERVER['REMOTE_ADDR'] . '_' . $username;
            
            if (!checkRateLimit($identifier)) {
                $remaining = getRateLimitWaitTime($identifier);
                $waitMsg = ($remaining >= 60) ? ceil($remaining / 60) . " minutes" : $remaining . " seconds";
                $error = "Too many failed login attempts. Please try again in $waitMsg.";
                $showForm = false;
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Using SHA256 as requested by the user
                    $hashedPassword = hash('sha256', $password);

                    if ($admin && $hashedPassword === $admin['password']) {
                        regenerateSessionId();
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        setSessionFingerprint();
                        resetRateLimit($identifier);
                        logSecurityEvent("Successful login for user: $username", "INFO");
                        
                        // Database Logging
                        require_once __DIR__ . '/model/SystemLogModel.php';
                        $logModel = new SystemLogModel();
                        $logModel->log("LOGIN", "Administrator $username logged in successfully.");

                        header("Location: dashboard");
                        exit;
                    } else {
                        $error = "Invalid username or password.";
                        logSecurityEvent("Failed login attempt for username: $username", "WARNING");
                    }
                } catch (PDOException $e) {
                    $error = "System error. Please try again later.";
                    logSecurityEvent("Database error during login: " . $e->getMessage(), "ERROR");
                }
            }
        }
    }
}
?>

<?php $root = $base_path ?? './'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="login-wrapper">
    <!-- Animated Background -->
    <div class="hero-backdrop">
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
    </div>

    <div class="login-content container">
        <div class="login-card-premium">
            <div class="login-header-premium">
                <div class="security-icon-wrapper">
                    <i class="fas fa-shield-alt"></i>
                    <div class="icon-glow"></div>
                </div>
                <h2>Admin Login</h2>
                <p>Secure Access Portal for Inventory Management</p>
            </div>

            <div class="login-body-premium">
                <?php if ($error): ?>
                    <div class="error-banner">
                        <i class="fas fa-exclamation-circle"></i> 
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($showForm): ?>
                    <form method="POST" class="premium-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="premium-input-group">
                            <label>Username</label>
                            <div class="input-field-wrapper">
                                <i class="fas fa-user field-icon"></i>
                                <input type="text" name="username" class="premium-input" 
                                       required placeholder="Enter your username" 
                                       maxlength="50" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>
                        </div>

                        <div class="premium-input-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <label style="margin-bottom: 0;">Password</label>
                                <a href="forgot_password" class="forgot-password-link">Forgot Password?</a>
                            </div>
                            <div class="input-field-wrapper">
                                <i class="fas fa-lock field-icon"></i>
                                <input type="password" name="password" id="password" class="premium-input" 
                                       required placeholder="Enter your password">
                                <i class="fas fa-eye password-toggle-btn" id="togglePasswordBtn" title="Show/Hide Password"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-login-premium">
                            <span>Sign In</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="suspended-view">
                        <div class="suspended-icon"><i class="fas fa-clock"></i></div>
                        <h3>Access Temporarily Suspended</h3>
                        <p>Too many failed attempts. Please protect your account security and try again later.</p>
                    </div>
                <?php endif; ?>
                
                <div class="login-footer-links">
                    <a href="<?php echo $root; ?>" class="back-link-premium">
                        <i class="fas fa-arrow-left"></i>
                        <span>Return to Main Site</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: #022c22 !important;
        margin: 0;
        padding: 0;
    }

    .login-wrapper {
        min-height: 80vh;
        background: var(--gradient-hero);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        padding: 100px 0 60px;
        font-family: 'Outfit', sans-serif;
    }

    /* Backdrop - Shared with index.php */
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

    .login-content {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 500px;
        padding: 20px;
    }

    .login-card-premium {
        background: rgba(2, 44, 34, 0.7);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 40px 100px rgba(0, 0, 0, 0.3);
        animation: cardAppear 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes cardAppear {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .login-header-premium {
        padding: 50px 40px 30px;
        text-align: center;
        background: rgba(255, 255, 255, 0.02);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .security-icon-wrapper {
        position: relative;
        width: 90px;
        height: 90px;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        color: #34d399;
        font-size: 2.5rem;
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

    .login-header-premium h2 {
        color: white;
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -0.02em;
    }

    .login-header-premium p {
        color: #94a3b8;
        font-size: 0.95rem;
    }

    .login-body-premium {
        padding: 40px;
    }

    .premium-input-group {
        margin-bottom: 24px;
    }

    .premium-input-group label {
        display: block;
        color: #cbd5e1;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .input-field-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .field-icon {
        position: absolute;
        left: 20px;
        color: #64748b;
        font-size: 1.1rem;
        transition: all 0.3s ease;
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
        font-family: 'Inter', sans-serif;
    }

    .premium-input:focus {
        outline: none;
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.05);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
    }

    .premium-input:focus + .field-icon {
        color: #10b981;
    }

    .password-toggle-btn {
        position: absolute;
        right: 20px;
        color: #64748b;
        cursor: pointer;
        padding: 5px;
        transition: color 0.3s;
    }

    .password-toggle-btn:hover {
        color: white;
    }

    .btn-login-premium {
        width: 100%;
        margin-top: 10px;
        padding: 18px;
        background: var(--gradient-primary);
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

    .btn-login-premium:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(16, 185, 129, 0.5);
    }

    .btn-login-premium:active {
        transform: translateY(0);
    }

    .error-banner {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .login-footer-links {
        margin-top: 30px;
        text-align: center;
        padding-top: 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .back-link-premium {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .back-link-premium:hover {
        color: white;
        transform: translateX(-5px);
    }

    .forgot-password-link {
        color: #10b981;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .forgot-password-link:hover {
        color: white;
        text-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
    }

    /* Suspended state */
    .suspended-view {
        text-align: center;
        padding: 20px 0;
    }

    .suspended-icon {
        font-size: 3.5rem;
        color: #ef4444;
        margin-bottom: 20px;
    }

    .suspended-view h3 {
        color: white;
        font-size: 1.4rem;
        margin-bottom: 12px;
    }

    .suspended-view p {
        color: #94a3b8;
        line-height: 1.6;
    }

    @media (max-width: 480px) {
        .login-card-premium { border-radius: 0; }
        .login-wrapper { background: #022c22; }
        .login-header-premium { padding: 40px 20px 20px; }
        .login-body-premium { padding: 30px 20px; }
    }
</style>

<script>
    const togglePasswordBtn = document.querySelector('#togglePasswordBtn');
    const passwordInput = document.querySelector('#password');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            
            // Re-center icon if needed or add visual feedback
            this.style.color = type === 'text' ? '#10b981' : '#64748b';
        });
    }

    // Add subtle focus animation for the whole card
    const inputs = document.querySelectorAll('.premium-input');
    const card = document.querySelector('.login-card-premium');
    
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            card.style.borderColor = 'rgba(59, 130, 246, 0.3)';
            card.style.boxShadow = '0 40px 100px rgba(59, 130, 246, 0.15)';
        });
        
        input.addEventListener('blur', () => {
            card.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            card.style.boxShadow = '0 40px 100px rgba(0, 0, 0, 0.3)';
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
