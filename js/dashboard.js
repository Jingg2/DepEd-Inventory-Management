console.log('dashboard.js: Script evaluation started');
window.addEventListener('error', function (e) {
    console.error('Global JS Error:', e.message, 'at', e.filename, ':', e.lineno);
});

// Function to create a supply card element
function createSupplyCard(supply) {
    const card = document.createElement('div');
    card.className = 'supply-card';
    card.setAttribute('data-id', supply.id || '');
    card.setAttribute('data-name', supply.item || '');
    card.setAttribute('data-category', supply.category || '');
    card.setAttribute('data-quantity', supply.quantity || '');
    card.setAttribute('data-stock-no', supply.stock_no || '');
    card.setAttribute('data-unit', supply.unit || '');
    card.setAttribute('data-description', supply.description || '');
    card.setAttribute('data-unit-cost', supply.unit_cost || '');
    card.setAttribute('data-total-cost', supply.total_cost || '');
    card.setAttribute('data-status', supply.status || '');
    card.setAttribute('data-low-threshold', supply.low_stock_threshold || '10');
    card.setAttribute('data-critical-threshold', supply.critical_stock_threshold || '5');

    // Determine status badge
    let badgeClass = '';
    let badgeText = '';
    const qty = parseInt(supply.quantity) || parseInt(supply.previous_month) || 0;
    const lowT = parseInt(supply.low_stock_threshold) || 10;
    const critT = parseInt(supply.critical_stock_threshold) || 5;

    if (qty <= 0) {
        badgeClass = 'status-out';
        badgeText = 'Out of Stock';
    } else if (qty <= critT) {
        badgeClass = 'status-critical';
        badgeText = 'Critical';
    } else if (qty <= lowT) {
        badgeClass = 'status-low';
        badgeText = 'Low Stock';
    } else {
        badgeClass = 'status-in-stock';
        badgeText = 'In Stock';
    }

    card.innerHTML = `
        <div class="status-badge ${badgeClass}">${badgeText}</div>
        <img src="${supply.image_base64 || basePath + 'img/Bogo_City_logo.png'}" alt="${supply.item || ''}">
        <h3>${supply.item || ''}</h3>
        <p>Description: ${supply.description || ''}</p>
        <div class="qty-display ${badgeClass}">
            <i class="fas fa-cubes"></i>
            Quantity: <span class="qty-value">${supply.quantity || supply.previous_month || '0'}</span>
        </div>
        <div class="actions">
            <i class="fas fa-eye icon view-icon" title="View Details"></i>
            <i class="fas fa-edit icon edit-icon" title="Edit"></i>
            <i class="fas fa-trash icon delete-icon" title="Delete"></i>
        </div>
    `;

    // Event listeners are handled by global event delegation for better performance and consistency
    // See "Modal functionality", "Handle Stock Card icon clicks", and "Handle edit icon clicks" sections below

    return card;
}



// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function () {
    console.log('dashboard.js: DOMContentLoaded fired');
    // Get basePath from global variable or default to empty
    const basePath = typeof window.basePath !== 'undefined' ? window.basePath : '';
    // Sidebar toggle logic moved to js/sidebar.js

    // Supply page search and filter
    const searchBar = document.getElementById('search');
    const filterCategory = document.getElementById('filter-category');
    const filterPropertyClassification = document.getElementById('filter-property-classification');

    // Status filter state
    let currentStatusFilter = 'all'; // 'all', 'low', 'out'

    if (searchBar || filterCategory) {
        function filterCards() {
            console.log("filterCards running...");
            const query = searchBar ? searchBar.value.toLowerCase() : '';
            const categoryFilterValue = filterCategory ? filterCategory.value.toLowerCase() : '';
            const propertyClassFilterValue = filterPropertyClassification ? filterPropertyClassification.value.toLowerCase() : '';

            // support for both grouped (dashboard) and flat (controlled assets) layouts
            const categorySections = document.querySelectorAll('.category-section');
            const flatCards = document.querySelectorAll('.supply-cards .supply-card, .supply-card:not(.category-section .supply-card)');

            // Helper to check validity
            const checkCard = (card) => {
                const name = card.getAttribute('data-name').toLowerCase();
                const category = (card.getAttribute('data-category') || "").toLowerCase().trim();
                const propertyClass = (card.getAttribute('data-property-classification') || "").toLowerCase().trim();
                const qty = parseInt(card.getAttribute('data-quantity')) || 0;

                const matchesSearch = name.includes(query);
                const matchesCategory = categoryFilterValue === '' || category === categoryFilterValue;
                const matchesPropertyClass = propertyClassFilterValue === '' || propertyClass === propertyClassFilterValue;

                // Status filtering logic
                let matchesStatus = true;
                if (currentStatusFilter === 'low') {
                    const lowT = parseInt(card.getAttribute('data-low-threshold')) || 10;
                    const critT = parseInt(card.getAttribute('data-critical-threshold')) || 5;
                    matchesStatus = (qty > critT && qty <= lowT);
                } else if (currentStatusFilter === 'out') {
                    matchesStatus = (qty <= 0);
                } else if (currentStatusFilter === 'critical') {
                    const critT = parseInt(card.getAttribute('data-critical-threshold')) || 5;
                    matchesStatus = (qty > 0 && qty <= critT);
                }

                if (matchesSearch && matchesCategory && matchesPropertyClass && matchesStatus) {
                    card.style.display = 'block';
                    return true;
                } else {
                    card.style.display = 'none';
                    return false;
                }
            };

            // Process Grouped Layout (Categories or Receipts)
            const groupedSections = document.querySelectorAll('.category-section, .receipt-group');
            if (groupedSections.length > 0) {
                groupedSections.forEach(section => {
                    const supplyCards = section.querySelectorAll('.supply-card');
                    let visibleCardsInSection = 0;
                    supplyCards.forEach(card => {
                        if (checkCard(card)) visibleCardsInSection++;
                    });
                    // Hide the whole section if no cards match
                    section.style.display = (visibleCardsInSection > 0) ? 'block' : 'none';
                });
            }

            // Process Flat Layout
            if (flatCards.length > 0) {
                flatCards.forEach(card => checkCard(card));

                // Handle "No Results" message for flat layout
                const noResultsMsg = document.querySelector('.no-results-message'); // Ensure this element exists in logic if needed
                const anyVisible = Array.from(flatCards).some(c => c.style.display !== 'none');

                // Simple toggle for a generic no-results div if it exists
                const noResultsDiv = document.querySelector('.no-results');
                if (noResultsDiv) {
                    // Only show if it's NOT the initial "no data" state but a search result state
                    // logic here depends on specific requirements, keeping simple for now
                }
            }
        }

        // Add event listeners for stat cards
        const statTotal = document.getElementById('stat-total');
        const statLow = document.getElementById('stat-low-stock');
        const statOut = document.getElementById('stat-out-of-stock');

        function updateStatCardUI(activeId) {
            [statTotal, statLow, statOut].forEach(card => {
                if (!card) return;
                if (card.id === activeId) {
                    card.style.border = '2px solid #2A4D88';
                    card.style.transform = 'scale(1.02)';
                    card.style.boxShadow = '0 4px 12px rgba(42, 77, 136, 0.2)';
                } else {
                    card.style.border = 'none';
                    card.style.transform = 'scale(1)';
                    card.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.1)';
                }
            });
        }

        if (statTotal) {
            statTotal.addEventListener('click', () => {
                currentStatusFilter = 'all';
                updateStatCardUI('stat-total');
                filterCards();
            });
        }

        if (statLow) {
            statLow.addEventListener('click', () => {
                if (currentStatusFilter === 'low') {
                    currentStatusFilter = 'all';
                    updateStatCardUI('stat-total');
                } else {
                    currentStatusFilter = 'low';
                    updateStatCardUI('stat-low-stock');
                }
                filterCards();
            });
        }

        if (statOut) {
            statOut.addEventListener('click', () => {
                if (currentStatusFilter === 'out') {
                    currentStatusFilter = 'all';
                    updateStatCardUI('stat-total');
                } else {
                    currentStatusFilter = 'out';
                    updateStatCardUI('stat-out-of-stock');
                }
                filterCards();
            });
        }

        searchBar.addEventListener('input', filterCards);
        filterCategory.addEventListener('change', filterCards);
        filterPropertyClassification.addEventListener('change', filterCards);

        // Run on load
        filterCards();
    }


    // Modal interactions are now handled in js/supply_modals.js


    // Initialize charts if on dashboard
    if (document.getElementById('stockLevelsChart')) {
        initDashboardCharts(basePath);
    }
});

