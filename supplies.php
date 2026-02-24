<?php
require_once 'includes/security.php';
initSecureSession();
require_once 'db/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Category Matching Patterns
$patterns = ['OFFICE SUPPLIES', 'JANITORIAL SUPPLIES', '%IT EQUIPMENT%', '%ELECTRICAL%', '%test%'];
$patternSql = "(category = ? OR category = ? OR category LIKE ? OR category LIKE ? OR category LIKE ?)";

// Fetch unique categories for filter from matching patterns
$categories = [];
try {
    $catStmt = $pdo->prepare("SELECT DISTINCT category FROM supply WHERE category IS NOT NULL AND category != '' AND $patternSql AND (unit_cost IS NULL OR unit_cost < 5000) ORDER BY category ASC");
    $catStmt->execute($patterns);
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Fetch supplies with filtering and inclusion
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$sql = "SELECT * FROM supply WHERE $patternSql AND (unit_cost IS NULL OR unit_cost < 5000)";
$params = $patterns;

if (!empty($search)) {
    $sql .= " AND (item LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY item ASC";

$supplies = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching supplies: " . $e->getMessage());
}

// Group supplies by category
$groupedSupplies = [];
foreach ($supplies as $supply) {
    $cat = !empty($supply['category']) ? trim($supply['category']) : 'Uncategorized';
    
    // Normalize "Office" variants to "OFFICE SUPPLIES"
    if (stripos($cat, 'OFFICE') !== false) {
        $cat = 'OFFICE SUPPLIES';
    }
    
    if (!isset($groupedSupplies[$cat])) {
        $groupedSupplies[$cat] = [];
    }
    $groupedSupplies[$cat][] = $supply;
}

// Ensure OFFICE SUPPLIES is at the top if it exists
$officeKey = '';
foreach (array_keys($groupedSupplies) as $k) {
    if (strcasecmp($k, 'OFFICE SUPPLIES') === 0) {
        $officeKey = $k;
        break;
    }
}

if ($officeKey) {
    $officeItems = $groupedSupplies[$officeKey];
    unset($groupedSupplies[$officeKey]);
    $groupedSupplies = [$officeKey => $officeItems] + $groupedSupplies;
}
?>
<?php $root = $base_path ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Supplies - Inventory Management System</title>
    <link rel="stylesheet" href="<?php echo $root; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $root; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        var basePath = '<?php echo addslashes($root); ?>';
        (function() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && window.innerWidth > 1024) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
    <style>
        /* Override dashboard.css global body flex for landing page */
        body {
            display: block !important;
            height: auto !important;
            overflow-x: hidden;
            background-color: #022c22 !important;
        }
        
        /* Category Header Styles */
        .category-header-banner {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-emerald) !important;
        }

        /* Supply Card Design from Image */
        .supply-card {
            border: 1px solid rgba(16, 185, 129, 0.1) !important;
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
            background: white;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .supply-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(16, 185, 129, 0.2);
            border-color: var(--accent-emerald) !important;
        }
        
        .status-badge.status-in-stock { background: #059669; } /* Emerald */
        .status-badge.status-low { background: #fbbf24; } /* Amber */
        .status-badge.status-critical { background: #f97316; } /* Orange */
        .status-badge.status-out { background: #ef4444; } /* Red */

        .card-image img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .card-title {
            font-weight: bold;
            font-size: 1.2rem;
            margin: 5px 0;
            color: #000;
        }
        
        .card-info {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 2px;
        }

        .action-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
            width: 100%;
        }

        .icon-box {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            border: none;
        }
        .icon-view { background-color: var(--bg-emerald-light); color: var(--primary-emerald); }
        .icon-edit { background-color: #dcfce7; color: #166534; }
        .icon-delete { background-color: #fee2e2; color: #991b1b; }
        
        .icon-box i { font-size: 1.1rem; }

        /* Mobile Responsiveness Improvements */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 5px;
            letter-spacing: -0.02em;
        }
        .section-subtitle {
            color: #94a3b8;
        }
        .view-request-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            box-shadow: 0 8px 20px var(--primary-glow);
            transition: all 0.3s ease;
        }
        .view-request-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px var(--primary-glow);
        }
        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            align-items: flex-end;
        }
        .supplies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }

        @media (max-width: 992px) {
            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px !important;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            .section-title {
                font-size: 1.5rem;
            }
            .filter-form {
                grid-template-columns: 1fr;
            }
            .supplies-grid {
                grid-template-columns: 1fr;
            }
            .details-bubble-grid {
                grid-template-columns: 1fr;
            }
            .hero-mini {
                padding: 40px 0 !important;
            }
            .hero-mini h1 {
                font-size: 1.8rem !important;
            }
        }

        /* Floating Action Button (FAB) Styles */
        .fab-request-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 15px;
            pointer-events: none;
        }

        .fab-button {
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
            cursor: pointer;
            border: none;
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: auto;
        }

        .fab-button:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.6);
        }

        .fab-button:active {
            transform: scale(0.95);
        }

        .fab-icon {
            font-size: 1.6rem;
        }

        .fab-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.85rem;
            font-weight: 700;
            min-width: 24px;
            height: 24px;
            padding: 0 6px;
            border-radius: 12px;
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
            animation: badgePop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes badgePop {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        .fab-label {
            background: white;
            color: #0d2137;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
            white-space: nowrap;
            border: 1px solid #e2e8f0;
        }

        .fab-button:hover + .fab-label, 
        .fab-request-container:hover .fab-label {
            opacity: 1;
            transform: translateX(0);
        }

        /* Pulse animation for adding items */
        @keyframes fabPulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .fab-pulse {
            animation: fabPulse 0.6s ease-out;
        }

        /* Admin-specific FAB Styles */
        .admin-fab-container {
            bottom: 115px !important;
        }
        .admin-fab-button {
            background: #059669 !important;
            box-shadow: 0 10px 25px rgba(63, 81, 181, 0.4) !important;
        }
        .admin-fab-button:hover {
            background: #303f9f !important;
            box-shadow: 0 15px 35px rgba(63, 81, 181, 0.5) !important;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="landing-background" style="background-color: #022c22; min-height: 100vh; padding-top: 80px;">
    <!-- Hero Section -->
    <section class="hero-mini" style="background: var(--gradient-primary); color: white; padding: 80px 0; margin-bottom: 50px; text-align: center; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background: radial-gradient(circle at 20% 30%, #10b981 0%, transparent 50%), radial-gradient(circle at 80% 70%, #34d399 0%, transparent 50%); pointer-events: none;"></div>
        <div class="container" style="position: relative; z-index: 2;">
            <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.04em;">Available Inventory</h1>
            <p style="opacity: 0.8; font-size: 1.2rem; font-weight: 400; max-width: 600px; margin: 0 auto;">Browse and search for items available in our department.</p>
        </div>
    </section>

    <!-- Supplies Section -->
    <section class="available-supplies" style="padding-bottom: 0px;">
        <div class="container">
            <div class="section-header">
                <div class="title-group">
                    <h2 class="section-title">Available Supplies</h2>
                    <p class="section-subtitle" style="color: #94a3b8;">Browse items and add them to your requisition request.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="view-request-btn">
                        <i class="fas fa-clipboard-list"></i> View Requisition (0)
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-card">
                <form action="" method="GET" class="filter-form" onsubmit="event.preventDefault(); return false;">
                    <div class="input-group-search">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #34d399; text-transform: uppercase; margin-bottom: 8px;">Search Supplies</label>
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #8b9cb1;"></i>
                            <input type="text" id="supply-search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or description..." autocomplete="off" style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #e1e8ed; border-radius: 8px; font-size: 0.95rem;">
                        </div>
                    </div>
                    <div class="input-group-filter">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #34d399; text-transform: uppercase; margin-bottom: 8px;">Category</label>
                        <select name="category" onchange="this.form.submit()" style="width: 100%; padding: 12px 15px; border: 1px solid #e1e8ed; border-radius: 8px; font-size: 0.95rem; appearance: none; background: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%238b9cb1%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%0-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E') no-repeat right 15px center; background-size: 12px;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="supplies-sections">
                <?php if (empty($groupedSupplies)): ?>
                    <div style="text-align: center; padding: 50px; background: white; border-radius: 12px; color: #6c757d; grid-column: 1/-1;">
                        <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>No supplies found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedSupplies as $categoryName => $categoryItems): ?>
                        <div class="category-group" style="margin-bottom: 60px;">
                            <div class="category-header-banner" style="border-bottom: 3px solid #10b981 !important;">
                                <i class="fas fa-layer-group" style="font-size: 1.6rem; color: #10b981;"></i>
                                <h2 style="font-size: 1.8rem; color: #ffffff; margin: 0; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?php echo htmlspecialchars($categoryName); ?>
                                    <span style="font-size: 1.1rem; opacity: 0.6; margin-left: 12px; text-transform: none; font-weight: 600; color: #34d399;">(<?php echo count($categoryItems); ?> ITEMS)</span>
                                </h2>
                            </div>

                            <div class="supplies-grid">
                                <?php foreach ($categoryItems as $item): ?>
                                    <?php 
                                        $qty = (int)($item['quantity'] ?? 0);
                                        $badgeClass = 'status-in-stock';
                                        $status_label = 'In Stock';
                                        
                                        $lowThreshold = 10; // Default or fetch from DB
                                        $criticalThreshold = 5; // Default or fetch from DB

                                        if ($qty <= 0) {
                                            $badgeClass = 'status-out';
                                            $status_label = 'Out of Stock';
                                        } elseif ($qty <= $criticalThreshold) {
                                            $badgeClass = 'status-critical';
                                            $status_label = 'Critical';
                                        } elseif ($qty <= $lowThreshold) {
                                            $badgeClass = 'status-low';
                                            $status_label = 'Low Stock';
                                        }

                                        // Prepare image source
                                        $imgSrc = 'img/Bogo_City_logo.png';
                                        if (!empty($item['image'])) {
                                            $imgSrc = 'data:image/jpeg;base64,' . base64_encode($item['image']);
                                        }
                                    ?>
                                    <div class="supply-card" 
                                         data-id="<?php echo htmlspecialchars($item['supply_id'] ?? ''); ?>"
                                         data-name="<?php echo htmlspecialchars($item['item'] ?? 'Unknown'); ?>"
                                         data-category="<?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?>"
                                         data-quantity="<?php echo htmlspecialchars($item['quantity'] ?? '0'); ?>"
                                         data-stock-no="<?php echo htmlspecialchars($item['stock_no'] ?? 'N/A'); ?>"
                                         data-unit="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>"
                                         data-description="<?php echo htmlspecialchars($item['description'] ?? 'No description available.'); ?>"
                                         data-unit-cost="<?php echo htmlspecialchars($item['unit_cost'] ?? '0.00'); ?>"
                                         data-total-cost="<?php echo htmlspecialchars($item['total_cost'] ?? '0.00'); ?>"
                                         data-status="<?php echo htmlspecialchars($item['status'] ?? 'Available'); ?>"
                                         data-image="<?php echo $imgSrc; ?>">
                                        
                                        <!-- Status Badge (Pill) -->
                                        <div class="status-badge <?php echo $badgeClass; ?>">
                                            <?php echo strtoupper($status_label); ?>
                                        </div>

                                        <!-- Image -->
                                        <div class="card-image">
                                            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($item['item'] ?? 'Supply'); ?>">
                                        </div>

                                        <!-- Centered Content -->
                                        <div class="card-title"><?php echo htmlspecialchars($item['item'] ?? 'Untitled Item'); ?></div>
                                        <div class="card-info">Description: <?php echo htmlspecialchars($item['description'] ?? 'No description.'); ?></div>
                                        <div class="qty-display <?php echo $badgeClass; ?>" style="margin: 10px auto !important;">
                                            <i class="fas fa-cubes"></i>
                                            Quantity: <span class="qty-value"><?php echo $qty; ?></span>
                                        </div>
                                        
                                        <!-- Action Icons -->
                                        <div class="action-icons">
                                            <button class="icon-box icon-view btn-view-details" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="icon-box icon-edit btn-request-item" title="Add to Request">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Floating Action Button -->
    <div class="fab-request-container" id="fab-container">
        <div class="fab-label">View Requisition</div>
        <button class="fab-button" id="fab-view-request" title="View Request">
            <i class="fas fa-clipboard-list fab-icon"></i>
            <span class="fab-badge" id="fab-badge">0</span>
        </button>
    </div>

    <?php include 'includes/footer.php'; ?>
</div>

<!-- Modal for Item Details - Redesigned -->
<div id="item-modal" class="modal item-modal-wrapper">
    <div class="modal-content redesigned-modal">
        <div class="modal-header-custom">
            <h2 id="modal-title">Supply Details</h2>
            <span class="close-custom" id="item-close">&times;</span>
        </div>
        
        <div class="modal-body-custom">
            <div class="modal-image-container">
                <img id="modal-img" src="" alt="Item Image">
            </div>
            
            <div class="details-bubble-grid">
                <div class="detail-bubble">
                    <strong>Stock Number:</strong> <span id="modal-stock-no"></span>
                </div>
                <div class="detail-bubble">
                    <strong>Item Name:</strong> <span id="modal-name"></span>
                </div>
                <div class="detail-bubble">
                    <strong>Category:</strong> <span id="modal-category"></span>
                </div>
                <div class="detail-bubble">
                    <strong>Unit:</strong> <span id="modal-unit"></span>
                </div>
                <div class="detail-bubble">
                    <strong>Quantity:</strong> <span id="modal-quantity"></span>
                </div>
                <div class="detail-bubble">
                    <strong>Unit Cost:</strong> ₱<span id="modal-unit-cost"></span>
                </div>
                <div class="detail-bubble">
                    <strong>Total Cost:</strong> ₱<span id="modal-total-cost"></span>
                </div>
                <div class="detail-bubble">
                    <strong>Status:</strong> <span id="modal-status"></span>
                </div>
                <div class="detail-bubble full-width">
                    <strong>Description:</strong> <span id="modal-description"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Requisition Request -->
<div id="request-modal" class="modal request-modal-wrapper">
    <div class="modal-content redesigned-modal" style="max-width: 900px;">
        <div class="modal-header-custom">
            <h2 id="request-modal-title">Requisition and Issue Slip</h2>
            <span class="close-custom" id="request-close">&times;</span>
        </div>
        
        <div class="modal-body-custom">
            <div class="requisition-form">
                <div class="form-header">
                    <h3 class="info-title">Employee Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Employee ID</label>
                            <input type="text" id="req-emp-id" class="form-control" placeholder="Enter ID">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" id="req-date" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" id="req-name" class="form-control" placeholder="Full Name" readonly>
                        </div>
                         <div class="form-group">
                            <label class="form-label">Designation</label>
                            <input type="text" id="req-designation" class="form-control" placeholder="Position" readonly>
                        </div>
                         <div class="form-group full-width-span">
                            <label class="form-label">Department / Office</label>
                            <input type="text" id="req-department" class="form-control" placeholder="Department" readonly>
                        </div>
                        <div class="form-group full-width-span">
                            <label class="form-label">Purpose of Request</label>
                            <textarea id="req-purpose" class="form-control" placeholder="Enter the purpose of your request"></textarea>
                        </div>
                    </div>
                </div>

                <div class="request-items-section">
                    <h3 class="info-title">Selected Items</h3>
                    <div class="table-container">
                        <table class="request-items-table">
                            <thead>
                                <tr>
                                    <th>Stock No.</th>
                                    <th>Unit</th>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th class="qty-col">Quantity</th>
                                    <th class="action-col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="request-table-body">
                                <!-- Items will be populated here -->
                                <tr id="empty-request-row">
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #a0aec0;">No items added to request yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="request-form-actions">
                     <button type="button" id="clear-request-btn" class="btn-clear-request">Clear All</button>
                     <button type="button" id="submit-request-btn" class="btn-submit-request">
                        <i class="fas fa-paper-plane"></i> Submit Request
                     </button>
                </div>
            </div>
        </div>
    </div>
</div>



<style>
    /* Modal Visibility and Layout - Generic for all modals */
    .modal.active {
        display: block !important; /* Switch to block for standard scrolling */
        overflow-y: auto;
        padding: 50px 0;
        opacity: 1 !important;
        visibility: visible !important;
        z-index: 9999;
        background-color: rgba(0,0,0,0.7) !important;
    }

    /* Premium Redesigned Modal Classes */
    .redesigned-modal {
        background: white;
        border-radius: 16px;
        max-width: 950px; /* Larger size */
        width: 95%;
        margin: 0 auto !important; /* Centered with block auto-margins */
        overflow: visible;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        animation: modalFadeIn 0.3s ease-out;
    }
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .modal-header-custom {
        background: var(--gradient-primary);
        color: white;
        padding: 24px 30px;
        text-align: left;
        position: relative;
        border-bottom: 2px solid rgba(16, 185, 129, 0.2);
    }
    .modal-header-custom h2 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: white !important;
    }
    .close-custom {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 24px;
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.2s;
        background: rgba(255,255,255,0.1);
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: white;
    }
    .close-custom:hover {
        opacity: 1;
        background: rgba(255,255,255,0.2);
    }
    .modal-body-custom {
        padding: 30px;
        max-height: 80vh;
        overflow-y: auto;
    }
    .modal-image-container {
        display: flex;
        justify-content: center;
        margin-bottom: 25px;
    }
    .modal-image-container img {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid #f0f2f5;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .details-bubble-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .detail-bubble {
        background: #f0fdf4;
        padding: 14px 22px;
        border-radius: 12px;
        border-left: 5px solid #10b981;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    .detail-bubble:hover {
        background: #dcfce7;
        transform: translateX(4px);
    }
    .detail-bubble strong {
        color: #1a202c;
        font-weight: 600;
        margin-right: 15px;
    }
    .detail-bubble span {
        color: #4a5568;
        font-weight: 500;
        text-align: right;
    }
    .detail-bubble.full-width {
        grid-column: 1 / -1;
        flex-direction: column;
        align-items: flex-start;
    }
    .detail-bubble.full-width span {
        text-align: left;
        margin-top: 5px;
    }
    
    .request-items-table thead {
        background: #022c22;
        color: white;
    }
    .btn-submit-request {
        background: var(--gradient-primary);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 12px;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }
    .btn-submit-request:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(16, 185, 129, 0.4);
    }

    /* Requisition Form Styles */
    .form-header {
        margin-bottom: 20px;
        border-bottom: 2px solid #f0f2f5;
        padding-bottom: 15px;
    }
    .info-title {
        color: #0d2137;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .form-label {
        display: block;
        font-weight: 600;
        font-size: 0.9rem;
        color: #4a5568;
        margin-bottom: 5px;
    }
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 0.95rem;
    }
    .form-control[readonly] {
        background-color: #f8fafc;
    }
    .full-width-span {
        grid-column: 1 / -1;
    }
    #req-purpose {
        min-height: 80px;
        resize: vertical;
    }
    .table-container {
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .request-items-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }
    .request-items-table th {
        background: #f8f9fa;
        padding: 12px;
        font-weight: 600;
        color: #4a5568;
        font-size: 0.9rem;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }
    .request-items-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.95rem;
    }
    .qty-col { width: 100px; }
    .action-col { width: 60px; }
    
    .request-form-actions {
        margin-top: 30px;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }
    .btn-clear-request {
        padding: 10px 20px;
        border: 1px solid #cbd5e0;
        background: white;
        color: #4a5568;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-clear-request:hover {
        background: #f7fafc;
        border-color: #a0aec0;
    }
    .btn-submit-request {
        padding: 10px 24px;
        border: none;
        background: #0d2137;
        color: white;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-submit-request:hover {
        background: #1a365d;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .modal-body-custom {
            padding: 15px;
        }
        .form-grid {
            grid-template-columns: 1fr;
        }
        .details-bubble-grid {
            grid-template-columns: 1fr;
        }
        .request-form-actions {
            flex-direction: column-reverse;
            gap: 10px;
        }
        .request-form-actions button {
            width: 100%;
            justify-content: center;
        }
        .modal-header-custom h2 {
            font-size: 1.3rem;
        }
        .redesigned-modal {
            margin: 10px auto !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Instant Search Implementation
    const searchInput = document.getElementById('supply-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const categoryGroups = document.querySelectorAll('.category-group');
            
            categoryGroups.forEach(group => {
                const cards = group.querySelectorAll('.supply-card');
                let hasVisibleCards = false;
                
                cards.forEach(card => {
                    // Use data attributes for more reliable searching
                    const itemName = (card.getAttribute('data-name') || '').toLowerCase();
                    const itemDesc = (card.getAttribute('data-description') || '').toLowerCase();
                    
                    if (itemName.includes(query) || itemDesc.includes(query)) {
                        card.style.display = '';
                        hasVisibleCards = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show/hide the entire category group based on whether it has visible cards
                group.style.display = hasVisibleCards ? '' : 'none';
            });
        });
    }

    const modal = document.getElementById('item-modal');
    const closeBtn = document.getElementById('item-close');

    if (!modal) return;

    // Use event delegation to handle clicks on any 'View Details' button
    document.addEventListener('click', function(e) {
        const viewBtn = e.target.closest('.btn-view-details');
        
        if (viewBtn) {
            e.preventDefault();
            e.stopPropagation(); // Prevent conflict with dashboard.js
            
            const card = viewBtn.closest('.supply-card');
            if (card) {
                // Extract data from card attributes
                const name = card.getAttribute('data-name') || 'N/A';
                const stockNo = card.getAttribute('data-stock-no') || 'N/A';
                const category = card.getAttribute('data-category') || 'N/A';
                const unit = card.getAttribute('data-unit') || '';
                const quantity = card.getAttribute('data-quantity') || '0';
                const status = card.getAttribute('data-status') || 'Available';
                const description = card.getAttribute('data-description') || 'No description available.';
                const unitCost = parseFloat(card.getAttribute('data-unit-cost')) || 0;
                const totalCost = parseFloat(card.getAttribute('data-total-cost')) || 0;
                const imgSrc = card.getAttribute('data-image') || 'img/Bogo_City_logo.png';

                // Populate Modal Elements
                const modalTitle = document.getElementById('modal-title');
                const modalImg = document.getElementById('modal-img');
                const modalStockNo = document.getElementById('modal-stock-no');
                const modalName = document.getElementById('modal-name');
                const modalCategory = document.getElementById('modal-category');
                const modalUnit = document.getElementById('modal-unit');
                const modalQuantity = document.getElementById('modal-quantity');
                const modalUnitCost = document.getElementById('modal-unit-cost');
                const modalTotalCost = document.getElementById('modal-total-cost');
                const modalStatus = document.getElementById('modal-status');
                const modalDescription = document.getElementById('modal-description');

                if (modalTitle) modalTitle.textContent = name + ' Details';
                if (modalImg) modalImg.src = imgSrc;
                if (modalStockNo) modalStockNo.textContent = stockNo;
                if (modalName) modalName.textContent = name;
                if (modalCategory) modalCategory.textContent = category;
                if (modalUnit) modalUnit.textContent = unit;
                if (modalQuantity) modalQuantity.textContent = quantity;
                if (modalStatus) modalStatus.textContent = status;
                if (modalDescription) modalDescription.textContent = description;
                
                // Format currency
                if (modalUnitCost) modalUnitCost.textContent = unitCost.toLocaleString('en-PH', {minimumFractionDigits: 2});
                if (modalTotalCost) modalTotalCost.textContent = totalCost.toLocaleString('en-PH', {minimumFractionDigits: 2});

                // Show Modal
                modal.classList.add('active');
            }
        }
    });

    // Close functionality
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.classList.remove('active');
        };
    }

    // Close on outside click
    window.onclick = function(event) {
        const itemModal = document.getElementById('item-modal');
        const reqModal = document.getElementById('request-modal');
        if (event.target == itemModal) {
            itemModal.classList.remove('active');
        }
        if (event.target == reqModal) {
            reqModal.classList.remove('active');
        }
    };

    // Requisition Request Logic
    const requestModal = document.getElementById('request-modal');
    const requestCloseBtn = document.getElementById('request-close');
    const viewRequestBtn = document.querySelector('.view-request-btn');
    const requestTableBody = document.getElementById('request-table-body');
    const clearRequestBtn = document.getElementById('clear-request-btn');
    const submitRequestBtn = document.getElementById('submit-request-btn');
    const emptyRow = document.getElementById('empty-request-row');
    const requestCountBadge = document.querySelector('.view-request-btn'); // reusing the button text

    let requestItems = [];

    // Initialize
    updateRequestUI();

    // Event Listeners related to Request Modal
    if (viewRequestBtn) {
        viewRequestBtn.addEventListener('click', function() {
            renderRequestTable();
            requestModal.classList.add('active');
        });
    }

    if (requestCloseBtn) {
        requestCloseBtn.addEventListener('click', function() {
            requestModal.classList.remove('active');
        });
    }

    // Add to Request logic
    document.querySelectorAll('.btn-request-item').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.supply-card');
            const item = {
                id: card.getAttribute('data-id'),
                stockNo: card.getAttribute('data-stock-no'),
                name: card.getAttribute('data-name'),
                description: card.getAttribute('data-description'),
                unit: card.getAttribute('data-unit'),
                maxQty: parseInt(card.getAttribute('data-quantity')),
                requestQty: 1
            };

            addToRequest(item);
        });
    });

    if (clearRequestBtn) {
        clearRequestBtn.addEventListener('click', function() {
            showConfirm('Are you sure you want to clear all items?', function(result) {
                if (result) {
                    requestItems = [];
                    updateRequestUI();
                    renderRequestTable();
                }
            });
        });
    }

    // Employee ID Lookup Logic
    const empIdInput = document.getElementById('req-emp-id');
    const empNameInput = document.getElementById('req-name');
    const empPositionInput = document.getElementById('req-designation');
    const empDeptInput = document.getElementById('req-department');

    let lookupTimeout;

    if (empIdInput) {
        empIdInput.addEventListener('input', function() {
            const id = this.value.trim();
            
            // Clear existing fields immediately if ID is cleared
            if (id === '') {
                empNameInput.value = '';
                empPositionInput.value = '';
                empDeptInput.value = '';
                return;
            }

            // Debounce the lookup to avoid too many requests
            clearTimeout(lookupTimeout);
            lookupTimeout = setTimeout(() => {
                fetch(`api/get_employee.php?id=${encodeURIComponent(id)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.employee) {
                            const emp = data.employee;
                            empNameInput.value = `${emp.first_name} ${emp.last_name}`;
                            empPositionInput.value = emp.position || '';
                            empDeptInput.value = emp.department_name || '';
                            
                            // Visual feedback
                            empIdInput.style.borderColor = '#48bb78'; // Green
                            empIdInput.style.backgroundColor = '#f0fff4';
                        } else {
                            // ID not found
                            empIdInput.style.borderColor = '#e2e8f0'; // Default
                            empIdInput.style.backgroundColor = '#ffffff';
                            // Clear fields if ID is invalid
                            empNameInput.value = '';
                            empPositionInput.value = '';
                            empDeptInput.value = '';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching employee:', error);
                    });
            }, 500); // 500ms delay
        });

        // Also handle blur/change for immediate lookup
        empIdInput.addEventListener('change', function() {
            this.dispatchEvent(new Event('input'));
        });
    }

    if (submitRequestBtn) {
        submitRequestBtn.addEventListener('click', function() {
            if (requestItems.length === 0) {
                showModal('Please add items to your request first.', 'warning');
                return;
            }
            
            const empId = document.getElementById('req-emp-id').value;
            const name = document.getElementById('req-name').value;
            const purpose = document.getElementById('req-purpose').value;
             
            if (!empId || !name) {
                showModal('Please fill in Employee ID (Name will auto-fill).', 'warning');
                return;
            }
            
            // Show loading state
            const originalBtnText = submitRequestBtn.innerHTML;
            submitRequestBtn.disabled = true;
            submitRequestBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const payload = {
                employee: { 
                    id: empId, 
                    name: name,
                    date: document.getElementById('req-date').value,
                    designation: document.getElementById('req-designation').value,
                    department: document.getElementById('req-department').value,
                    purpose: purpose
                },
                items: requestItems
            };

            fetch('api/submit_requisition.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showModal(`Request submitted successfully! Requisition No: ${data.requisition_no}`, 'success');
                    
                    // Reset cart
                    requestItems = [];
                    updateRequestUI();
                    renderRequestTable();
                    requestModal.classList.remove('active');
                    
                    // Clear form
                    document.getElementById('req-emp-id').value = '';
                    document.getElementById('req-name').value = '';
                    document.getElementById('req-designation').value = '';
                    document.getElementById('req-department').value = '';
                    document.getElementById('req-purpose').value = '';
                    document.getElementById('req-emp-id').style.borderColor = '#e2e8f0';
                    document.getElementById('req-emp-id').style.backgroundColor = '#ffffff';
                } else {
                    showModal('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Submission Error:', error);
                showModal('An error occurred while submitting the request.', 'error');
            })
            .finally(() => {
                submitRequestBtn.disabled = false;
                submitRequestBtn.innerHTML = originalBtnText;
            });
        });
    }

    function addToRequest(newItem) {
        const existingItem = requestItems.find(item => item.id === newItem.id);
        
        if (existingItem) {
            if (existingItem.requestQty < existingItem.maxQty) {
                existingItem.requestQty++;
                const displayDesc = newItem.description || newItem.name || 'N/A';
                showModal(`Added another ${displayDesc} to request.`, 'success');
            } else {
                showModal(`Cannot add more. Max stock available is ${existingItem.maxQty}.`, 'warning');
            }
        } else {
            if (newItem.maxQty > 0) {
                requestItems.push(newItem);
                const displayDesc = newItem.description || newItem.name || 'N/A';
                showModal(`${displayDesc} added to request.`, 'success');
            } else {
                showModal('This item is out of stock.', 'error');
            }
        }
        updateRequestUI();
    }

    function removeFromRequest(itemId) {
        requestItems = requestItems.filter(item => item.id !== itemId);
        updateRequestUI();
        renderRequestTable();
    }

    function updateRequestUI() {
        // Update both the top button and the FAB
        const count = requestItems.reduce((sum, item) => sum + item.requestQty, 0);
        
        // Update top button
        if (requestCountBadge) {
             requestCountBadge.innerHTML = `<i class="fas fa-clipboard-list"></i> View Requisition (${count})`;
        }

        // Update FAB
        const fabBadge = document.getElementById('fab-badge');
        const fabBtn = document.getElementById('fab-view-request');
        if (fabBadge) {
            fabBadge.textContent = count;
            fabBadge.style.display = count > 0 ? 'flex' : 'none';
        }

        // Trigger pulse animation on FAB if adding items
        if (fabBtn && count > 0) {
            fabBtn.classList.remove('fab-pulse');
            void fabBtn.offsetWidth; // Trigger reflow
            fabBtn.classList.add('fab-pulse');
        }
    }

    // Connect FAB button
    const fabBtn = document.getElementById('fab-view-request');
    if (fabBtn) {
        fabBtn.addEventListener('click', function() {
            renderRequestTable();
            requestModal.classList.add('active');
        });
    }

    function renderRequestTable() {
        requestTableBody.innerHTML = '';
        
        if (requestItems.length === 0) {
            requestTableBody.appendChild(emptyRow);
            return;
        }

        requestItems.forEach(item => {
            const tr = document.createElement('tr');
            const displayDesc = item.description || 'N/A';
            tr.innerHTML = `
                <td style="padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748;">${item.stockNo}</td>
                <td style="padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748;">${item.unit}</td>
                <td style="padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748;">${item.name}</td>
                <td style="padding: 12px; border-bottom: 1px solid #edf2f7; color: #2d3748;">${displayDesc}</td>
                <td style="padding: 12px; border-bottom: 1px solid #edf2f7;">
                    <input type="number" min="1" max="${item.maxQty}" value="${item.requestQty}" 
                           class="qty-input" data-id="${item.id}"
                           style="width: 60px; padding: 5px; border: 1px solid #e2e8f0; border-radius: 4px;">
                </td>
                <td style="padding: 12px; border-bottom: 1px solid #edf2f7; text-align: center;">
                    <button class="remove-btn" data-id="${item.id}" style="background: #fed7d7; color: #c53030; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            requestTableBody.appendChild(tr);
        });

        // Attach listeners to new elements
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                removeFromRequest(this.getAttribute('data-id'));
            });
        });

        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const id = this.getAttribute('data-id');
                let newQty = parseInt(this.value);
                const item = requestItems.find(i => i.id === id);
                
                if (item) {
                    if (newQty < 1) newQty = 1;
                    if (newQty > item.maxQty) {
                        showModal(`Only ${item.maxQty} available in stock.`, 'warning');
                        newQty = item.maxQty;
                    }
                    item.requestQty = newQty;
                    this.value = newQty; // update input view
                    updateRequestUI();
                }
            });
        });
    }

    // Close on outside click
    window.onclick = function(event) {
        const itemModal = document.getElementById('item-modal');
        const reqModal = document.getElementById('request-modal');
        if (event.target == itemModal) {
            itemModal.classList.remove('active');
        }
        if (event.target == reqModal) {
            reqModal.classList.remove('active');
        }
    };
});
</script>


    <script src="js/dashboard.js?v=<?php echo time(); ?>"></script>
<?php include_once __DIR__ . '/includes/logout_modal.php'; ?>

</body>
</html>
