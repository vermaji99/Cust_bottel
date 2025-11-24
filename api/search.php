<?php
require __DIR__ . '/bootstrap.php';
$term = trim($_GET['q'] ?? '');
if (!$term) {
    json_response(['success' => false, 'error' => 'QUERY_REQUIRED'], 422);
}
$stmt = db()->prepare('SELECT id, name, price, image FROM products WHERE is_active = 1 AND name LIKE ? LIMIT 10');
$stmt->execute(["%{$term}%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response(['success' => true, 'results' => $results]);





