<?php
header('Content-Type: application/json');
require __DIR__ . '/../includes/bootstrap.php';

$admin = require_admin_auth();

try {
    $stats = [
        'total_users' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn(),
        'total_orders' => (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'total_products' => (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'total_revenue' => (float) db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ("delivered", "shipped")')->fetchColumn(),
        'pending_orders' => (int) db()->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn(),
    ];
    
    echo json_encode(['success' => true, 'data' => $stats]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch stats']);
}

