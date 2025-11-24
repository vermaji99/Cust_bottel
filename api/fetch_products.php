<?php
require __DIR__ . '/bootstrap.php';
$limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$search = trim($_GET['q'] ?? '');

$where = ['is_active = 1'];
$params = [];
if ($search) {
    $where[] = 'name LIKE ?';
    $params[] = "%{$search}%";
}
$whereSql = 'WHERE ' . implode(' AND ', $where);
$stmt = db()->prepare("SELECT id, name, price, image FROM products $whereSql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response(['success' => true, 'products' => $products]);





