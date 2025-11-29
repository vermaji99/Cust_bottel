/**
 * Navbar JavaScript - Simplified and Fixed Version
 * Clean menu toggle that works on all devices
 */

(function () {
    "use strict";

    function initNavbar() {
        const menuToggle = document.getElementById("menu-toggle");
        const nav = document.querySelector(".nav-futuristic");
        const header = document.getElementById("main-header");
        const profileDropdown = document.getElementById("profileDropdown");

        /* =============================
           MOBILE MENU TOGGLE - SIMPLIFIED
        ============================== */
        if (menuToggle && nav) {
            console.log("Menu toggle initialized");

            const toggleMenu = () => {
                const isOpen = nav.classList.contains("nav-open");
                console.log("Toggling menu, isOpen:", isOpen);

                if (!isOpen) {
                    // OPENING MENU
                    console.log("Opening menu");

                    // Add nav-open class
                    nav.classList.add("nav-open");
                    nav.classList.remove("active"); // Remove old class if exists

                    // Calculate header height
                    const headerHeight = header ? header.offsetHeight : 70;
                    const maxHeightValue = window.innerHeight - headerHeight;

                    console.log("Header height:", headerHeight, "px");

                    // Set menu container styles - CRITICAL: Disable transitions first
                    nav.style.transition = "none !important";
                    nav.style.setProperty("transition", "none", "important");

                    // Clear all styles first
                    nav.removeAttribute("style");

                    // Use cssText for immediate application - NO TRANSITIONS
                    nav.style.cssText = `
                        position: fixed !important;
                        top: ${headerHeight}px !important;
                        left: 0 !important;
                        right: 0 !important;
                        width: 100% !important;
                        max-width: 100vw !important;
                        display: flex !important;
                        flex-direction: column !important;
                        max-height: ${maxHeightValue}px !important;
                        min-height: 200px !important;
                        padding: 20px 0 !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        z-index: 99999 !important;
                        background: rgba(11, 12, 16, 0.98) !important;
                        background-color: rgba(11, 12, 16, 0.98) !important;
                        overflow-y: auto !important;
                        overflow-x: visible !important;
                        margin: 0 !important;
                        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5) !important;
                        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
                        transition: none !important;
                    `;

                    // Force opacity to stay at 1 - prevent any transitions
                    setTimeout(() => {
                        nav.style.setProperty("opacity", "1", "important");
                        nav.style.setProperty(
                            "transition",
                            "none",
                            "important"
                        );
                    }, 0);

                    setTimeout(() => {
                        nav.style.setProperty("opacity", "1", "important");
                        nav.style.setProperty(
                            "transition",
                            "none",
                            "important"
                        );
                    }, 50);

                    setTimeout(() => {
                        nav.style.setProperty("opacity", "1", "important");
                        nav.style.setProperty(
                            "transition",
                            "none",
                            "important"
                        );
                    }, 100);

                    // Force immediate visibility check and prevent opacity transitions
                    requestAnimationFrame(() => {
                        // CRITICAL: Disable all transitions and force opacity
                        nav.style.setProperty(
                            "transition",
                            "none",
                            "important"
                        );
                        nav.style.setProperty("opacity", "1", "important");

                        const navRect = nav.getBoundingClientRect();
                        const navComputed = window.getComputedStyle(nav);
                        console.log("Menu container immediate check:", {
                            display: navComputed.display,
                            visibility: navComputed.visibility,
                            opacity: navComputed.opacity,
                            transition: navComputed.transition,
                            zIndex: navComputed.zIndex,
                            position: navComputed.position,
                            top: navComputed.top,
                            rect: {
                                top: navRect.top,
                                left: navRect.left,
                                width: navRect.width,
                                height: navRect.height,
                                visible:
                                    navRect.width > 0 &&
                                    navRect.height > 0 &&
                                    navRect.top >= 0,
                            },
                        });

                        // If opacity is not 1, force it immediately
                        if (parseFloat(navComputed.opacity) < 1) {
                            console.warn(
                                "Opacity is",
                                navComputed.opacity,
                                "- Forcing to 1..."
                            );
                            nav.style.setProperty(
                                "transition",
                                "none",
                                "important"
                            );
                            nav.style.setProperty("opacity", "1", "important");
                        }

                        // If menu is not visible, force it again
                        if (
                            navComputed.display !== "flex" ||
                            navComputed.visibility !== "visible" ||
                            parseFloat(navComputed.opacity) < 1 ||
                            navRect.height === 0
                        ) {
                            console.warn(
                                "Menu container NOT VISIBLE - Forcing again..."
                            );
                            nav.style.setProperty(
                                "transition",
                                "none",
                                "important"
                            );
                            nav.style.setProperty(
                                "display",
                                "flex",
                                "important"
                            );
                            nav.style.setProperty(
                                "visibility",
                                "visible",
                                "important"
                            );
                            nav.style.setProperty("opacity", "1", "important");
                            nav.style.setProperty(
                                "min-height",
                                "200px",
                                "important"
                            );
                        }
                    });

                    // Additional checks to prevent opacity from changing
                    setTimeout(() => {
                        const navComputed = window.getComputedStyle(nav);
                        if (parseFloat(navComputed.opacity) < 1) {
                            console.warn(
                                "Opacity dropped to",
                                navComputed.opacity,
                                "- Fixing..."
                            );
                            nav.style.setProperty(
                                "transition",
                                "none",
                                "important"
                            );
                            nav.style.setProperty("opacity", "1", "important");
                        }
                    }, 150);

                    setTimeout(() => {
                        const navComputed = window.getComputedStyle(nav);
                        if (parseFloat(navComputed.opacity) < 1) {
                            console.warn(
                                "Opacity still",
                                navComputed.opacity,
                                "- Final fix..."
                            );
                            nav.style.setProperty(
                                "transition",
                                "none",
                                "important"
                            );
                            nav.style.setProperty("opacity", "1", "important");
                        }
                    }, 300);

                    // Change icon to X
                    const icon = menuToggle.querySelector("i");
                    if (icon) {
                        icon.classList.remove("fa-bars");
                        icon.classList.add("fa-times");
                    }

                    // CRITICAL: Force all nav links to be visible
                    const links = nav.querySelectorAll(".nav-link-futuristic");
                    console.log("Nav links found:", links.length);

                    links.forEach((link, index) => {
                        // Remove any existing inline styles
                        link.removeAttribute("style");

                        // Force visibility with inline styles
                        link.style.cssText = `
                            display: flex !important;
                            opacity: 1 !important;
                            visibility: visible !important;
                            width: 100% !important;
                            min-width: 100% !important;
                            min-height: 48px !important;
                            height: auto !important;
                            padding: 16px 20px !important;
                            margin: 0 !important;
                            color: rgba(255, 255, 255, 0.9) !important;
                            background-color: transparent !important;
                            justify-content: flex-start !important;
                            align-items: center !important;
                            gap: 12px !important;
                            flex-direction: row !important;
                            text-decoration: none !important;
                            position: relative !important;
                            z-index: 1 !important;
                            overflow: visible !important;
                            clip: auto !important;
                            clip-path: none !important;
                        `;

                        // Force icon visibility with setProperty
                        const linkIcon = link.querySelector("i");
                        if (linkIcon) {
                            linkIcon.removeAttribute("style");
                            linkIcon.style.setProperty(
                                "display",
                                "inline-block",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "visibility",
                                "visible",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "opacity",
                                "1",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "color",
                                "rgba(255, 255, 255, 0.9)",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "font-size",
                                "1.1rem",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "width",
                                "24px",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "min-width",
                                "24px",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "height",
                                "auto",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "line-height",
                                "1",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "text-align",
                                "center",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "flex-shrink",
                                "0",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "position",
                                "relative",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "z-index",
                                "2",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "font-family",
                                "Font Awesome 6 Free",
                                "important"
                            );
                            linkIcon.style.setProperty(
                                "font-weight",
                                "900",
                                "important"
                            );
                        } else {
                            console.warn(
                                `Link ${index + 1} has no icon element`
                            );
                        }

                        // Force text visibility with setProperty
                        const linkText = link.querySelector(".nav-text");
                        if (linkText) {
                            linkText.removeAttribute("style");
                            linkText.style.setProperty(
                                "display",
                                "inline-block",
                                "important"
                            );
                            linkText.style.setProperty(
                                "visibility",
                                "visible",
                                "important"
                            );
                            linkText.style.setProperty(
                                "opacity",
                                "1",
                                "important"
                            );
                            linkText.style.setProperty(
                                "color",
                                "rgba(255, 255, 255, 0.9)",
                                "important"
                            );
                            linkText.style.setProperty(
                                "font-weight",
                                "500",
                                "important"
                            );
                            linkText.style.setProperty(
                                "font-size",
                                "0.9rem",
                                "important"
                            );
                            linkText.style.setProperty(
                                "margin-left",
                                "12px",
                                "important"
                            );
                            linkText.style.setProperty(
                                "line-height",
                                "1.5",
                                "important"
                            );
                            linkText.style.setProperty(
                                "position",
                                "relative",
                                "important"
                            );
                            linkText.style.setProperty(
                                "z-index",
                                "2",
                                "important"
                            );
                            linkText.style.setProperty(
                                "white-space",
                                "nowrap",
                                "important"
                            );
                            linkText.style.setProperty(
                                "text-transform",
                                "uppercase",
                                "important"
                            );
                            linkText.style.setProperty(
                                "letter-spacing",
                                "0.1em",
                                "important"
                            );
                        } else {
                            console.warn(
                                `Link ${index + 1} has no .nav-text element`
                            );
                        }

                        const linkTitle =
                            link.getAttribute("title") ||
                            link.textContent.trim() ||
                            `Link ${index + 1}`;
                        console.log(
                            `Link ${
                                index + 1
                            } ("${linkTitle}") - Forced visible`
                        );

                        // Immediate verification
                        const linkRect = link.getBoundingClientRect();
                        const linkComputed = window.getComputedStyle(link);
                        const iconComputed = linkIcon
                            ? window.getComputedStyle(linkIcon)
                            : null;
                        const textComputed = linkText
                            ? window.getComputedStyle(linkText)
                            : null;

                        console.log(`Link ${index + 1} immediate check:`, {
                            link: {
                                display: linkComputed.display,
                                visibility: linkComputed.visibility,
                                opacity: linkComputed.opacity,
                                width: linkRect.width,
                                height: linkRect.height,
                                color: linkComputed.color,
                            },
                            icon: iconComputed
                                ? {
                                      display: iconComputed.display,
                                      visibility: iconComputed.visibility,
                                      opacity: iconComputed.opacity,
                                  }
                                : "No icon",
                            text: textComputed
                                ? {
                                      display: textComputed.display,
                                      visibility: textComputed.visibility,
                                      opacity: textComputed.opacity,
                                      color: textComputed.color,
                                  }
                                : "No text",
                        });

                        // If link is still not visible, force again
                        if (
                            linkComputed.display !== "flex" ||
                            linkComputed.visibility !== "visible" ||
                            parseFloat(linkComputed.opacity) < 1 ||
                            linkRect.height === 0
                        ) {
                            console.warn(
                                `Link ${
                                    index + 1
                                } still not visible - forcing again...`
                            );
                            link.style.setProperty(
                                "display",
                                "flex",
                                "important"
                            );
                            link.style.setProperty(
                                "visibility",
                                "visible",
                                "important"
                            );
                            link.style.setProperty("opacity", "1", "important");
                            link.style.setProperty(
                                "min-height",
                                "48px",
                                "important"
                            );

                            if (linkIcon) {
                                linkIcon.style.setProperty(
                                    "display",
                                    "inline-block",
                                    "important"
                                );
                                linkIcon.style.setProperty(
                                    "visibility",
                                    "visible",
                                    "important"
                                );
                                linkIcon.style.setProperty(
                                    "opacity",
                                    "1",
                                    "important"
                                );
                            }

                            if (linkText) {
                                linkText.style.setProperty(
                                    "display",
                                    "inline-block",
                                    "important"
                                );
                                linkText.style.setProperty(
                                    "visibility",
                                    "visible",
                                    "important"
                                );
                                linkText.style.setProperty(
                                    "opacity",
                                    "1",
                                    "important"
                                );
                            }
                        }
                    });

                    // Additional force after a delay
                    setTimeout(() => {
                        links.forEach((link, index) => {
                            const linkComputed = window.getComputedStyle(link);
                            const linkIcon = link.querySelector("i");
                            const linkText = link.querySelector(".nav-text");

                            if (
                                linkComputed.display !== "flex" ||
                                linkComputed.visibility !== "visible" ||
                                parseFloat(linkComputed.opacity) < 1
                            ) {
                                console.warn(
                                    `Link ${
                                        index + 1
                                    } opacity/visibility issue - final fix...`
                                );
                                link.style.setProperty(
                                    "display",
                                    "flex",
                                    "important"
                                );
                                link.style.setProperty(
                                    "visibility",
                                    "visible",
                                    "important"
                                );
                                link.style.setProperty(
                                    "opacity",
                                    "1",
                                    "important"
                                );

                                if (linkIcon) {
                                    linkIcon.style.setProperty(
                                        "display",
                                        "inline-block",
                                        "important"
                                    );
                                    linkIcon.style.setProperty(
                                        "visibility",
                                        "visible",
                                        "important"
                                    );
                                    linkIcon.style.setProperty(
                                        "opacity",
                                        "1",
                                        "important"
                                    );
                                }

                                if (linkText) {
                                    linkText.style.setProperty(
                                        "display",
                                        "inline-block",
                                        "important"
                                    );
                                    linkText.style.setProperty(
                                        "visibility",
                                        "visible",
                                        "important"
                                    );
                                    linkText.style.setProperty(
                                        "opacity",
                                        "1",
                                        "important"
                                    );
                                }
                            }
                        });
                    }, 200);

                    // Final verification after a short delay
                    setTimeout(() => {
                        const allLinks = nav.querySelectorAll(
                            ".nav-link-futuristic"
                        );
                        const navRect = nav.getBoundingClientRect();
                        const navComputed = window.getComputedStyle(nav);

                        console.log("=== FINAL VERIFICATION ===");
                        console.log("Menu container:", {
                            display: navComputed.display,
                            visibility: navComputed.visibility,
                            opacity: navComputed.opacity,
                            zIndex: navComputed.zIndex,
                            position: navComputed.position,
                            top: navComputed.top,
                            rect: {
                                top: navRect.top,
                                left: navRect.left,
                                width: navRect.width,
                                height: navRect.height,
                            },
                        });

                        allLinks.forEach((link, idx) => {
                            const rect = link.getBoundingClientRect();
                            const computed = window.getComputedStyle(link);
                            const icon = link.querySelector("i");
                            const text = link.querySelector(".nav-text");
                            const iconComputed = icon
                                ? window.getComputedStyle(icon)
                                : null;
                            const textComputed = text
                                ? window.getComputedStyle(text)
                                : null;

                            console.log(`Link ${idx + 1}:`, {
                                link: {
                                    display: computed.display,
                                    visibility: computed.visibility,
                                    opacity: computed.opacity,
                                    width: rect.width,
                                    height: rect.height,
                                    top: rect.top,
                                    left: rect.left,
                                },
                                icon: iconComputed
                                    ? {
                                          display: iconComputed.display,
                                          visibility: iconComputed.visibility,
                                          opacity: iconComputed.opacity,
                                      }
                                    : "No icon",
                                text: textComputed
                                    ? {
                                          display: textComputed.display,
                                          visibility: textComputed.visibility,
                                          opacity: textComputed.opacity,
                                      }
                                    : "No text",
                            });

                            // If link is not visible, force it again
                            if (
                                computed.display !== "flex" ||
                                computed.visibility !== "visible" ||
                                computed.opacity !== "1" ||
                                rect.height === 0
                            ) {
                                console.warn(
                                    `Link ${
                                        idx + 1
                                    } NOT VISIBLE - Forcing again...`
                                );
                                link.style.setProperty(
                                    "display",
                                    "flex",
                                    "important"
                                );
                                link.style.setProperty(
                                    "visibility",
                                    "visible",
                                    "important"
                                );
                                link.style.setProperty(
                                    "opacity",
                                    "1",
                                    "important"
                                );
                                link.style.setProperty(
                                    "min-height",
                                    "48px",
                                    "important"
                                );
                            }
                        });
                    }, 200);
                } else {
                    // CLOSING MENU
                    console.log("Closing menu");

                    // Remove nav-open class
                    nav.classList.remove("nav-open");

                    // Reset menu styles for closing animation
                    nav.style.opacity = "0";
                    nav.style.visibility = "hidden";
                    nav.style.maxHeight = "0";
                    nav.style.padding = "0";

                    // Change icon back to bars
                    const icon = menuToggle.querySelector("i");
                    if (icon) {
                        icon.classList.remove("fa-times");
                        icon.classList.add("fa-bars");
                    }

                    // Hide menu after transition
                    setTimeout(() => {
                        if (!nav.classList.contains("nav-open")) {
                            nav.style.display = "none";
                            nav.style.cssText = ""; // Clear all inline styles
                        }
                    }, 400);
                }
            };

            // Attach event listeners
            menuToggle.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log("Menu toggle clicked");
                toggleMenu();
            });

            menuToggle.addEventListener("touchend", function (e) {
                e.preventDefault();
                e.stopPropagation();
                console.log("Menu toggle touched");
                toggleMenu();
            });

            // Close menu on outside click
            document.addEventListener("click", function (e) {
                if (
                    nav.classList.contains("nav-open") &&
                    !nav.contains(e.target) &&
                    !menuToggle.contains(e.target)
                ) {
                    toggleMenu();
                }
            });

            // ESC key to close
            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape" && nav.classList.contains("nav-open")) {
                    toggleMenu();
                }
            });
        }

        /* =============================
           SCROLL HEADER EFFECT
        ============================== */
        if (header) {
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

    // Initialize after DOM ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initNavbar);
    } else {
        initNavbar();
    }

    // Also try after a short delay to ensure everything is loaded
    setTimeout(initNavbar, 100);
})();
