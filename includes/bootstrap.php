<?php
declare(strict_types=1);

/**
 * Global bootstrap for every entry point.
 * - Loads Composer
 * - Loads configuration
 * - Starts hardened session
 * - Loads helper + mail utilities
 */

if (defined('BOTTEL_BOOTSTRAPPED')) {
    return;
}

define('BOTTEL_BOOTSTRAPPED', true);

$rootPath = dirname(__DIR__);

// Composer autoloader (PHPMailer, JWT, etc.)
$autoloadPath = $rootPath . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    throw new RuntimeException('Missing vendor/autoload.php. Run `composer install`.');
}
require_once $autoloadPath;

// Load config and expose $config + $pdo (legacy compatibility)
$config = require __DIR__ . '/config.php';

/**
 * Lightweight safety net to ensure critical auth columns/tables exist.
 */
if (!function_exists('bottelEnsureSchema')) {
    function bottelEnsureSchema(PDO $pdo, array $dbConfig): void
    {
        $schema = $dbConfig['name'];
        try {
            $columnStmt = $pdo->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at'
            ");
            $columnStmt->execute([$schema]);
            if ((int) $columnStmt->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER role");
            }

            $tableStmt = $pdo->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'email_verifications'
            ");
            $tableStmt->execute([$schema]);
            if ((int) $tableStmt->fetchColumn() === 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS email_verifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        token CHAR(64) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        consumed_at DATETIME NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            }
        } catch (Throwable $e) {
            error_log('Schema check failed: ' . $e->getMessage());
        }
    }
}

bottelEnsureSchema($pdo, $config['db']);

// Secure session handling
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name($config['security']['session_name']);
    session_start();
}

if (empty($_SESSION['__session_hardened'])) {
    session_regenerate_id(true);
    $_SESSION['__session_hardened'] = time();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email.php';

