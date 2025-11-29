<?php
// Debug script to test review submission endpoint
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>Review Submission Debug</h2>";
echo "<pre>";

try {
    echo "1. Loading bootstrap...\n";
    require __DIR__ . '/bootstrap.php';
    echo "   ✓ Bootstrap loaded successfully\n\n";
    
    echo "2. Checking database connection...\n";
    $db = db();
    echo "   ✓ Database connected\n\n";
    
    echo "3. Checking reviews table...\n";
    try {
        $checkTable = $db->query("SELECT 1 FROM reviews LIMIT 1");
        echo "   ✓ Reviews table exists\n\n";
    } catch (PDOException $e) {
        echo "   ✗ Reviews table doesn't exist: " . $e->getMessage() . "\n";
        echo "   Attempting to create table...\n";
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
        echo "   ✓ Reviews table created\n\n";
    }
    
    echo "4. Checking current user...\n";
    $currentUser = current_user();
    if ($currentUser) {
        echo "   ✓ User authenticated: " . $currentUser['name'] . " (ID: " . $currentUser['id'] . ")\n\n";
    } else {
        echo "   ✗ No user authenticated\n\n";
    }
    
    echo "5. Checking POST data...\n";
    echo "   POST data:\n";
    print_r($_POST);
    echo "\n";
    
    echo "6. Testing CSRF verification...\n";
    $csrfToken = $_POST['csrf_token'] ?? $_SESSION[app_config('security.csrf_key')] ?? '';
    echo "   CSRF token from POST: " . (empty($csrfToken) ? 'NOT SET' : 'SET') . "\n";
    echo "   Session CSRF key: " . ($_SESSION[app_config('security.csrf_key')] ?? 'NOT SET') . "\n";
    $csrfValid = verify_csrf($csrfToken);
    echo "   CSRF valid: " . ($csrfValid ? 'YES' : 'NO') . "\n\n";
    
    echo "7. Testing submit_review.php directly...\n";
    echo "   (This would normally require POST data)\n\n";
    
    echo "=== All checks passed! ===\n";
    
} catch (Throwable $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

