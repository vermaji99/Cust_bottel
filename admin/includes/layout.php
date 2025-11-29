<?php
/**
 * Admin Layout Components
 * Shared sidebar and header for all admin pages
 */
function admin_sidebar(string $activePage = ''): string {
    $admin = admin_current_user();
    $menuItems = [
        ['icon' => 'home', 'label' => 'Dashboard', 'link' => 'index.php', 'key' => 'dashboard'],
        ['icon' => 'images', 'label' => 'Hero Slides', 'link' => 'hero-slides.php', 'key' => 'hero-slides'],
        ['icon' => 'box', 'label' => 'Products', 'link' => 'products.php', 'key' => 'products'],
        ['icon' => 'folder', 'label' => 'Categories', 'link' => 'categories.php', 'key' => 'categories'],
        ['icon' => 'shopping-cart', 'label' => 'Orders', 'link' => 'orders.php', 'key' => 'orders'],
        ['icon' => 'envelope', 'label' => 'Messages', 'link' => 'messages.php', 'key' => 'messages'],
        ['icon' => 'users', 'label' => 'Users', 'link' => 'users.php', 'key' => 'users'],
        ['icon' => 'tag', 'label' => 'Coupons', 'link' => 'coupons.php', 'key' => 'coupons'],
        ['icon' => 'cog', 'label' => 'Settings', 'link' => 'settings.php', 'key' => 'settings'],
    ];
    
    $html = '<div class="admin-sidebar">';
    $html .= '<div class="sidebar-logo">🧴 Bottle Admin</div>';
    $html .= '<ul class="sidebar-menu">';
    
    foreach ($menuItems as $item) {
        $active = ($activePage === $item['key']) ? 'active' : '';
        $html .= sprintf(
            '<li><a href="%s" class="%s"><i class="fas fa-%s"></i> %s</a></li>',
            esc($item['link']),
            $active,
            $item['icon'],
            esc($item['label'])
        );
    }
    
    $html .= '</ul>';
    $html .= '<div class="sidebar-footer">';
    $html .= sprintf('<div class="admin-info"><strong>%s</strong><small>%s</small></div>', esc($admin['name'] ?? 'Admin'), esc($admin['email'] ?? ''));
    $html .= '<a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

function admin_header(string $title, string $subtitle = ''): string {
    $html = '<div class="admin-header">';
    $html .= '<div>';
    $html .= sprintf('<h1 class="page-title">%s</h1>', esc($title));
    if ($subtitle) {
        $html .= sprintf('<p class="page-subtitle">%s</p>', esc($subtitle));
    }
    $html .= '</div>';
    $html .= '<div class="header-actions">';
    $html .= '<a href="index.php" class="btn-icon" title="Dashboard"><i class="fas fa-home"></i></a>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

if (!function_exists('esc')) {
    function esc(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
