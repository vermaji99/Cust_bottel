<?php
declare(strict_types=1);

/**
 * Admin Authentication System
 * Separate from user authentication
 */

if (!function_exists('admin_current_user')) {
    function admin_current_user(): ?array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        if (empty($_SESSION['admin_id'])) {
            $cached = null;
            return null;
        }

        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND role IN ("admin", "staff") LIMIT 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        if (!$cached) {
            admin_logout();
        }
        return $cached;
    }
}

if (!function_exists('admin_login')) {
    function admin_login(array $admin): void
    {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
    }
}

if (!function_exists('admin_logout')) {
    function admin_logout(): void
    {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_role']);
    }
}

if (!function_exists('require_admin_auth')) {
    function require_admin_auth(): array
    {
        $admin = admin_current_user();
        if (!$admin) {
            header('Location: login.php');
            exit;
        }
        return $admin;
    }
}

if (!function_exists('require_admin_role')) {
    function require_admin_role(string $role = 'admin'): array
    {
        $admin = require_admin_auth();
        if ($admin['role'] !== $role && $role === 'admin') {
            http_response_code(403);
            exit('Forbidden: Admin access required');
        }
        return $admin;
    }
}