/**
 * Advanced Dashboard Charts Initialization
 */
/**
 * Advanced Dashboard Charts Initialization
 */
// Theme Palette based on css/dashboard.css
const ChartTheme = {
    primaryEmerald: '#059669',
    secondaryEmerald: '#10b981',
    accentEmerald: '#34d399',
    deepBlue: '#1e3a8a', // Deep Blue for contrast
    bgLight: '#f8fafc',
    textMain: '#1e293b',
    textMuted: '#64748b',
    fontFamily: "'Outfit', 'Inter', sans-serif"
};

// Global Chart Defaults
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = ChartTheme.fontFamily;
    Chart.defaults.color = ChartTheme.textMuted;
    Chart.defaults.scale.grid.color = 'rgba(226, 232, 240, 0.6)';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 600, family: ChartTheme.fontFamily };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 13, family: ChartTheme.fontFamily };
    Chart.defaults.plugins.legend.labels.font = { size: 12, weight: 500, family: ChartTheme.fontFamily };
}

/**
 * Advanced Dashboard Charts Initialization
 */
function initDashboardCharts(basePath) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        return;
    }

    const chartContainers = document.querySelectorAll('.chart-container canvas');
    chartContainers.forEach(canvas => {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = ChartTheme.textMuted;
        ctx.font = '14px ' + ChartTheme.fontFamily;
        ctx.textAlign = 'center';
        ctx.fillText('Loading analytics...', canvas.width / 2, canvas.height / 2);
    });

    console.log('Initializing Dashboard Charts with basePath:', basePath);

    fetch(basePath + 'api/get_dashboard_charts_data.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
            return response.json();
        })
        .then(result => {
            if (!result.success || !result.data) {
                throw new Error("API returned no data");
            }
            renderDashboardCharts(result.data);
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
            const grids = document.querySelector('.charts-grid');
            if (grids) {
                // Ensure we don't duplicate error messages
                if (!grids.querySelector('.error-message-box')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'full-width error-message-box';
                    errorDiv.style.cssText = 'background: #fff5f5; color: #c53030; padding: 20px; border-radius: 12px; border: 1px solid #feb2b2; margin-bottom: 20px; grid-column: 1 / -1; box-shadow: 0 4px 6px rgba(0,0,0,0.05);';
                    errorDiv.innerHTML = `<h3 style="margin:0 0 10px 0; display:flex; align-items:center; gap:10px;"><i class="fas fa-exclamation-triangle"></i> Limited Data Access</h3>
                                         <p style="margin:0 0 10px 0;">We encountered an issue loading real-time analytics. Please check your connection.</p>
                                         <button onclick="location.reload()" style="background: #c53030; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight:600;"><i class="fas fa-sync-alt"></i> Retry</button>`;
                    grids.prepend(errorDiv);
                }
            }
        });
}

/**
 * Render all dashboard charts with the provided data object
 */
