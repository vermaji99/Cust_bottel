<?php
/**
 * User Pages Navbar Component (for pages in user/ directory)
 * Usage: include this file in any user page and pass $currentPage parameter
 * Example: $currentPage = 'orders'; include __DIR__ . '/includes/navbar.php';
 */

if (!isset($currentPage)) {
    $currentPage = '';
}

// Get current user - check multiple sources (order matters for user pages)
$user = null;
// First check $currentUser (used in profile.php and other user pages)
if (isset($currentUser) && $currentUser) {
    $user = $currentUser;
}
// Then check $authUser (used in init.php)
elseif (isset($authUser) && $authUser) {
    $user = $authUser;
}
// Finally check current_user() function
elseif (function_exists('current_user')) {
    $user = current_user();
}

$isLoggedIn = (bool) $user;
$currentUserId = $isLoggedIn && isset($user['id']) ? (int)$user['id'] : 0;

// Get cart and wishlist counts for badges
$cartCount = $isLoggedIn ? cart_count($currentUserId) : 0;
$wishlistCount = $isLoggedIn ? wishlist_count($currentUserId) : 0;

// Function to check if page is active
function isActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>
<header id="main-header" class="header-futuristic">
    <div class="logo-futuristic" onclick="window.location.href='../index.php'">BTL</div>
    <nav class="nav-futuristic">
        <a href="../index.php" class="nav-link-futuristic <?= isActive('home', $currentPage) ?>">Home</a>
        <a href="../category.php" class="nav-link-futuristic <?= isActive('shop', $currentPage) ?>">Shop</a>
        <a href="../about.php" class="nav-link-futuristic <?= isActive('about', $currentPage) ?>">About</a>
        <a href="../contact.php" class="nav-link-futuristic <?= isActive('contact', $currentPage) ?>">Contact</a>
        <?php if ($isLoggedIn): ?>
            <a href="orders.php" class="nav-link-futuristic <?= isActive('orders', $currentPage) ?>">My Orders</a>
        <?php endif; ?>
    </nav>
    <div class="header-actions-futuristic">
        <?php if ($isLoggedIn): ?>
            <a href="cart.php" class="icon-btn-futuristic cart-icon <?= isActive('cart', $currentPage) ?>" title="Cart">
                <i class="fas fa-shopping-cart"></i>
            </a>
        <?php endif; ?>
        <?php if ($isLoggedIn): ?>
            <a href="profile.php" class="icon-btn-futuristic profile-icon <?= isActive('profile', $currentPage) ?>" title="Profile" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user"></i>
            </a>
        <?php else: ?>
            <a href="../login.php" class="icon-btn-futuristic profile-icon" title="Login" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user"></i>
            </a>
        <?php endif; ?>
        <div class="menu-toggle-futuristic" id="menu-toggle">
            <span class="icon">menu</span>
        </div>
    </div>
</header>
