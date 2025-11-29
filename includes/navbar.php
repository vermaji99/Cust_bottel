<?php
/**
 * Reusable Navbar Component
 * Usage: include this file in any page and pass $currentPage parameter
 * Example: $currentPage = 'home'; include __DIR__ . '/includes/navbar.php';
 */

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
    <div class="logo-futuristic" onclick="window.location.href='<?= $currentPage === 'home' ? '#' : 'index.php' ?>'">
        BTL
    </div>

    <!-- NAVIGATION MENU / ALWAYS FLEX FOR DESKTOP -->
    <nav class="nav-futuristic">
        <a href="index.php" class="nav-link-futuristic <?= isActive('home', $currentPage) ?>" title="Home">
            <i class="fas fa-home"></i>
            <span class="nav-text">Home</span>
        </a>

        <a href="category.php" class="nav-link-futuristic <?= isActive('shop', $currentPage) ?>" title="Shop">
            <i class="fas fa-shopping-bag"></i>
            <span class="nav-text">Shop</span>
        </a>

        <a href="about.php" class="nav-link-futuristic <?= isActive('about', $currentPage) ?>" title="About">
            <i class="fas fa-info-circle"></i>
            <span class="nav-text">About</span>
        </a>

        <a href="contact.php" class="nav-link-futuristic <?= isActive('contact', $currentPage) ?>" title="Contact">
            <i class="fas fa-envelope"></i>
            <span class="nav-text">Contact</span>
        </a>

        <?php if ($isLoggedIn): ?>
        <a href="user/orders.php" class="nav-link-futuristic <?= isActive('orders', $currentPage) ?>" title="My Orders">
            <i class="fas fa-box"></i>
            <span class="nav-text">My Orders</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- RIGHT SIDE ICONS -->
    <div class="header-actions-futuristic">
        
        <?php if ($isLoggedIn): ?>
        <a href="user/cart.php" class="icon-btn-futuristic cart-icon <?= isActive('cart', $currentPage) ?>" title="Cart">
            <i class="fas fa-shopping-cart"></i>
        </a>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
        <a href="user/profile.php" 
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

        <!-- MOBILE MENU TOGGLE -->
        <div class="menu-toggle-futuristic" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>

</header>
