<?php
require __DIR__ . '/bootstrap.php';
$user = current_user();

$response = [
    'success' => true,
    'authenticated' => (bool) $user,
    'counts' => [
        'wishlist' => $user ? wishlist_count($user['id']) : 0,
        'cart' => $user ? cart_count($user['id']) : 0,
    ],
];

json_response($response);





