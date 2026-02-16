<?php
require_once 'includes/security.php';
initSecureSession();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="main-wrapper">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-backdrop">
            <div class="floating-orb orb-1"></div>
            <div class="floating-orb orb-2"></div>
        </div>
        
        <div class="container hero-container">
            <div class="hero-content">
                <span class="badge-new">NEW: Version 2.0 is Live</span>
                <h1 class="hero-title">Master Your <span class="text-gradient">Inventory</span> Effortlessly</h1>
                <p class="hero-subtitle">
                    Streamline your workflow with DepEd's most advance inventory management system. 
                    Real-time tracking, intelligent reporting, and seamless requisition at your fingertips.
                </p>
                <div class="hero-actions">
                    <a href="supplies" class="btn-primary-premium">
                        <i class="fas fa-shopping-cart"></i>
                        Get Started
                    </a>
                    <a href="#features" class="btn-secondary-premium">
                        Learn More
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="hero-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <div class="stat-info">
                        <span class="stat-value">Instant</span>
                        <span class="stat-label">Stock Updates</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="stat-info">
                        <span class="stat-value">Secure</span>
                        <span class="stat-label">Data Management</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="features-backdrop">
            <div class="feature-orb orb-3"></div>
            <div class="feature-orb orb-4"></div>
        </div>
        
        <div class="container" style="position: relative; z-index: 5;">
            <div class="section-header">
                <h2 class="section-title">Designed for <span class="text-gradient">Excellence</span></h2>
                <p class="section-desc">Experience the power of professional supply management.</p>
            </div>
            
            <div class="feature-grid">
                <div class="feature-card-premium">
                    <div class="card-glow"></div>
                    <div class="feature-icon-wrapper-premium">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Real-time Tracking</h3>
                    <p>Monitor your stock levels dynamically with automated alerts and precision tracking.</p>
                </div>

                <div class="feature-card-premium">
                    <div class="card-glow"></div>
                    <div class="feature-icon-wrapper-premium">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Smart Requisitions</h3>
                    <p>Simplified multi-item requests with real-time status updates for all employees.</p>
                </div>

                <div class="feature-card-premium">
                    <div class="card-glow"></div>
                    <div class="feature-icon-wrapper-premium">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Predictive Alerts</h3>
                    <p>Always stay ahead of shortages with intelligent threshold-based notifications.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>

<style>
    :root {
        --text-gradient: linear-gradient(135deg, #34d399 0%, #10b981 100%);
    }

    body {
        background-color: #022c22 !important;
        margin: 0;
        padding: 0;
    }

    .main-wrapper {
        background: #022c22;
        min-height: 100vh;
        overflow-x: hidden;
        margin-bottom: 0 !important;
    }

    /* Hero Styling */
    .hero-section {
        position: relative;
        padding: 180px 0 120px;
        background: var(--gradient-hero);
        color: white;
        text-align: left;
        overflow: hidden;
    }

    .hero-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.15;
        background: #10b981;
        animation: floatOrb 20s infinite alternate ease-in-out;
    }

    .orb-1 { width: 400px; height: 400px; top: -100px; right: -50px; }
    .orb-2 { width: 300px; height: 300px; bottom: -50px; left: -50px; background: #34d399; }

    @keyframes floatOrb {
        0% { transform: translate(0, 0); }
        100% { transform: translate(50px, 100px); }
    }

    .hero-container {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 60px;
        align-items: center;
        position: relative;
        z-index: 10;
    }

    .badge-new {
        display: inline-block;
        padding: 6px 16px;
        background: rgba(16, 185, 129, 0.2);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: 100px;
        font-size: 14px;
        font-weight: 600;
        color: #6ee7b7;
        margin-bottom: 24px;
        animation: fadeIn 0.8s ease-out;
    }

    .hero-title {
        font-size: 4.5rem;
        line-height: 1.1;
        font-weight: 800;
        margin-bottom: 24px;
        letter-spacing: -0.04em;
    }

    .text-gradient {
        background: var(--text-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        line-height: 1.6;
        color: #94a3b8;
        max-width: 600px;
        margin-bottom: 40px;
    }

    .hero-actions {
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .btn-primary-premium {
        padding: 18px 36px;
        background: var(--gradient-primary);
        color: white;
        border-radius: 14px;
        font-weight: 700;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-primary-premium:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(16, 185, 129, 0.6);
    }

    .btn-secondary-premium {
        padding: 18px 36px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 14px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }

    .btn-secondary-premium:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    /* Stat Cards Styling */
    .hero-stats {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 24px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        background: rgba(255, 255, 255, 0.06);
        transform: translateX(10px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        background: rgba(16, 185, 129, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #34d399;
        font-size: 1.5rem;
    }

    .stat-value {
        display: block;
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #94a3b8;
    }

    /* Features Section Styling */
    .features-section {
        padding: 120px 0;
        background: #022c22;
        position: relative;
        overflow: hidden;
    }

    .features-backdrop {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        pointer-events: none;
    }

    .feature-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(100px);
        opacity: 0.1;
        background: #10b981;
    }

    .orb-3 { width: 400px; height: 400px; top: -100px; left: -100px; background: #10b981; }
    .orb-4 { width: 300px; height: 300px; bottom: -50px; right: -50px; background: #065f46; }

    .section-header {
        text-align: center;
        margin-bottom: 80px;
    }

    .section-title {
        font-size: 3.5rem;
        font-weight: 800;
        color: white;
        margin-bottom: 16px;
        letter-spacing: -0.04em;
    }

    .section-desc {
        font-size: 1.25rem;
        color: #94a3b8;
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
    }

    .feature-card-premium {
        background: rgba(255, 255, 255, 0.02);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        padding: 60px 40px;
        border-radius: 30px;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
        overflow: hidden;
    }

    .card-glow {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, rgba(59, 130, 246, 0.15), transparent 70%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .feature-card-premium:hover {
        transform: translateY(-15px) scale(1.02);
        border-color: rgba(16, 185, 129, 0.3);
        background: rgba(255, 255, 255, 0.04);
        box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.5);
    }

    .feature-card-premium:hover .card-glow {
        opacity: 1;
    }

    .feature-icon-wrapper-premium {
        width: 70px;
        height: 70px;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #10b981;
        margin-bottom: 35px;
        transition: all 0.3s ease;
    }

    .feature-card-premium:hover .feature-icon-wrapper-premium {
        transform: scale(1.1) rotate(5deg);
        background: #10b981;
        color: white;
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.5);
    }

    .feature-card-premium h3 {
        font-size: 1.6rem;
        font-weight: 800;
        color: white;
        margin-bottom: 18px;
        letter-spacing: -0.02em;
    }

    .feature-card-premium p {
        color: #94a3b8;
        line-height: 1.8;
        font-size: 1rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .hero-title { font-size: 3.5rem; }
        .hero-container { grid-template-columns: 1fr; text-align: center; }
        .hero-content { display: flex; flex-direction: column; align-items: center; }
        .hero-stats { flex-direction: row; justify-content: center; }
        .hero-subtitle { margin-left: auto; margin-right: auto; }
    }

    @media (max-width: 640px) {
        .hero-title { font-size: 2.75rem; }
        .hero-actions { flex-direction: column; width: 100%; }
        .btn-primary-premium, .btn-secondary-premium { width: 100%; justify-content: center; }
        .hero-stats { flex-direction: column; }
    }
</style>
