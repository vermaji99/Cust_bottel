<?php
require __DIR__ . '/bootstrap.php';
ensure_json_request();
$user = api_require_user();
$payload = read_json_body();

$productId = (int) ($payload['product_id'] ?? 0);
$quantity = (int) max(1, (int) ($payload['quantity'] ?? 1));
if ($productId <= 0) {
    json_response(['success' => false, 'error' => 'INVALID_PRODUCT'], 422);
}

$stmt = db()->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1');
$stmt->execute([$user['id'], $productId]);
$existing = (int) ($stmt->fetchColumn() ?: 0);
$target = $existing + $quantity;
$result = cart_set_quantity($user['id'], $productId, $target);

json_response([
    'success' => true,
    'item' => $result,
    'counts' => [
        'cart' => cart_count($user['id']),
    ],
]);





