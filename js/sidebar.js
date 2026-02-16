/**
 * Sidebar Toggle Logic - Shared across all views
 */
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const mainContent = document.querySelector('.main-content');
    const html = document.documentElement;

    // The early-load script handles initial restoration, 
    // but we ensure classes are synced here if needed.
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed && window.innerWidth > 1024) {
        html.classList.add('sidebar-collapsed');
        if (sidebar) sidebar.classList.add('collapsed');
        if (mainContent) mainContent.classList.add('collapsed');
    }

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function () {
            const isMobile = window.innerWidth <= 1024;
            if (isMobile) {
                sidebar.classList.toggle('sidebar-open');
                mainContent.classList.toggle('sidebar-open');
            } else {
                // Toggle global class for early loading support
                html.classList.toggle('sidebar-collapsed');

                // Keep individual classes for transition support if they exist in CSS
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');

                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', html.classList.contains('sidebar-collapsed'));
            }
        });
        console.log('Sidebar toggle logic initialized with persistent state');
    } else {
        console.warn('Sidebar toggle components not found:', {
            toggleBtn: !!toggleBtn,
            sidebar: !!sidebar,
            mainContent: !!mainContent
        });
    }
});
