<?php
// Handle dynamic base path
$root = $base_path ?? './';

// Get the current navigation path from the router or server
$current_nav = $currentRoute ?? '/' . basename($_SERVER['PHP_SELF']);
$current_nav = '/' . trim(str_replace('.php', '', $current_nav), '/');
if (empty($current_nav) || $current_nav == '/') $current_nav = 'home';
?>
<nav class="navbar">
    <div class="container nav-container">
        <a href="<?php echo $root; ?>" class="logo" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
            <div style="width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <img src="<?php echo $root; ?>images/DepED-Logo.png" alt="DepED Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <span style="color: white; font-weight: 700; font-size: 1.5rem; letter-spacing: -0.5px; text-transform: uppercase;">INVENTORY <span style="color: #6ee7b7">MANAGEMENT</span></span>
        </a>
        <ul class="nav-links">
            <li><a href="<?php echo $root; ?>" <?php echo ($current_nav == 'home' || $current_nav == 'index') ? 'class="active"' : ''; ?>>Home</a></li>
            <?php if (isset($_SESSION['admin_id'])): ?>
                <li><a href="<?php echo $root; ?>dashboard" <?php echo $current_nav == 'dashboard' ? 'class="active"' : ''; ?>>Dashboard</a></li>
                <li><a href="<?php echo $root; ?>logout" class="btn-logout" style="background: #ef5350; padding: 8px 16px; border-radius: 4px; color: white;" onclick="showLogoutModal(event, this.href);">Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo $root; ?>supplies" <?php echo $current_nav == 'supplies' ? 'class="active"' : ''; ?>>Supplies</a></li>
                <?php if ($current_nav != 'login'): ?>
                    <li><a href="<?php echo $root; ?>login">Admin Login</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        <div class="mobile-menu-btn" style="display: none;">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<style>
    .navbar {
        background: rgba(2, 44, 34, 0.8);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 1.25rem 0;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 100000;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
    }
    
    /* Scrolled state for navbar */
    .navbar.scrolled {
        padding: 1rem 0;
        background: rgba(2, 44, 34, 0.95);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .nav-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 40px;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 3rem;
        align-items: center;
        margin: 0;
        padding: 0;
    }

    .nav-links a {
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        letter-spacing: 0.01em;
    }

    .nav-links a:hover, .nav-links a.active {
        color: white;
    }
    
    .nav-links a.active {
        position: relative;
    }
    
    .nav-links a.active::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 100%;
        height: 2px;
        background: #6ee7b7;
        border-radius: 2px;
    }

    .btn-logout {
        background: #ef4444 !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        transition: all 0.3s ease !important;
    }
    
    .btn-logout:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        opacity: 1 !important;
    }

    @media (max-width: 768px) {
        .navbar { padding: 1rem 0; }
        .nav-container { padding: 0 20px; }
        .logo span { font-size: 1.1rem !important; }
        .nav-links { gap: 1.5rem; }
    }

    @media (max-width: 480px) {
        .logo span {
            font-size: 0.85rem !important;
            letter-spacing: -0.8px;
        }
        .logo span span {
            display: none; /* Hide "MANAGEMENT" on very small screens to fit */
        }
        .nav-links {
            gap: 0.6rem;
        }
        .nav-links a {
            font-size: 0.8rem;
        }
        .logo div {
            width: 25px !important;
            height: 25px !important;
            padding: 2px !important;
        }
    }
</style>

<script>
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.navbar');
        if (window.scrollY > 20) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });
</script>
