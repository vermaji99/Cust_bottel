/**
 * Navbar JavaScript - Simplified and Fixed Version
 * Clean menu toggle that works on all devices
 */

(function () {
    "use strict";

    // Prevent duplicate initialization
    if (window.navbarInitialized) {
        return;
    }
    window.navbarInitialized = true;

    function initNavbar() {
        // Check for duplicate headers and remove them
        const headers = document.querySelectorAll('#main-header');
        if (headers.length > 1) {
            // Keep only the first one, remove others
            for (let i = 1; i < headers.length; i++) {
                headers[i].remove();
            }
        }
        
        // Check for duplicate mobile popups and remove them
        const popups = document.querySelectorAll('#mobileNavPopup');
        if (popups.length > 1) {
            for (let i = 1; i < popups.length; i++) {
                popups[i].remove();
            }
        }
        
        const header = document.getElementById("main-header");
        const profileDropdown = document.getElementById("profileDropdown");

        /* =============================
           SCROLL HEADER EFFECT
        ============================== */
        if (header && !header.dataset.scrollListenerAdded) {
            header.dataset.scrollListenerAdded = 'true';
            function handleScroll() {
                if (window.scrollY > 50) {
                    header.classList.add("scrolled");
                } else {
                    header.classList.remove("scrolled");
                }
            }
            window.addEventListener("scroll", handleScroll);
            handleScroll();
        }

        /* =============================
           PROFILE DROPDOWN
        ============================== */
        if (profileDropdown) {
            const dropdownToggle = profileDropdown.querySelector(".icon-link");

            if (dropdownToggle) {
                dropdownToggle.addEventListener("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    profileDropdown.classList.toggle("active");
                });

                // Close dropdown outside click
                document.addEventListener("click", function (e) {
                    if (!profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove("active");
                    }
                });

                // ESC close dropdown
                document.addEventListener("keydown", function (e) {
                    if (e.key === "Escape") {
                        profileDropdown.classList.remove("active");
                    }
                });
            }
        }

        /* =============================
           PROFILE ICON DIRECT LINK FIX
        ============================== */
        setTimeout(function () {
            const profileIconLink = document.querySelector("a.profile-icon");
            if (profileIconLink) {
                const href = profileIconLink.getAttribute("href");

                profileIconLink.addEventListener("click", function (e) {
                    if (href && href !== "#" && href !== "javascript:void(0)") {
                        e.preventDefault();
                        window.location.href = href;
                    }
                });
            }
        }, 100);
    }

    // Initialize after DOM ready (only once)
    let initCalled = false;
    function safeInit() {
        if (initCalled) return;
        initCalled = true;
        initNavbar();
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", safeInit);
    } else {
        safeInit();
    }
})();
