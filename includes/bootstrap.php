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

            // Check and create reviews table if it doesn't exist
            $reviewsTableStmt = $pdo->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reviews'
            ");
            $reviewsTableStmt->execute([$schema]);
            $reviewsTableExists = (int) $reviewsTableStmt->fetchColumn() > 0;
            
            if (!$reviewsTableExists) {
                // Create table without foreign keys first
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS reviews (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        product_id INT NOT NULL,
                        user_id INT NOT NULL,
                        rating TINYINT(1) NOT NULL,
                        comment TEXT DEFAULT NULL,
                        admin_reply TEXT DEFAULT NULL,
                        admin_replied_at TIMESTAMP NULL DEFAULT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        KEY product_id (product_id),
                        KEY user_id (user_id),
                        KEY rating (rating),
                        UNIQUE KEY unique_user_product_review (user_id, product_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ");
                
                // Try to add foreign keys (may fail silently if referenced tables don't exist)
                try {
                    $pdo->exec("ALTER TABLE reviews ADD CONSTRAINT reviews_ibfk_1 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE");
                } catch (PDOException $e) {
                    // Foreign key might fail, continue without it
                }
                try {
                    $pdo->exec("ALTER TABLE reviews ADD CONSTRAINT reviews_ibfk_2 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
                } catch (PDOException $e) {
                    // Foreign key might fail, continue without it
                }
                
                // Add index for faster queries
                try {
                    $pdo->exec("CREATE INDEX idx_product_rating ON reviews(product_id, rating)");
                } catch (PDOException $e) {
                    // Index might already exist, ignore
                }
            } else {
                // Table exists - ensure 'comment' column exists (fix for missing column error)
                try {
                    $commentColumnStmt = $pdo->prepare("
                        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'comment'
                    ");
                    $commentColumnStmt->execute([$schema]);
                    $commentColumnExists = (int) $commentColumnStmt->fetchColumn() > 0;
                    
                    if (!$commentColumnExists) {
                        // Add missing comment column
                        $pdo->exec("ALTER TABLE reviews ADD COLUMN comment TEXT DEFAULT NULL AFTER rating");
                        error_log("Added missing 'comment' column to existing reviews table");
                    }
                } catch (PDOException $e) {
                    error_log("Failed to check/add comment column: " . $e->getMessage());
                }
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

