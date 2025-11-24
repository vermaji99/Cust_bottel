<?php
require __DIR__ . '/bootstrap.php';
ensure_json_request();
$user = api_require_user();
$payload = read_json_body();

$productId = (int) ($payload['product_id'] ?? 0);
if ($productId <= 0) {
    json_response(['success' => false, 'error' => 'INVALID_PRODUCT'], 422);
}

cart_remove($user['id'], $productId);

json_response([
    'success' => true,
    'counts' => [
        'cart' => cart_count($user['id']),
    ],
]);





