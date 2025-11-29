<?php
declare(strict_types=1);

/**
 * Admin Bootstrap - Separate Admin Website Bootstrap
 * This is a standalone admin system with its own authentication
 */

if (defined('ADMIN_BOOTSTRAPPED')) {
    return;
}

define('ADMIN_BOOTSTRAPPED', true);

// Load main bootstrap for database and utilities
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

// Load admin auth functions
require_once __DIR__ . '/auth.php';

// Load admin layout helpers
require_once __DIR__ . '/layout.php';

// Admin-specific paths
define('ADMIN_ROOT', dirname(__DIR__));
define('ADMIN_UPLOADS', ADMIN_ROOT . '/uploads');

// Ensure uploads directory exists
if (!is_dir(ADMIN_UPLOADS)) {
    mkdir(ADMIN_UPLOADS, 0755, true);
}
