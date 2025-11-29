/**
 * Advanced Navbar JavaScript
 * Include this in all pages for navbar functionality
 */

(function() {
    'use strict';

    const menuToggle = document.getElementById('menu-toggle');
    const nav = document.querySelector('.nav-futuristic');
    const header = document.getElementById('main-header');
    const profileDropdown = document.getElementById('profileDropdown');

    // Mobile Menu Toggle
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            nav.classList.toggle('active');
            // Toggle the icon for visual feedback
            const icon = menuToggle.querySelector('.icon');
            if (icon) {
                if (nav.classList.contains('active')) {
                    icon.textContent = 'close';
                } else {
                    icon.textContent = 'menu';
                }
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (nav.classList.contains('active') && !nav.contains(e.target) && !menuToggle.contains(e.target)) {
                nav.classList.remove('active');
                const icon = menuToggle.querySelector('.icon');
                if (icon) {
                    icon.textContent = 'menu';
                }
            }
        });
    }

    // Profile Dropdown Toggle
    if (profileDropdown) {
        const dropdownToggle = profileDropdown.querySelector('.icon-link');
        
        if (dropdownToggle) {
            dropdownToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                profileDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!profileDropdown.contains(e.target)) {
                    profileDropdown.classList.remove('active');
                }
            });
            
            // Close dropdown on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    profileDropdown.classList.remove('active');
                }
            });
        }
    }

    // Header Scroll Effect
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Ensure Profile Icon Link Works Properly - Force Navigation
    setTimeout(() => {
        const profileIconLink = document.querySelector('a.profile-icon');
        if (profileIconLink) {
            const href = profileIconLink.getAttribute('href');
            
            // Remove any existing handlers and add our own
            profileIconLink.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (href && href !== '#' && href !== 'javascript:void(0)') {
                    window.location.href = href;
                }
                return false;
            };
            
            // Also ensure icon clicks work
            const iconInside = profileIconLink.querySelector('i');
            if (iconInside) {
                iconInside.style.pointerEvents = 'none';
                iconInside.onclick = function(e) {
                    e.stopPropagation();
                    if (href && href !== '#' && href !== 'javascript:void(0)') {
                        window.location.href = href;
                    }
                };
            }
        }
    }, 50);
})();

