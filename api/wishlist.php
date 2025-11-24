<?php
require __DIR__ . '/bootstrap.php';
ensure_json_request();
$payload = read_json_body();
$action = $payload['action'] ?? 'list';
$user = api_require_user();

switch ($action) {
    case 'add':
        $productId = (int) ($payload['product_id'] ?? 0);
        if ($productId <= 0) {
            json_response(['success' => false, 'error' => 'INVALID_PRODUCT'], 422);
        }
        wishlist_add($user['id'], $productId);
        json_response([
            'success' => true,
            'counts' => [
                'wishlist' => wishlist_count($user['id']),
            ],
        ]);
        break;

    case 'remove':
        $productId = (int) ($payload['product_id'] ?? 0);
        if ($productId <= 0) {
            json_response(['success' => false, 'error' => 'INVALID_PRODUCT'], 422);
        }
        wishlist_remove($user['id'], $productId);
        json_response([
            'success' => true,
            'counts' => [
                'wishlist' => wishlist_count($user['id']),
            ],
        ]);
        break;

    case 'move_to_cart':
        $productId = (int) ($payload['product_id'] ?? 0);
        if ($productId <= 0) {
            json_response(['success' => false, 'error' => 'INVALID_PRODUCT'], 422);
        }
        wishlist_remove($user['id'], $productId);
        $result = cart_set_quantity($user['id'], $productId, max(1, (int) ($payload['quantity'] ?? 1)));
        json_response([
            'success' => true,
            'counts' => [
                'wishlist' => wishlist_count($user['id']),
                'cart' => cart_count($user['id']),
            ],
            'cart_item' => $result,
        ]);
        break;

    case 'list':
    default:
        json_response([
            'success' => true,
            'items' => wishlist_items($user['id']),
            'counts' => [
                'wishlist' => wishlist_count($user['id']),
                'cart' => cart_count($user['id']),
            ],
        ]);
}