function renderDashboardCharts(data) {
    console.log("Rendering charts with data:", data);

    // Helper to create gradients
    const createGradient = (ctx, colorStart, colorEnd) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, colorStart);
        gradient.addColorStop(1, colorEnd);
        return gradient;
    };

    // 1. Stock Levels Analysis (Horizontal Bar)
    try {
        const ctx1 = document.getElementById('stockLevelsChart');
        if (ctx1) {
            const itemCount = (data.stockLevels || []).length;
            // Dynamic height: ensure at least 300px, or 40px per item
            const chartHeight = Math.max(300, itemCount * 40);

            // Adjust wrapper height
            const innerWrapper = ctx1.closest('.chart-inner-wrapper') || ctx1.parentElement;
            if (innerWrapper) {
                innerWrapper.style.height = chartHeight + 'px';
                innerWrapper.style.maxHeight = 'none'; // removing limit
            }
            ctx1.height = chartHeight;

            const chartCtx = ctx1.getContext('2d');
            const gradientSafe = createGradient(chartCtx, '#3b82f6', '#2563eb');
            const gradientCaution = createGradient(chartCtx, '#f59e0b', '#d97706');
            const gradientCritical = createGradient(chartCtx, '#f97316', '#ea580c');
            const gradientOut = createGradient(chartCtx, '#ef4444', '#dc2626');

            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: (data.stockLevels || []).map(item => item.name),
                    datasets: [{
                        label: 'Current Quantity',
                        data: (data.stockLevels || []).map(item => item.qty),
                        backgroundColor: (data.stockLevels || []).map(item => {
                            if (item.urgency === 'Out of Stock') return gradientOut;
                            if (item.urgency === 'Critical') return gradientCritical;
                            if (item.urgency === 'Caution') return gradientCaution;
                            return gradientSafe;
                        }),
                        borderRadius: 6,
                        barThickness: 16,
                        maxBarThickness: 24
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ` Quantity: ${context.raw} units`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11, family: ChartTheme.fontFamily },
                                color: ChartTheme.textMuted
                            }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 12, weight: 500, family: ChartTheme.fontFamily },
                                color: ChartTheme.textMain,
                                autoSkip: false
                            }
                        }
                    },
                    animation: {
                        duration: 1200,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }
    } catch (e) { console.error('Error creating Stock Levels chart:', e); }

    // 2. Stock Distribution (Donut)
    try {
        const ctx2 = document.getElementById('distributionChart');
        if (ctx2 && data.categoryDistribution) {
            // "Wow" Palette for Distribution
            const palette = [
                '#10b981', // Emerald
                '#3b82f6', // Blue
                '#8b5cf6', // Violet
                '#f59e0b', // Amber
                '#06b6d4', // Cyan
                '#ec4899', // Pink
                '#6366f1'  // Indigo
            ];

            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(data.categoryDistribution),
                    datasets: [{
                        data: Object.values(data.categoryDistribution),
                        backgroundColor: palette,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.chart._metasets[context.datasetIndex].total;
                                    const percentage = Math.round((value / total) * 100) + '%';
                                    return ` ${label}: ${value} (${percentage})`;
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        }
    } catch (e) { console.error('Error creating Distribution chart:', e); }

    // 3. Inventory Value Analysis (Bar)
    try {
        const ctx3 = document.getElementById('valueChart');
        if (ctx3 && data.inventoryValue) {
            const chartCtx = ctx3.getContext('2d');
            const gradientMoney = createGradient(chartCtx, '#10b981', '#059669'); // Emerald Gradient

            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: Object.keys(data.inventoryValue),
                    datasets: [{
                        label: 'Total Value',
                        data: Object.values(data.inventoryValue),
                        backgroundColor: gradientMoney,
                        borderRadius: 8,
                        barThickness: 40,
                        maxBarThickness: 60
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' Value: ₱' + context.raw.toLocaleString(undefined, { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '₱' + value.toLocaleString(),
                                font: { size: 11 },
                                color: ChartTheme.textMuted
                            },
                            grid: {
                                color: 'rgba(226, 232, 240, 0.4)',
                                borderDash: [4, 4]
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 },
                                color: ChartTheme.textMuted
                            }
                        }
                    }
                }
            });
        }
    } catch (e) { console.error('Error creating Value chart:', e); }

    // 4. Employee Requisitions (Bar Chart)
    try {
        const ctx4 = document.getElementById('trendChart');
        if (ctx4 && data.employeeRequisitions) {
            if (data.employeeRequisitions.length === 0) {
                // Empty state
                const parent = ctx4.parentElement;
                if (parent) {
                    parent.innerHTML = `<div style="height:100%; display:flex; align-items:center; justify-content:center; flex-direction:column; color:#94a3b8;">
                                       <i class="fas fa-chart-bar" style="font-size: 2.5rem; margin-bottom: 15px; opacity: 0.3;"></i>
                                       <p style="font-weight:500;">No requisition activity yet.</p>
                                   </div>`;
                }
            } else {
                const chartCtx = ctx4.getContext('2d');
                const gradientBlue = createGradient(chartCtx, '#6366f1', '#4f46e5'); // Indigo Gradient

                new Chart(ctx4, {
                    type: 'bar',
                    data: {
                        labels: data.employeeRequisitions.map(e => `${e.first_name} ${e.last_name}`),
                        datasets: [{
                            label: 'Requisitions',
                            data: data.employeeRequisitions.map(e => e.requisition_count),
                            backgroundColor: gradientBlue,
                            borderRadius: 6,
                            barThickness: 24,
                            maxBarThickness: 32
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                grid: {
                                    color: 'rgba(226, 232, 240, 0.4)',
                                    borderDash: [4, 4]
                                }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        }
    } catch (e) { console.error('Error creating Employee Requisitions chart:', e); }

    // 5. Turnover Analysis
    try {
        const ctx5 = document.getElementById('turnoverChart');
        if (ctx5 && data.turnover) {
            const tCount = (data.turnover || []).length;
            const tHeight = Math.max(300, tCount * 50);

            const innerWrapper = ctx5.closest('.chart-inner-wrapper') || ctx5.parentElement;
            if (innerWrapper) {
                innerWrapper.style.height = tHeight + 'px';
                innerWrapper.style.maxHeight = 'none';
            }
            ctx5.height = tHeight;

            new Chart(ctx5, {
                type: 'bar',
                data: {
                    labels: data.turnover.map(t => t.name),
                    datasets: [
                        {
                            label: 'Issued',
                            data: data.turnover.map(t => t.issued),
                            backgroundColor: '#ef4444', // Red for issued/outflow
                            borderRadius: 4,
                            barThickness: 12,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'In Stock',
                            data: data.turnover.map(t => t.stock),
                            backgroundColor: '#cbd5e1', // Grey for stick
                            borderRadius: 4,
                            barThickness: 12,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', align: 'end' }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            grid: { color: 'rgba(226, 232, 240, 0.4)' }
                        },
                        y: {
                            stacked: false,
                            grid: { display: false },
                            ticks: {
                                font: { size: 11, weight: 500 },
                                autoSkip: false
                            }
                        }
                    }
                }
            });
        }
    } catch (e) { console.error('Error creating Turnover chart:', e); }

    // 6. Urgency Heatmap
    try {
        const ctx6 = document.getElementById('urgencyChart');
        if (ctx6 && data.lowStockUrgency) {
            new Chart(ctx6, {
                type: 'polarArea',
                data: {
                    labels: ['Out of Stock', 'Critical', 'Caution'],
                    datasets: [{
                        data: [
                            data.lowStockUrgency.out || 0,
                            data.lowStockUrgency.critical || 0,
                            data.lowStockUrgency.caution || 0
                        ],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.85)', // Red
                            'rgba(249, 115, 22, 0.85)', // Orange
                            'rgba(245, 158, 11, 0.85)'  // Amber
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ` ${context.label}: ${context.raw} items`;
                                }
                            }
                        }
                    },
                    layout: {
                        padding: 10
                    },
                    scales: {
                        r: {
                            ticks: { backdropColor: 'transparent', z: 10 },
                            grid: { color: 'rgba(226, 232, 240, 0.6)' }
                        }
                    }
                }
            });
        }
    } catch (e) { console.error('Error creating Urgency chart:', e); }
}

// Global Custom Alert Function
// Global Custom Alert Function
window.showModal = function (message, type = 'info', callback = null) {
    window.modalCallback = callback;

    let modal = document.getElementById('custom-alert-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'custom-alert-modal';
        modal.className = 'modal';
        modal.style.zIndex = '200000';
        modal.innerHTML = `
            <div class="modal-content" style="text-align: center; padding: 24px; border-radius: 16px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-width: 400px; width: 90%; margin: auto;">
                <div class="modal-icon" style="font-size: 2.5rem; margin-bottom: 16px;"></div>
                <h3 id="custom-alert-title" style="margin: 0 0 8px 0; color: #1e293b; font-size: 1.2rem;">Notification</h3>
                <p id="custom-alert-message" style="color: #64748b; font-size: 0.95rem; margin-bottom: 24px; line-height: 1.5;"></p>
                <div style="display: flex; justify-content: center; gap: 10px;">
                    <button id="modal-cancel-btn" onclick="closeModal(false)" class="cancel-btn" style="padding: 10px 24px; font-size: 0.9rem; border-radius: 8px; cursor: pointer; background: #e2e8f0; color: #475569; border: none; transition: all 0.2s; display: none;">Cancel</button>
                    <button id="modal-ok-btn" onclick="closeModal(true)" class="btn-primary" style="padding: 10px 24px; font-size: 0.9rem; border-radius: 8px; cursor: pointer; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; transition: transform 0.2s;">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(false);
            }
        });
    }

    const icon = modal.querySelector('.modal-icon');
    const title = document.getElementById('custom-alert-title');
    const msg = document.getElementById('custom-alert-message');
    const content = modal.querySelector('.modal-content');
    const cancelBtn = document.getElementById('modal-cancel-btn');
    const okBtn = document.getElementById('modal-ok-btn');

    // Reset styles and buttons
    content.style.borderTop = 'none';
    cancelBtn.style.display = 'none';
    okBtn.innerText = 'OK';

    if (type === 'success') {
        icon.innerHTML = '<i class="fas fa-check-circle" style="color: #2ecc71;"></i>';
        title.innerText = 'Success';
        content.style.borderTop = '5px solid #2ecc71';
    } else if (type === 'error') {
        icon.innerHTML = '<i class="fas fa-times-circle" style="color: #e74c3c;"></i>';
        title.innerText = 'Error';
        content.style.borderTop = '5px solid #e74c3c';
    } else if (type === 'warning') {
        icon.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i>';
        title.innerText = 'Warning';
        content.style.borderTop = '5px solid #f39c12';
    } else if (type === 'confirm') {
        icon.innerHTML = '<i class="fas fa-question-circle" style="color: #10b981;"></i>';
        title.innerText = 'Confirm Action';
        content.style.borderTop = '5px solid #10b981';
        cancelBtn.style.display = 'block';
        okBtn.innerText = 'Confirm';
    } else {
        icon.innerHTML = '<i class="fas fa-info-circle" style="color: #10b981;"></i>';
        title.innerText = 'Notification';
        content.style.borderTop = '5px solid #10b981';
    }

    msg.innerHTML = message;
    modal.style.display = 'block';
};

window.showConfirm = function (message, callback) {
    window.showModal(message, 'confirm', callback);
};

window.closeModal = function (result = true) {
    const modal = document.getElementById('custom-alert-modal');
    if (modal) {
        modal.style.display = 'none';
        if (typeof window.modalCallback === 'function') {
            const cb = window.modalCallback;
            window.modalCallback = null;
            cb(result);
        }
    }
};
