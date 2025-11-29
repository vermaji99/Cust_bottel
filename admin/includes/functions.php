<?php
declare(strict_types=1);

/**
 * Admin-specific helper functions
 */

/**
 * Get current admin user (separate from regular user)
 */
function current_admin(): ?array
{
    static $cached;
    if ($cached !== null) {
        return $cached;
    }

    if (empty($_SESSION['admin_id']) || !$_SESSION['admin_logged_in']) {
        $cached = null;
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND role IN ("admin", "staff") LIMIT 1');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    
    if (!$admin) {
        logout_admin();
        $cached = null;
        return null;
    }
    
    $cached = $admin;
    return $admin;
}

/**
 * Login admin (separate from user login)
 */
function login_admin(array $admin): void
{
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_logged_in'] = true;

    // Log admin session
    $stmt = db()->prepare('INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $admin['id'],
        session_id(),
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}

/**
 * Logout admin
 */
function logout_admin(): void
{
    if (!empty($_SESSION['admin_id'])) {
        $stmt = db()->prepare('DELETE FROM user_sessions WHERE session_id = ?');
        $stmt->execute([session_id()]);
    }

    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_role']);
    $_SESSION['admin_logged_in'] = false;
}

/**
 * Require admin authentication - redirects if not logged in
 */
function require_admin_auth(): array
{
    $admin = current_admin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

/**
 * Require admin role (not staff)
 */
function require_admin_role(): array
{
    $admin = require_admin_auth();
    if (($admin['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access Denied: Admin role required.');
    }
    return $admin;
}

/**
 * Check if admin is logged in
 */
function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !empty(current_admin());
}

