<?php
// Shared Notification Logic and UI

// 1. Fetch Data if not already available
if (!isset($allSupplies) && !isset($supplies)) {
    require_once __DIR__ . '/../../model/supplyModel.php';
    $supplyModelForNotify = new SupplyModel();
    $suppliesForNotify = $supplyModelForNotify->getAllSupplies();
} else {
    $suppliesForNotify = isset($supplies) ? $supplies : $allSupplies;
}

// Define basePath for JS if not already defined
$basePath = (strpos($_SERVER['SCRIPT_NAME'], '/view/') !== false) ? '../' : '';
?>
<script>
    if (typeof basePath === 'undefined') {
        var basePath = '<?php echo addslashes($basePath); ?>';
    }
</script>
<?php

// 2. Calculate Alerts
$alertItems = [];
if (!isset($defaultLow)) {
     require_once __DIR__ . '/../../model/settingsModel.php';
     $settingsModelForNotify = new SettingsModel();
     $settingsForNotify = $settingsModelForNotify->getAllSettings();
     $defaultLow = $settingsForNotify['default_low_stock'] ?? 10;
     $defaultCritical = $settingsForNotify['default_critical_stock'] ?? 5;
}

foreach ($suppliesForNotify as $supply) {
    $qty = isset($supply['quantity']) ? (int)$supply['quantity'] : (isset($supply['previous_month']) ? (int)$supply['previous_month'] : 0);
    
    $lowThreshold = isset($supply['low_stock_threshold']) ? (int)$supply['low_stock_threshold'] : $defaultLow;
    $criticalThreshold = isset($supply['critical_stock_threshold']) ? (int)$supply['critical_stock_threshold'] : $defaultCritical;
    
    if ($qty <= 0) {
        $supply['alert_type'] = 'Out of Stock';
        $supply['notif_type'] = 'stock';
        $alertItems[] = $supply;
    } elseif ($qty <= $lowThreshold) {
        $supply['alert_type'] = ($qty <= $criticalThreshold) ? 'Critical' : 'Low Stock';
        $supply['notif_type'] = 'stock';
        $alertItems[] = $supply;
    }
}

// 3. Fetch Pending Requisitions
require_once __DIR__ . '/../../model/requisitionModel.php';
$reqModelForNotify = new RequisitionModel();
$pendingReqList = $reqModelForNotify->getPendingRequisitions(10);

$requisitionNotifications = [];
foreach ($pendingReqList as $req) {
    $requisitionNotifications[] = [
        'id' => $req['requisition_id'],
        'item' => $req['requisition_no'],
        'alert_type' => 'New Request from ' . $req['first_name'] . ' ' . $req['last_name'],
        'notif_type' => 'requisition',
        'subtext' => $req['department_name'] . ' - ' . date('M j, Y', strtotime($req['created_at']))
    ];
}

// Merge notifications
$allNotifications = array_merge($requisitionNotifications, $alertItems);
$totalNotifCount = count($allNotifications);
?>

