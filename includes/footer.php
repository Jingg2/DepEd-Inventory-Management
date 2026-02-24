<?php $root = $base_path ?? './'; ?>
<style>
    .premium-footer {
        background: var(--gradient-primary);
        color: #f8fafc;
        padding: 4rem 0 2rem;
        position: relative;
        overflow: hidden;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .premium-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.5), transparent);
    }
    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 3rem;
        margin-bottom: 3rem;
        text-align: left;
    }
    .footer-section h4 {
        color: #10b981;
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-transform: uppercase;
        letter-spacing: 0.1rem;
        font-family: 'Outfit', sans-serif;
    }
    .footer-section p {
        font-size: 0.95rem;
        line-height: 1.6;
        opacity: 0.8;
        font-family: 'Inter', sans-serif;
    }
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-links li {
        margin-bottom: 0.8rem;
    }
    .footer-brand span {
        color: #10b981;
    }
    .footer-links a {
        color: #f8fafc;
        text-decoration: none;
        opacity: 0.7;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: 'Inter', sans-serif;
    }
    .footer-links li i {
        font-size: 0.85rem;
        color: #10b981;
    }
    .footer-links a:hover {
        opacity: 1;
        color: #10b981;
        transform: translateX(5px);
    }
    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
        font-family: 'Inter', sans-serif;
    }
    .footer-bottom p {
        font-size: 0.9rem;
        opacity: 0.6;
        margin: 0;
    }
    .dev-team {
        background: rgba(16, 185, 129, 0.1);
        padding: 0.6rem 1.2rem;
        border-radius: 50px;
        border: 1px solid rgba(16, 185, 129, 0.2);
        font-weight: 500;
        color: #34d399;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }
    .dev-team:hover {
        background: rgba(16, 185, 129, 0.15);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2);
    }
    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 2rem;
        }
        .footer-links a {
            justify-content: center;
        }
        .footer-links a:hover {
            transform: translateY(-3px);
        }
        .footer-bottom {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<footer class="premium-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>About System</h4>
                <p>A high-performance Inventory Management System designed for DepED, ensuring precise tracking and seamless requisition of essential department supplies.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Navigation</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo $root; ?>dashboard"><i class="fas fa-th-large"></i> Dashboard Overview</a></li>
                    <li><a href="<?php echo $root; ?>supplies"><i class="fas fa-box-open"></i> Inventory Items</a></li>
                    <li><a href="<?php echo $root; ?>reports"><i class="fas fa-file-invoice"></i> System Reports</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Support & Contact</h4>
                <p>
                    <i class="fas fa-map-marker-alt" style="color: #10b981; width: 20px;"></i>Gabaldon Bldg., BCS I, Cogon, Bogo City, Cebu<br>
                    <i class="fas fa-envelope" style="color: #10b981; width: 20px;"></i> bogo.city@deped.gov.ph<br>
                    <i class="fas fa-phone-alt" style="color: #10b981; width: 20px;"></i> (032) 260-1234
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Inventory Management System | Bogo City Division. All rights reserved.</p>
            <div class="dev-team">
                <i class="fas fa-code"></i> 
                <span>Developed by OJT Students | <strong> <a href="https://github.com/damayosun" style="color: #10b981;">Mr. Damayo</a> & <a href="https://github.com/Jingg2" style="color: #10b981;">Mr. Sullera</a></strong></span>
            </div>
        </div>
    </div>
</footer>
    <?php include_once __DIR__ . '/logout_modal.php'; ?>
    <script src="<?php echo $root; ?>assets/js/main.js"></script>
</body>
</html>
