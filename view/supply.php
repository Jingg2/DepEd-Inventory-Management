<?php
// filepath: c:\OJT DEVELOPMENT\Inventory_System\view\supply.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/security.php';
    initSecureSession();
    requireAuth();

// Internal data check
if (!isset($supplies)) {
    die("Error: Supply data not initialized. This view must be loaded via the proper controller or route.");
}

require_once __DIR__ . '/../model/settingsModel.php';
$settingsModel = new SettingsModel();
$settings = $settingsModel->getAllSettings();
$defaultLow = $settings['default_low_stock'] ?? 10;
$defaultCritical = $settings['default_critical_stock'] ?? 5;
// Determine base path for assets
$basePath = (strpos($_SERVER['SCRIPT_NAME'], '/view/') !== false) ? '../' : '';
$actionPath = (strpos($_SERVER['SCRIPT_NAME'], '/view/') !== false) ? '../supply.php' : 'supply.php';

    require_once __DIR__ . '/../model/requisitionModel.php';
    $reqModelNotify = new RequisitionModel();
    $pendingStats = $reqModelNotify->getRequisitionStats();
    $pendingCount = $pendingStats['pending'] ?? 0;
} catch (Throwable $e) {
    die("<h1>Error Loading Inventory Page</h1><p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p><p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
}
?>
<?php 
// Robust root calculation
$serverPath = str_replace('\\', '/', dirname(__DIR__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$scriptDir = str_ireplace($docRoot, '', $serverPath);
$root = rtrim($scriptDir, '/') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Supply</title>
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
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 90%;
            animation: slideIn 0.3s ease;
            position: relative;
        }

        /* Item Details Modal */
        .item-modal-content {
            max-width: 500px;
            padding: 24px;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            z-index: 10;
        }

        .close, .close-custom, .add-close-btn, .item-close-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 300;
            font-size: 24px;
        }

        .close:hover, .close-custom:hover, .add-close-btn:hover, .item-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: rotate(90deg) scale(1.1);
            border-color: white;
        }

        /* Standard Emerald Modal Title (for Details/Add/Edit) */
        #modal-title {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white !important;
            font-size: 1.25rem;
            font-weight: 700;
            margin: -24px -24px 25px -24px;
            padding: 20px;
            text-align: left;
            border-radius: 16px 16px 0 0;
            display: flex !important;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15);
            visibility: visible !important;
            opacity: 1 !important;
        }

        #modal-title::before {
            content: "\f05a"; /* Info circle icon */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .header h1 {
            color: #1e293b !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin: 0;
        }

        #modal-title, #request-modal-title, .modal-header-custom h2 {
            color: white !important;
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            align-items: center;
            gap: 12px;
        }

        #modal-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            margin: 20px auto;
            display: block;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 3px solid #f7fafc;
        }

        .item-modal-content p {
            margin: 15px 0;
            font-size: 16px;
            color: #4a5568;
            display: flex;
            align-items: center;
        }

        .item-modal-content p strong {
            color: #2d3748;
            min-width: 140px;
            font-weight: 600;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .details-grid p {
            margin: 0;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .view-icon {
            background: #edf2f7;
            color: #3182ce;
        }

        .view-icon:hover {
            background: #3182ce;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(49, 130, 206, 0.3);
        }

        .edit-icon {
            background: #edf2f7;
            color: #667eea;
        }

        .edit-icon:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .delete-icon:hover {
            background: #fed7d7;
            color: #e53e3e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(229, 62, 62, 0.3);
        }

        .stock-card-icon:hover {
            background: #38a169;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(56, 161, 105, 0.3);
        }

        .stock-card-preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 1rem; /* Increased font size */
        }

        .stock-card-preview-table th, .stock-card-preview-table td {
            border: 1px solid #e2e8f0;
            padding: 12px 15px;
            text-align: left;
            white-space: nowrap; /* Prevent text wrapping to keep table neat */
        }

        .stock-card-preview-table th {
            background: #f7fafc;
            font-weight: 600;
        }

        .stock-card-modal-content {
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            overflow: hidden; 
            max-width: 900px; /* Reduced width */
            width: 80%; /* Reduced from 98% */
        }

        .modal-body-scroll {
            flex: 1;
            overflow-y: auto;
            border-radius: 8px;
            border: 1px solid #edf2f7;
            margin: 0 20px;
            padding-bottom: 20px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 16px 16px;
            display: flex;
            justify-content: flex-end;
        }

        .download-btn-premium {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }

        .download-btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
            background: linear-gradient(135deg, #219150 0%, #27ae60 100%);
        }

        .download-btn-premium i {
            font-size: 1.1em;
        }

        /* Supply Card Actions - Horizontal Layout */
        .supply-card .actions {
            display: flex;
            flex-direction: row;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        /* Add Supply Modal */
        .add-supply-modal-content {
            padding: 0;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal-header-section {
            background: linear-gradient(135deg, #0d2137 0%, #2A4D88 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 16px 16px 0 0;
            text-align: center; /* Center for more premium look */
        }

        .modal-header-section h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .modal-subtitle {
            font-size: 14px;
            opacity: 0.95;
            font-weight: 400;
        }

        .supply-form {
            flex: 1;
            overflow-y: auto;
            padding: 24px 30px;
        }

        .form-section {
            margin-bottom: 35px;
            background: #f7fafc;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #667eea;
        }

        .section-header i {
            color: #667eea;
            font-size: 20px;
        }

        .section-header h3 {
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #667eea;
            font-size: 14px;
        }

        .required {
            color: #e53e3e;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Drag and Drop Area */
        .drag-drop-area {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .drag-drop-area:hover {
            border-color: #667eea;
            background: #f7fafc;
        }

        .drag-drop-area.drag-over {
            border-color: #667eea;
            background: #edf2f7;
        }

        .drag-drop-content i {
            font-size: 48px;
            color: #cbd5e0;
            margin-bottom: 15px;
        }

        .drag-drop-text {
            color: #4a5568;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .browse-link {
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
        }

        .drag-drop-hint {
            color: #a0aec0;
            font-size: 13px;
        }

        .image-preview {
            position: relative;
        }

        .image-preview img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .remove-image-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #e53e3e;
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(229, 62, 62, 0.3);
            transition: all 0.3s ease;
        }

        .remove-image-btn:hover {
            background: #c53030;
            transform: scale(1.1);
        }

        /* Form Actions */
        .form-actions {
            flex-shrink: 0;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding: 20px 40px;
            background: white;
            border-top: 2px solid #e2e8f0;
            border-radius: 0 0 16px 16px;
        }

        .form-actions button {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cancel-btn {
            background: #e2e8f0;
            color: #4a5568;
        }

        .cancel-btn:hover {
            background: #cbd5e0;
        }

        button[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        /* Scrollbar */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        /* Responsive */
        @media (max-width: 992px) { /* Increased threshold for tablet/laptop */
            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                max-width: 100%;
                margin: 10px;
            }

            .supply-form {
                padding: 25px;
            }

            .form-actions {
                padding: 15px 25px;
            }

            .item-modal-content {
                padding: 30px 20px;
                width: 95%;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

        /* Stock Card Filter Controls */
        .sc-filter-controls {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
            align-items: flex-end !important;
            gap: 15px !important;
            padding: 10px 20px !important; /* Reduced padding */
            background: white !important;
            border-bottom: 1px solid #edf2f7 !important;
        }

        .sc-filter-controls .form-group {
            flex: 0 1 auto !important;
            width: auto !important;
            min-width: 120px; /* Smaller min-width */
            margin: 0 !important;
        }

        .sc-filter-controls .form-group label {
            font-size: 0.8rem !important;
            margin-bottom: 4px !important;
        }

        .sc-filter-controls .form-group input {
            font-size: 0.9rem !important;
            height: 36px !important; /* Compact height */
            padding: 6px 10px !important;
        }

        .sc-filter-controls .sc-button-group {
            flex: 0 0 auto !important;
            display: flex !important;
            gap: 8px !important;
            margin-bottom: 2px !important; /* Align with input bottom */
        }

        /* Stock Card Table Styles */
        .stock-card-preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem; /* Increased to 1rem */
            color: #2d3748;
        }

        .stock-card-preview-table th {
            background-color: #f8fafc;
            color: #4a5568;
            font-weight: 700; /* Bold headers */
            text-align: left;
            padding: 12px 15px; /* More breathing room */
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .stock-card-preview-table td {
            padding: 10px 15px; /* More breathing room */
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
        }

        .stock-card-preview-table tr:hover {
            background-color: #f7fafc;
        }

        .sc-filter-controls .sc-button-group button {
            height: 36px !important; /* Match input height */
            padding: 0 16px !important;
            font-size: 0.85rem !important;
        }

        @media (max-width: 992px) { /* Increased threshold for tablet/laptop */
            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                max-width: 100%;
                margin: 10px;
            }

            .supply-form {
                padding: 25px;
            }

            .form-actions {
                padding: 15px 25px;
            }

            .item-modal-content {
                padding: 30px 20px;
                width: 95%;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            /* Stock Card Responsive Adjustments */
            /* Stock Card Responsive Adjustments - Removed forced column layout for filters */
            .stock-card-info-header {
                flex-direction: column !important;
                text-align: left !important;
                gap: 10px;
                padding: 15px 20px !important;
            }

            .stock-card-info-header div {
                text-align: left !important;
            }

            .modal-body-scroll {
                margin: 0 10px;
                padding-bottom: 10px;
                max-height: none; /* Let flex handle it */
            }

            .download-btn-premium {
                width: 100%;
                justify-content: center;
            }
        }

        /* Mobile specific overrides */
        @media (max-width: 600px) {
            .stock-card-modal-content {
                width: 98% !important;
                margin: 5px !important;
                padding: 5px !important;
            }

            .modal-header-section {
                padding: 15px !important;
            }

            .modal-header-section h2 {
                font-size: 1.2rem !important;
            }

            .stock-card-info-header {
                padding: 12px 15px !important;
            }

            .sc-filter-controls {
                padding: 0 15px 15px !important;
            }

            .sc-filter-controls div:last-child {
                width: auto !important; /* Allow buttons to sit inline if space permits */
            }

            .stock-card-preview-table th, 
            .stock-card-preview-table td {
                padding: 8px 4px !important;
                font-size: 0.75rem !important;
            }

            .modal-footer {
                padding: 15px !important;
            }
        }

        /* Category Section Styles */
        .category-section {
            margin-bottom: 40px;
            background: rgba(255, 255, 255, 0.5);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .category-section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 3px solid #2A4D88;
        }

        .category-section-header i {
            font-size: 24px;
            color: #2A4D88;
        }

        .category-section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .category-section .supply-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* Search & Filter Enhancement */
        .search-filter-container #search {
            flex: 2; /* Make search bar take more space */
            padding-left: 45px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23a0aec0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") no-repeat 15px center;
            background-color: #f8fafc;
        }

        .search-filter-container #search:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%232A4D88' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E");
            background-color: #ffffff;
        }

        .search-filter-container #filter-category {
            flex: 1;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="<?php echo $root; ?>images/deped_bogo_logo.png" alt="Logo">
            <h2>Inventory System</h2>
        </div>
        <ul>
            <li><a href="<?php echo $root; ?>dashboard"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="<?php echo $root; ?>inventory" class="active"><i class="fas fa-box"></i> <span>Supply</span></a></li>
            <li class="divider"></li>
            <li>
                <a href="<?php echo $root; ?>requests">
                    <i class="fas fa-file-invoice"></i> <span>Request</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="sidebar-badge"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>employees"><i class="fas fa-users"></i> <span>Employee</span></a></li>
            <li><a href="<?php echo $root; ?>reports"><i class="fas fa-file-excel"></i> <span>Reports</span></a></li>
            <li class="divider"></li>
            <li><a href="<?php echo $root; ?>settings"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="<?php echo $root; ?>logout" style="color: #ef5350;" onclick="showLogoutModal(event, this.href);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Supply Management</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include_once __DIR__ . '/includes/head_notification.php'; ?>
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>
        <div class="stats">
            <div class="stat-card" id="stat-total" style="cursor: pointer; position: relative; overflow: hidden;">
                <i class="fas fa-boxes" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1;"></i>
                <h3>Total items</h3>
                <p><?php echo count($supplies); ?></p>
            </div>
            <div class="stat-card" id="stat-low-stock" style="cursor: pointer; position: relative; overflow: hidden; border-top-color: #f39c12;">
                <i class="fas fa-exclamation-triangle" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: #f39c12;"></i>
                <h3>Low stocks Item</h3>
                <p><?php echo $lowStockCount ?? 0; ?></p>
            </div>
            <div class="stat-card" id="stat-out-of-stock" style="cursor: pointer; position: relative; overflow: hidden; border-top-color: #e74c3c;">
                <i class="fas fa-times-circle" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: #e74c3c;"></i>
                <h3>Total Out of Stock</h3>
                <p><?php echo $outOfStockCount ?? 0; ?></p>
            </div>
            <div class="stat-card" style="position: relative; overflow: hidden; border-top-color: #2ecc71;">
                <i class="fas fa-money-bill-wave" style="position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.1; color: #2ecc71;"></i>
                <h3>total cost</h3>
                <p>₱<?php echo number_format($totalInventoryValue ?? 0, 2); ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
            <button class="add-supply-btn" id="btn-add-new-supply">Add New Supply</button>
            <button class="view-request-btn" id="admin-view-requisition" style="background: #0d2137; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                <i class="fas fa-clipboard-list"></i> Requisition Slip (0)
            </button>
            <button class="controlled-assets-btn" id="btn-controlled-assets" style="background: #1e3a8a; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                <i class="fas fa-truck-loading"></i> Controlled Assets Deliveries
            </button>
            <form method="GET" action="<?php echo $basePath; ?>api/export_supply_excel.php" style="margin: 0;">
                <button type="submit" class="download-btn" style="background-color: #217346; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;"><i class="fas fa-file-excel"></i> Download Excel</button>
            </form>

        </div>
        <div class="search-filter-container">
            <input type="text" id="search" placeholder="Search products...">
            <select id="filter-category">
                <option value="">All Categories</option>
                <?php
                // Get unique categories, preferably from the controller fetch
                $rawCategories = isset($fetchedCategories) ? $fetchedCategories : array_column($supplies, 'category');
                $uniqueCategories = [];
                foreach ($rawCategories as $rc) {
                    $trimmed = trim($rc);
                    if (!empty($trimmed)) {
                        $uniqueCategories[] = $trimmed;
                    }
                }
                $uniqueCategories = array_unique($uniqueCategories);
                sort($uniqueCategories);
                
                foreach ($uniqueCategories as $cat):
                ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filter-property-classification">
                <option value="">All Classifications</option>
                <option value="Consumable / Expendable">Consumable / Expendable</option>
                <option value="Semi-Expendable (Low Value)">Semi-Expendable (Low Value)</option>
                <option value="Semi-Expendable (High Value)">Semi-Expendable (High Value)</option>
                <option value="Property, Plant and Equipment (PPE)">Property, Plant and Equipment (PPE)</option>
            </select>
        </div>
        <h2>Supply List</h2>
        
        <?php
        // Group supplies by category
        $groupedSupplies = [];
        foreach ($supplies as $supply) {
            $cat = !empty($supply['category']) ? trim($supply['category']) : 'Uncategorized';
            
            if (!isset($groupedSupplies[$cat])) {
                $groupedSupplies[$cat] = [];
            }
            $groupedSupplies[$cat][] = $supply;
        }

        // Priority categories to show first (case-insensitive matching)
        $priorityCategories = [
            'office supplies',
            'janitorial supplies',
            'it equipment',
            'it supplies',
            'it equipment/supplies',
            'electrical',
            'electrical supplies',
        ];

        // Build a prioritized groupedSupplies array
        $prioritized = [];
        $remaining = $groupedSupplies;

        foreach ($priorityCategories as $priority) {
            foreach (array_keys($remaining) as $key) {
                if (strcasecmp(trim($key), $priority) === 0) {
                    $prioritized[$key] = $remaining[$key];
                    unset($remaining[$key]);
                    break;
                }
            }
        }

        // Merge: priority first, then the rest alphabetically
        ksort($remaining);
        $groupedSupplies = $prioritized + $remaining;

        foreach ($groupedSupplies as $categoryName => $categoryItems): 
        ?>
            <div class="category-section" data-category="<?php echo htmlspecialchars($categoryName); ?>">
                <div class="category-section-header">
                    <i class="fas <?php 
                        echo match($categoryName) {
                            'OFFICE SUPPLIES' => 'fa-pencil-alt',
                            'IT Equipment' => 'fa-laptop',
                            'Electrical Supplies' => 'fa-bolt',
                            'Janitor Supplies' => 'fa-broom',
                            default => 'fa-folder-open'
                        };
                    ?>"></i>
                    <h3 class="category-section-title">
                        <?php echo htmlspecialchars($categoryName); ?> 
                        <span style="font-size: 0.8em; opacity: 0.7;">(<?php echo count($categoryItems); ?> items)</span>
                    </h3>
                </div>
                <div class="supply-cards">
                    <?php foreach ($categoryItems as $supply): 
                        $qty = isset($supply['quantity']) ? (int)$supply['quantity'] : (isset($supply['previous_month']) ? (int)$supply['previous_month'] : 0);
                        
                        // Determine status badge
                        $badgeClass = '';
                        $badgeText = '';
                        
                        $lowThreshold = isset($supply['low_stock_threshold']) ? (int)$supply['low_stock_threshold'] : $defaultLow;
                        $criticalThreshold = isset($supply['critical_stock_threshold']) ? (int)$supply['critical_stock_threshold'] : $defaultCritical;

                        if ($qty <= 0) {
                            $badgeClass = 'status-out';
                            $badgeText = 'Out of Stock';
                        } elseif ($qty <= $criticalThreshold) {
                            $badgeClass = 'status-critical';
                            $badgeText = 'Critical';
                        } elseif ($qty <= $lowThreshold) {
                            $badgeClass = 'status-low';
                            $badgeText = 'Low Stock';
                        } else {
                            $badgeClass = 'status-in-stock';
                            $badgeText = 'In Stock';
                        }

                        // Determine card type based on property classification
                        $isSemiExpendable = (stripos($supply['property_classification'] ?? '', 'Semi-Expendable') !== false);
                        $cardIcon = $isSemiExpendable ? 'fa-address-card' : 'fa-file-invoice';
                        $cardTitle = $isSemiExpendable ? 'View Property Card' : 'View Stock Card';
                    ?>
                        <div class="supply-card" data-id="<?php echo htmlspecialchars($supply['supply_id'] ?? $supply['id'] ?? ''); ?>" data-name="<?php echo htmlspecialchars($supply['item'] ?? ''); ?>" data-category="<?php echo htmlspecialchars($categoryName); ?>" data-quantity="<?php echo $qty; ?>" data-stock-no="<?php echo htmlspecialchars($supply['stock_no'] ?? ''); ?>" data-unit="<?php echo htmlspecialchars($supply['unit'] ?? ''); ?>" data-description="<?php echo htmlspecialchars($supply['description'] ?? ''); ?>" data-unit-cost="<?php echo htmlspecialchars($supply['unit_cost'] ?? ''); ?>" data-total-cost="<?php echo htmlspecialchars($supply['total_cost'] ?? ''); ?>" data-status="<?php echo htmlspecialchars($supply['status'] ?? ''); ?>" data-image="<?php echo htmlspecialchars($supply['image_base64'] ?? $basePath . 'img/Bogo_City_logo.png'); ?>" data-property-classification="<?php echo htmlspecialchars($supply['property_classification'] ?? ''); ?>" data-low-threshold="<?php echo $lowThreshold; ?>" data-critical-threshold="<?php echo $criticalThreshold; ?>" data-previous-month="<?php echo (int)($supply['previous_month'] ?? 0); ?>" data-add-stock="<?php echo (int)($supply['add_stock'] ?? 0); ?>" data-issuance="<?php echo (int)($supply['issuance'] ?? 0); ?>">
                            <div class="status-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></div>
                            <img src="<?php echo htmlspecialchars($supply['image_base64'] ?? $basePath . 'img/Bogo_City_logo.png'); ?>" alt="<?php echo htmlspecialchars($supply['item'] ?? ''); ?>">
                            <h3><?php echo htmlspecialchars($supply['item'] ?? 'NO NAME'); ?></h3>
                            <p>Description: <?php echo htmlspecialchars($supply['description'] ?? ''); ?></p>
                            <div class="qty-display <?php echo $badgeClass; ?>">
                                <i class="fas fa-cubes"></i>
                                Quantity: <span class="qty-value"><?php echo htmlspecialchars($supply['quantity'] ?? $supply['previous_month'] ?? '0'); ?></span>
                            </div>
                            <div class="actions">
                                <i class="fas fa-eye icon view-icon" title="View Details"></i>
                                <i class="fas fa-plus icon btn-admin-request-item" title="Issue/Request" style="color: #2A4D88;"></i>
                                <i class="fas <?php echo $cardIcon; ?> icon stock-card-icon" title="<?php echo $cardTitle; ?>"></i>
                                <i class="fas fa-edit icon edit-icon" title="Edit"></i>
                                <i class="fas fa-trash icon delete-icon" title="Delete"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Modal for Item Details -->
    <?php include_once __DIR__ . '/supply_details_modal.php'; ?>

    <!-- Stock Card Preview Modal -->
    <?php include_once __DIR__ . '/stock_card_modal.php'; ?>

    <!-- Modal for Adding New Supply -->
    <?php include_once __DIR__ . '/includes/add_supply_modal.php'; ?>
    
    <!-- Modal for Editing Supply -->
    
    <!-- Modal for Editing Supply -->
    <!-- Modal for Editing Supply -->
    <?php include_once __DIR__ . '/includes/edit_supply_modal.php'; ?>

    <!-- Modal for Admin Requisition Slip -->
    <div id="admin-request-modal" class="modal redesigned-modal-wrapper">
        <div class="modal-content redesigned-modal" style="max-width: 900px;">
            <div class="modal-header-custom">
                <h2 id="request-modal-title"><i class="fas fa-file-invoice"></i> Requisition and Issue Slip (Admin)</h2>
                <span class="close-custom" id="admin-request-close">&times;</span>
            </div>
            
            <div class="modal-body-custom">
                <div class="requisition-form">
                    <div class="form-header">
                        <h3 class="info-title">Employee Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Employee ID</label>
                                <input type="text" id="admin-req-emp-id" class="form-control" placeholder="Enter ID">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" id="admin-req-date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" id="admin-req-name" class="form-control" placeholder="Full Name" readonly>
                            </div>
                             <div class="form-group">
                                <label class="form-label">Designation</label>
                                <input type="text" id="admin-req-designation" class="form-control" placeholder="Position" readonly>
                            </div>
                             <div class="form-group full-width-span">
                                <label class="form-label">Department / Office</label>
                                <input type="text" id="admin-req-department" class="form-control" placeholder="Department" readonly>
                            </div>
                            <div class="form-group full-width-span">
                                <label class="form-label">Purpose of Request</label>
                                <textarea id="admin-req-purpose" class="form-control" placeholder="Enter the purpose of this issuance"></textarea>
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
                                <tbody id="admin-request-table-body">
                                    <tr id="admin-empty-request-row">
                                        <td colspan="6" style="text-align: center; padding: 30px; color: #a0aec0;">No items added to request yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="request-form-actions">
                         <button type="button" id="admin-clear-request-btn" class="btn-clear-request">Clear All</button>
                         <button type="button" id="admin-submit-request-btn" class="btn-submit-request">
                            <i class="fas fa-paper-plane"></i> Submit Requisition
                         </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Premium Admin Requisition Modal Styles */
        .redesigned-modal-wrapper.modal.active {
            display: flex !important;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.6) !important;
        }
        
        #admin-request-modal .redesigned-modal {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 25px 30px;
            text-align: left;
            position: relative;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header-custom h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: white !important;
            display: flex !important;
            align-items: center;
            gap: 15px;
            letter-spacing: -0.01em;
            visibility: visible !important;
            opacity: 1 !important;
            white-space: normal;
            line-height: 1.2;
            word-break: keep-all;
        }

        #request-modal-title {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .modal-header-custom h2 i {
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px;
            border-radius: 12px;
            backdrop-filter: blur(4px);
        }

        .close-custom {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            cursor: pointer;
            color: white;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .close-custom:hover { opacity: 1; }

        .modal-body-custom {
            padding: 30px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 10px 0;
        }
        
        .full-width-span { grid-column: 1 / -1; }
        
        .form-label { 
            display: block; 
            font-weight: 700; 
            font-size: 0.75rem; 
            color: #718096; 
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .form-control { 
            width: 100% !important; 
            padding: 12px 15px !important; 
            border: 1px solid #e2e8f0 !important; 
            border-radius: 8px !important; 
            font-size: 0.95rem !important;
            background-color: #f8fafc !important;
            transition: all 0.2s;
        }
        
        .redesigned-modal-wrapper .form-control:focus {
            border-color: #059669 !important;
            background-color: #fff !important;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1) !important;
            outline: none;
        }

        .info-title { 
            color: #1a202c; 
            margin-bottom: 20px; 
            font-size: 1.1rem; 
            font-weight: 700;
            border-bottom: 2px solid #edf2f7; 
            padding-bottom: 10px; 
            margin-top: 25px; 
        }

        .request-items-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 10px; 
            border: 1px solid #edf2f7;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .request-items-table th { 
            background: #f8fafc; 
            padding: 15px; 
            font-weight: 700; 
            text-align: left; 
            color: #4a5568;
            border-bottom: 2px solid #edf2f7; 
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .request-items-table td { 
            padding: 15px; 
            border-bottom: 1px solid #edf2f7; 
            color: #2d3748;
            font-size: 0.95rem;
        }

        .request-form-actions { 
            margin-top: 40px; 
            display: flex; 
            justify-content: flex-end; 
            gap: 15px; 
            padding-top: 20px;
            border-top: 1px solid #edf2f7;
        }
        
        .btn-clear-request { 
            padding: 12px 25px; 
            border: 1px solid #e2e8f0; 
            background: #fff; 
            color: #718096; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-clear-request:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
            color: #4a5568;
        }
        
        .btn-submit-request { 
            padding: 12px 30px; 
            border: none; 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
            color: white; 
            border-radius: 8px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-weight: 700; 
            transition: transform 0.2s, background 0.2s;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
        }
        
        .btn-submit-request:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.3);
        }
        
        .btn-submit-request:active {
            transform: translateY(0);
        }

        .btn-admin-request-item {
            transition: all 0.2s !important;
        }
        .btn-admin-request-item:hover {
            background-color: #059669 !important;
            color: white !important;
            transform: scale(1.1);
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
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); /* Emerald Gradient */
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.3);
            cursor: pointer;
            border: none;
            position: relative;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: auto;
        }

        .fab-button:hover {
            transform: scale(1.1) translateY(-5px);
            opacity: 0.95;
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.4);
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
            0% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(5, 150, 105, 0); }
            100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }
        }

        .fab-pulse {
            animation: fabPulse 0.6s ease-out;
        }
    </style>
    <!-- Admin Floating Action Button -->
    <div class="fab-request-container" id="admin-fab-container">
        <div class="fab-label">Requisition Slip (Admin)</div>
        <button class="fab-button admin-fab-button" id="admin-fab-view-request" title="Admin Requisition Slip">
            <i class="fas fa-clipboard-check fab-icon"></i>
            <span class="fab-badge" id="admin-fab-badge">0</span>
        </button>
    </div>
    <script src="<?php echo $root; ?>js/sidebar.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/supply_modals.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $root; ?>js/admin_requisition.js?v=<?php echo time(); ?>"></script>
    <?php include_once __DIR__ . '/../includes/logout_modal.php'; ?>
</body>
</html>