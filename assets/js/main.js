document.addEventListener('DOMContentLoaded', function () {
    // Basic interactivity for the landing page
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');

            // Only intercept if it's a valid internal anchor (starts with # and has content)
            if (href && href.startsWith('#') && href.length > 1) {
                try {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                } catch (err) {
                    // Ignore selector errors (e.g. if href was changed to a URL)
                    console.debug('Smooth scroll skipped for:', href);
                }
            }
        });
    });
});
