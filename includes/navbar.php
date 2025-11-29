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
    <div class="logo-futuristic" onclick="window.location.href='<?= $currentPage === 'home' ? '#' : 'index.php' ?>'">BTL</div>
    <nav class="nav-futuristic">
        <a href="index.php" class="nav-link-futuristic <?= isActive('home', $currentPage) ?>">Home</a>
        <a href="category.php" class="nav-link-futuristic <?= isActive('shop', $currentPage) ?>">Shop</a>
        <a href="about.php" class="nav-link-futuristic <?= isActive('about', $currentPage) ?>">About</a>
        <a href="contact.php" class="nav-link-futuristic <?= isActive('contact', $currentPage) ?>">Contact</a>
        <?php if ($isLoggedIn): ?>
            <a href="user/orders.php" class="nav-link-futuristic <?= isActive('orders', $currentPage) ?>">My Orders</a>
        <?php endif; ?>
    </nav>
    <div class="header-actions-futuristic">
        <?php if ($isLoggedIn): ?>
            <a href="user/cart.php" class="icon-btn-futuristic cart-icon <?= isActive('cart', $currentPage) ?>" title="Cart">
                <i class="fas fa-shopping-cart"></i>
            </a>
        <?php endif; ?>
        <?php if ($isLoggedIn): ?>
            <a href="user/profile.php" class="icon-btn-futuristic profile-icon <?= isActive('profile', $currentPage) ?>" title="Profile" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user"></i>
            </a>
        <?php else: ?>
            <a href="#" class="icon-btn-futuristic profile-icon" id="profileLoginBtn" title="Login" style="text-decoration: none; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="fas fa-user" style="pointer-events: none;"></i>
            </a>
        <?php endif; ?>
        <div class="menu-toggle-futuristic" id="menu-toggle">
            <span class="icon">menu</span>
        </div>
    </div>
</header>