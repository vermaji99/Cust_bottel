<?php
/**
 * Reusable Navbar Component
 * Usage: include this file in any page and pass $currentPage parameter
 * Example: $currentPage = 'home'; include __DIR__ . '/includes/navbar.php';
 */

// Prevent duplicate navbar rendering
if (defined('NAVBAR_INCLUDED')) {
    return;
}
define('NAVBAR_INCLUDED', true);

if (!isset($currentPage)) {
    $currentPage = '';
}

$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;

// Get cart and wishlist counts for badges
$cartCount = $isLoggedIn ? cart_count($currentUser['id']) : 0;
$wishlistCount = $isLoggedIn ? wishlist_count($currentUser['id']) : 0;

// Function to check if page is active
function isActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>

<header id="main-header" class="header-futuristic">
    
    <!-- LOGO -->
    <?php
    // Determine base path based on current directory
    $basePath = '';
    $currentDir = dirname($_SERVER['PHP_SELF']);
    if (strpos($currentDir, '/user') !== false || strpos($currentDir, '\\user') !== false) {
        $basePath = '../';
    }
    $homeUrl = $currentPage === 'home' ? '#' : $basePath . 'index.php';
    ?>
    <div class="logo-futuristic" onclick="window.location.href='<?= $homeUrl ?>'">
        BTL
    </div>

    <!-- NAVIGATION MENU / ALWAYS FLEX FOR DESKTOP -->
    <nav class="nav-futuristic">
        <?php
        // Determine base path based on current directory
        $basePath = '';
        $currentDir = dirname($_SERVER['PHP_SELF']);
        if (strpos($currentDir, '/user') !== false || strpos($currentDir, '\\user') !== false) {
            $basePath = '../';
        }
        ?>
        <a href="<?= $basePath ?>index.php" class="nav-link-futuristic <?= isActive('home', $currentPage) ?>" title="Home">
            <i class="fas fa-home"></i>
            <span class="nav-text">Home</span>
        </a>

        <a href="<?= $basePath ?>category.php" class="nav-link-futuristic <?= isActive('shop', $currentPage) ?>" title="Shop">
            <i class="fas fa-shopping-bag"></i>
            <span class="nav-text">Shop</span>
        </a>

        <a href="<?= $basePath ?>about.php" class="nav-link-futuristic <?= isActive('about', $currentPage) ?>" title="About">
            <i class="fas fa-info-circle"></i>
            <span class="nav-text">About</span>
        </a>

        <a href="<?= $basePath ?>contact.php" class="nav-link-futuristic <?= isActive('contact', $currentPage) ?>" title="Contact">
            <i class="fas fa-envelope"></i>
            <span class="nav-text">Contact</span>
        </a>

        <?php if ($isLoggedIn): ?>
        <a href="<?= $basePath ?>user/orders.php" class="nav-link-futuristic <?= isActive('orders', $currentPage) ?>" title="My Orders">
            <i class="fas fa-box"></i>
            <span class="nav-text">My Orders</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- RIGHT SIDE ICONS -->
    <div class="header-actions-futuristic">
        <?php
        // Determine base path for icon links (if not already set)
        if (!isset($basePath)) {
            $basePath = '';
            $currentDir = dirname($_SERVER['PHP_SELF']);
            if (strpos($currentDir, '/user') !== false || strpos($currentDir, '\\user') !== false) {
                $basePath = '../';
            }
        }
        ?>
        
        <?php if ($isLoggedIn): ?>
        <a href="<?= $basePath ?>user/cart.php" class="icon-btn-futuristic cart-icon <?= isActive('cart', $currentPage) ?>" title="Cart">
            <i class="fas fa-shopping-cart"></i>
        </a>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
        <a href="<?= $basePath ?>user/profile.php" 
           class="icon-btn-futuristic profile-icon <?= isActive('profile', $currentPage) ?>" 
           title="Profile" 
           style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-user"></i>
        </a>
        <?php else: ?>
        <a href="#" 
           class="icon-btn-futuristic profile-icon" 
           id="profileLoginBtn" 
           title="Login" 
           style="text-decoration: none; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="fas fa-user" style="pointer-events: none;"></i>
        </a>
        <?php endif; ?>
        
        <!-- HAMBURGER BUTTON (MOBILE ONLY) - RIGHTMOST -->
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>

</header>

<!-- MOBILE NAVIGATION POPUP -->
<div class="mobile-nav-popup" id="mobileNavPopup">
    <div class="mobile-nav-popup-content">
        <div class="mobile-nav-header">
            <h3>Menu</h3>
            <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close Menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="mobile-nav-links">
            <?php
            // Determine base path based on current directory
            $basePath = '';
            $currentDir = dirname($_SERVER['PHP_SELF']);
            if (strpos($currentDir, '/user') !== false || strpos($currentDir, '\\user') !== false) {
                $basePath = '../';
            }
            ?>
            <a href="<?= $basePath ?>index.php" class="mobile-nav-link-item <?= isActive('home', $currentPage) ?>" onclick="closeMobileNav()">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="<?= $basePath ?>category.php" class="mobile-nav-link-item <?= isActive('shop', $currentPage) ?>" onclick="closeMobileNav()">
                <i class="fas fa-shopping-bag"></i>
                <span>Shop</span>
            </a>
            <a href="<?= $basePath ?>about.php" class="mobile-nav-link-item <?= isActive('about', $currentPage) ?>" onclick="closeMobileNav()">
                <i class="fas fa-info-circle"></i>
                <span>About</span>
            </a>
            <a href="<?= $basePath ?>contact.php" class="mobile-nav-link-item <?= isActive('contact', $currentPage) ?>" onclick="closeMobileNav()">
                <i class="fas fa-envelope"></i>
                <span>Contact</span>
            </a>
            <?php if ($isLoggedIn): ?>
            <a href="<?= $basePath ?>user/orders.php" class="mobile-nav-link-item <?= isActive('orders', $currentPage) ?>" onclick="closeMobileNav()">
                <i class="fas fa-box"></i>
                <span>My Orders</span>
            </a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<script>
// Prevent duplicate script execution
if (!window.navbarScriptLoaded) {
    window.navbarScriptLoaded = true;
    
    // Mobile Navigation Popup Functions
    function toggleMobileNav() {
        const popup = document.getElementById('mobileNavPopup');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        if (popup && hamburgerBtn) {
            popup.classList.toggle('active');
            hamburgerBtn.classList.toggle('active');
            document.body.style.overflow = popup.classList.contains('active') ? 'hidden' : '';
        }
    }

    function closeMobileNav() {
        const popup = document.getElementById('mobileNavPopup');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        if (popup && hamburgerBtn) {
            popup.classList.remove('active');
            hamburgerBtn.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Make functions globally available
    window.toggleMobileNav = toggleMobileNav;
    window.closeMobileNav = closeMobileNav;

    // Initialize on DOM ready
    (function() {
        // Check if already initialized
        if (window.mobileNavInitialized) return;
        window.mobileNavInitialized = true;
        
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const closeBtn = document.getElementById('mobileNavClose');
        const popup = document.getElementById('mobileNavPopup');
        
        if (hamburgerBtn) {
            hamburgerBtn.addEventListener('click', toggleMobileNav);
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeMobileNav);
        }
        
        // Close on outside click
        if (popup) {
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    closeMobileNav();
                }
            });
        }
        
        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && popup && popup.classList.contains('active')) {
                closeMobileNav();
            }
        });
    })();
}
</script>