<!-- Notification Styles -->
<style>
    /* Notification Styles Shared */
    .notification-container {
        position: relative;
        margin-right: 20px;
    }

    /* Keyframes for Bell Shake */
    @keyframes bellShake {
        0% { transform: rotate(0); }
        15% { transform: rotate(5deg); }
        30% { transform: rotate(-5deg); }
        45% { transform: rotate(4deg); }
        60% { transform: rotate(-4deg); }
        75% { transform: rotate(2deg); }
        85% { transform: rotate(-2deg); }
        92% { transform: rotate(1deg); }
        100% { transform: rotate(0); }
    }

    /* Keyframes for Badge Pulse */
    @keyframes pulseRed {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(231, 76, 60, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
    }

    .notification-bell {
        cursor: pointer;
        position: relative;
        font-size: 1.5rem;
        color: #12086F; /* Deep Navy to match Header */
        transition: color 0.3s ease;
    }

    .notification-bell.has-alerts {
        color: #e74c3c; /* Red color when alerts exist */
        animation: bellShake 4s infinite; /* Shake every 4 seconds */
    }

    .notification-bell:hover {
        color: #10b981; /* Emerald on hover */
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #e74c3c;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.7rem;
        font-weight: bold;
        border: 2px solid white;
        animation: pulseRed 2s infinite; /* Continuous pulse */
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .notification-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        width: 320px; /* Slightly wider */
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(2, 44, 34, 0.15); /* Deep Emerald Shadow */
        z-index: 99999;
        margin-top: 15px; /* More spacing */
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    
    .notification-dropdown::before {
        content: '';
        position: absolute;
        top: -8px;
        right: 15px;
        width: 16px;
        height: 16px;
        background: white;
        transform: rotate(45deg);
        border-left: 1px solid #e2e8f0;
        border-top: 1px solid #e2e8f0;
    }

    .notification-dropdown.active {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }

    .notification-header {
        padding: 15px;
        background: linear-gradient(to right, #f8f9fa, #ffffff);
        border-bottom: 1px solid #e2e8f0;
        font-weight: bold;
        color: #12086F;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-list {
        max-height: 350px;
        overflow-y: auto;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .notification-item {
        padding: 15px;
        border-bottom: 1px solid #f1f3f5;
        transition: background 0.2s;
        cursor: pointer;
        display: flex;
        align-items: flex-start; /* Align top for better multiline */
        gap: 12px;
        color: #333;
    }

    .notification-item:hover {
        background-color: #f8f9fa;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-icon {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 5px; /* Align with text */
    }

    .alert-critical { background-color: #e74c3c; box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2); }
    .alert-low { background-color: #f39c12; box-shadow: 0 0 0 2px rgba(243, 156, 18, 0.2); }
    .alert-out { background-color: #7f8c8d; box-shadow: 0 0 0 2px rgba(127, 140, 141, 0.2); }
    .alert-requisition { background-color: #9b59b6; box-shadow: 0 0 0 2px rgba(155, 89, 182, 0.2); }

    .notification-content h4 {
        margin: 0 0 4px 0;
        font-size: 0.95rem;
        color: #2c3e50;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .notification-content h4 i {
        font-size: 0.8em;
        opacity: 0.6;
    }

    .notification-content p {
        margin: 0;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .empty-notifications {
        padding: 30px;
        text-align: center;
        color: #95a5a6;
        font-style: italic;
    }

    /* Toast Notification Styles */
        z-index: 20000;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .toast-notif {
        min-width: 300px;
        background: white;
        color: #2c3e50;
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 15px;
        border-left: 5px solid #9b59b6;
        cursor: pointer;
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .toast-notif.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast-icon {
        width: 40px;
        height: 40px;
        background: #f3e5f5;
        color: #9b59b6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .toast-content {
        flex: 1;
    }

    .toast-content h5 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #12086F;
    }

    .toast-content p {
        margin: 3px 0 0 0;
        font-size: 0.85rem;
        color: #64748b;
    }

    .toast-close {
        color: #cbd5e1;
        font-size: 1.1rem;
        transition: color 0.2s;
        padding: 5px;
    }

    .toast-close:hover {
        color: #64748b;
    }
</style>

<!-- Notification HTML -->
<div class="notification-container">
    <div class="notification-bell <?php echo $totalNotifCount > 0 ? 'has-alerts' : ''; ?>" onclick="toggleNotifications(event)" title="<?php echo $totalNotifCount; ?> Notifications">
        <i class="fas fa-bell"></i>
        <?php if ($totalNotifCount > 0): ?>
            <span class="notification-badge"><?php echo $totalNotifCount; ?></span>
        <?php endif; ?>
    </div>
    <div class="notification-dropdown" id="notification-dropdown">
        <div class="notification-header">
            <span>Notifications</span>
            <span style="font-size: 0.8rem; font-weight: normal; color: #666;"><?php echo $totalNotifCount; ?> total</span>
        </div>
        <ul class="notification-list">
            <?php if ($totalNotifCount === 0): ?>
                <li class="empty-notifications">No new notifications.</li>
            <?php else: ?>
                <?php foreach ($allNotifications as $notif): 
                    $dotClass = '';
                    $icon = '';
                    $onClick = '';
                    
                    if ($notif['notif_type'] === 'requisition') {
                        $dotClass = 'alert-requisition';
                        $icon = '<i class="fas fa-file-invoice"></i>';
                        $onClick = "handleRequisitionClick(" . $notif['id'] . ")";
                    } else {
                        if ($notif['alert_type'] === 'Out of Stock') $dotClass = 'alert-out';
                        elseif ($notif['alert_type'] === 'Critical') $dotClass = 'alert-critical';
                        else $dotClass = 'alert-low';
                        $icon = '<i class="fas fa-box"></i>';
                        $onClick = "handleNotificationClick('" . addslashes(htmlspecialchars($notif['item'])) . "')";
                    }
                ?>
                    <li class="notification-item" onclick="<?php echo $onClick; ?>">
                        <div class="notification-icon <?php echo $dotClass; ?>"></div>
                        <div class="notification-content">
                            <h4><?php echo $icon; ?> <?php echo htmlspecialchars($notif['item']); ?></h4>
                            <p><?php echo $notif['alert_type']; ?></p>
                            <?php if (isset($notif['subtext'])): ?>
                                <p style="font-size: 0.75rem; color: #95a5a6; margin-top: 2px;"><?php echo htmlspecialchars($notif['subtext']); ?></p>
                            <?php else: ?>
                                <p style="font-size: 0.75rem; color: #a0aec0;">Qty: <?php echo $notif['quantity'] ?? $notif['previous_month'] ?? 0; ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Toast Container -->
<div id="request-toast-container"></div>

<!-- Audio Assets -->
<audio id="notif-sound" preload="auto">
    <source src="<?php echo $root; ?>assets/audio/notification.mp3" type="audio/mpeg">
    <!-- Fallback to a CDN sound if local file is missing -->
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

<!-- Global Alert Banner Logic -->
<?php
$criticalCount = 0;
$outCount = 0;
$lowStockCount = 0;
foreach ($alertItems as $item) {
    if ($item['alert_type'] === 'Critical') $criticalCount++;
    elseif ($item['alert_type'] === 'Out of Stock') $outCount++;
    elseif ($item['alert_type'] === 'Low Stock') $lowStockCount++;
}
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const outCount = <?php echo $outCount; ?>;
        const criticalCount = <?php echo $criticalCount; ?>;
        const lowStockCount = <?php echo $lowStockCount; ?>;
        const pendingReqCount = <?php echo count($pendingReqList); ?>; // This is currently limited to 10 in head_notification.php, but let's use it for the pill. 
        // Actually, let's fetch the REAL total pending count for the pill if possible.
        <?php 
            $fullStats = $reqModelForNotify->getRequisitionStats();
            $realPendingCount = $fullStats['pending'] ?? 0;
        ?>
        const realPendingCount = <?php echo $realPendingCount; ?>;

        if (outCount > 0 || criticalCount > 0 || lowStockCount > 0 || realPendingCount > 0) {
            const container = document.createElement('div');
            container.className = 'header-alert-summary';
            container.style.cssText = `
                display: flex;
                align-items: center;
                gap: 10px;
                margin-left: 25px;
                padding: 6px 16px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 25px;
                font-size: 0.95rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s ease;
                animation: fadeIn 0.5s ease-out;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            `;
            container.onclick = () => window.location.href = '<?php echo $root; ?>inventory?search=alert';
            container.title = "Click to view all stock alerts";

            let html = '<i class="fas fa-exclamation-circle" style="color: #64748b; margin-right: 4px; font-size: 1.1rem;"></i>';
            
            if (outCount > 0) {
                html += `
                    <span style="color: #ef4444; background: #fee2e2; padding: 4px 12px; border-radius: 15px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-ban" style="font-size: 0.85rem;"></i> ${outCount} Out
                    </span>`;
            }
            if (criticalCount > 0) {
                html += `
                    <span style="color: #f59e0b; background: #fef3c7; padding: 4px 12px; border-radius: 15px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 0.85rem;"></i> ${criticalCount} Critical
                    </span>`;
            }
            if (lowStockCount > 0) {
                html += `
                    <span style="color: #10b981; background: #d1fae5; padding: 4px 12px; border-radius: 15px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-info-circle" style="font-size: 0.85rem;"></i> ${lowStockCount} Low
                    </span>`;
            }

            if (realPendingCount > 0) {
                html += `
                    <span onclick="event.stopPropagation(); window.location.href='<?php echo $root; ?>requests'" style="color: #6b21a8; background: #f3e8ff; padding: 4px 12px; border-radius: 15px; display: flex; align-items: center; gap: 6px; border: 1px solid #d8b4fe;">
                        <i class="fas fa-file-invoice" style="font-size: 0.85rem;"></i> ${realPendingCount} Pending
                    </span>`;
            }

            container.innerHTML = html;

            const headerTitle = document.querySelector('.header h1');
            if (headerTitle) {
                headerTitle.style.display = 'inline-block';
                headerTitle.after(container);
            }
        }
    });

    // Audio Notification Helper
    function playNotificationSound() {
        const sound = document.getElementById('notif-sound');
        if (sound) {
            // Reset and play
            sound.currentTime = 0;
            sound.play().catch(e => console.debug('Audio playback blocked by browser policy until user interacts:', e));
        }
    }


    // Add Hover Styles and Animations
    const alertStyle = document.createElement('style');
    alertStyle.innerHTML = `
        .header-alert-summary:hover {
            background: #f1f5f9;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
    `;
    document.head.appendChild(alertStyle);

    // Sound notifications are now handled by the checkNewRequests polling system
    // This ensures sound only plays for genuinely NEW requests, not on every page load
</script>


<!-- Notification JS -->
<script>
    function toggleNotifications(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('notification-dropdown');
        if(dropdown) dropdown.classList.toggle('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('notification-dropdown');
        // Only close if it exists and is active
        if (dropdown && dropdown.classList.contains('active')) {
             if (!event.target.closest('.notification-container')) {
                dropdown.classList.remove('active');
            }
        }
    });

    function handleNotificationClick(itemName) {
        // Close dropdown
        const dropdown = document.getElementById('notification-dropdown');
        if(dropdown) dropdown.classList.remove('active');

        // Logic dependent on current page
        const currentPage = window.location.pathname.split("/").pop();
        
        if (currentPage === 'inventory') {
            // If already on supply page, trigger filter
            if (typeof filterByAlert === 'function') {
                filterByAlert(itemName);
            }
        } else {
            // Redirect to supply page with search query
            // Encode itemName to handle spaces and special chars safely
            window.location.href = '<?php echo $root; ?>inventory?search=' + encodeURIComponent(itemName);
        }
    }

    function handleRequisitionClick(id) {
        // Close dropdown
        const dropdown = document.getElementById('notification-dropdown');
        if(dropdown) dropdown.classList.remove('active');
        
        // Redirect to request page
        window.location.href = '<?php echo $root; ?>requests?id=' + id;
    }
    
    // Auto-process URL search param if on supply page (to handle redirect from other pages)
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split("/").pop();
        if (currentPage === 'inventory' || currentPage === '') {
             const urlParams = new URLSearchParams(window.location.search);
             const searchParam = urlParams.get('search');
             
             // Special case for "alert" keyword from banner
             if (searchParam === 'alert') {
                 // Trigger the "Critical/Low Stock" filter button if it exists?
             } else if (searchParam) {
                 const searchInput = document.getElementById('search');
                 if (searchInput) {
                     searchInput.value = searchParam;
                     const event = new Event('input', { bubbles: true });
                     searchInput.dispatchEvent(event);
                     const catFilter = document.getElementById('filter-category');
                     if(catFilter) catFilter.value = '';
                 }
             }
        }

        // --- NEW REQUEST ALERT SYSTEM ---
        let lastKnownLatestId = localStorage.getItem('last_requisition_id');
        
        // Initial setup for the very first visit
        if (!lastKnownLatestId) {
            // Get the list of IDs from the current PHP notifications
            const reqNotifs = <?php echo json_encode($requisitionNotifications); ?>;
            if (reqNotifs.length > 0) {
                lastKnownLatestId = reqNotifs[0].id;
                localStorage.setItem('last_requisition_id', lastKnownLatestId);
            }
        }

        function showNotificationToast(count, latestId, playSound = false) {
            const container = document.getElementById('request-toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'toast-notif';
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="toast-content">
                    <h5>New Request Received!</h5>
                    <p>There are currently ${count} pending requisitions waiting for review.</p>
                </div>
                <div class="toast-close">
                    <i class="fas fa-times"></i>
                </div>
            `;
            
            // Handle clicking the toast body to navigate
            toast.querySelector('.toast-content').onclick = (e) => {
                e.stopPropagation();
                window.location.href = 'request.php?id=' + latestId;
            };

            // Handle close button
            toast.querySelector('.toast-close').onclick = (e) => {
                e.stopPropagation();
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            };

            container.appendChild(toast);
            
            // Animation trigger
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Trigger Audio Alert only if playSound is true
            if (playSound) {
                playNotificationSound();
            }
            
            // Auto-hide
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 500);
                }
            }, 8000);
        }

        let firstCheck = true;
        let lastToastTime = Date.now();

        function checkNewRequests() {
            const fetchPath = (typeof basePath !== 'undefined' ? basePath : '') + 'api/check_new_requests.php';
            
            fetch(fetchPath)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.count > 0) {
                        const latestId = data.latest_id;
                        const storedId = parseInt(localStorage.getItem('last_requisition_id') || 0);
                        const currentTime = Date.now();
                        
                        // Condition for immediate notification: New request ID
                        const isNew = latestId > storedId;
                        // Condition for periodic notification: 30 seconds elapsed since last toast
                        const isReminder = (currentTime - lastToastTime) >= 30000;

                        // Show toast and play sound if:
                        // - First check (page load with pending requests)
                        // - New request arrives
                        // - Reminder time (every 30 seconds if there are pending requests)
                        if (firstCheck || isNew || isReminder) {
                            showNotificationToast(data.count, latestId, true);
                            lastToastTime = currentTime;
                            
                            if (isNew) {
                                localStorage.setItem('last_requisition_id', latestId);
                            }
                        }
                        
                        firstCheck = false;

                        // Update the bell icon badge
                        const badge = document.querySelector('.notification-badge');
                        const bell = document.querySelector('.notification-bell');
                        if (badge) {
                            badge.textContent = data.count;
                        } else if (bell) {
                            bell.classList.add('has-alerts');
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.count;
                            bell.appendChild(newBadge);
                        }
                    } else {
                        firstCheck = false;
                        // If no pending, reset reminder timer to allow immediate toast if one appears later
                        lastToastTime = 0; 
                    }
                })
                .catch(err => console.debug('Polling error:', err));
        }

        // Check frequently (every 5s) to detect NEW requests immediately
        setInterval(checkNewRequests, 5000);
        
        // Initial check on load
        setTimeout(checkNewRequests, 1000);
    });
</script>
