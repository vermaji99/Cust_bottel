// Admin Panel JavaScript
(function() {
    'use strict';

    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const body = document.body;

    function toggleMobileMenu() {
        if (mobileMenuToggle && adminSidebar && sidebarOverlay) {
            const isActive = mobileMenuToggle.classList.contains('active');
            
            if (isActive) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        }
    }

    function openMobileMenu() {
        if (mobileMenuToggle && adminSidebar && sidebarOverlay) {
            mobileMenuToggle.classList.add('active');
            adminSidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            body.classList.add('menu-open');
        }
    }

    function closeMobileMenu() {
        if (mobileMenuToggle && adminSidebar && sidebarOverlay) {
            mobileMenuToggle.classList.remove('active');
            adminSidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            body.classList.remove('menu-open');
        }
    }

    // Event Listeners
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeMobileMenu);
    }

    // Close menu on window resize (if resizing to desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileMenu();
        }
    });

    // Make functions globally available
    window.toggleMobileMenu = toggleMobileMenu;
    window.closeMobileMenu = closeMobileMenu;
    window.openMobileMenu = openMobileMenu;

    // Table responsive improvements
    const tableWrappers = document.querySelectorAll('.table-wrapper');
    tableWrappers.forEach(wrapper => {
        if (wrapper.scrollWidth > wrapper.clientWidth) {
            wrapper.style.overflowX = 'auto';
        }
    });

    // Form validation improvements for mobile
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#f44336';
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });

})();

