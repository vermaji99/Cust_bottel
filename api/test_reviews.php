<?php
// Test script to check reviews table and connection
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

try {
    require __DIR__ . '/bootstrap.php';
    
    // Test database connection
    $db = db();
    
    // Check if reviews table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'reviews'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if (!$tableExists) {
        // Try to create table
        $db->exec("
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
        $created = true;
    } else {
        $created = false;
    }
    
    // Check current user
    $user = current_user();
    
    echo json_encode([
        'success' => true,
        'database_connected' => true,
        'reviews_table_exists' => $tableExists,
        'table_created' => $created,
        'user_logged_in' => !empty($user),
        'user_id' => $user['id'] ?? null
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ], JSON_PRETTY_PRINT);
}

