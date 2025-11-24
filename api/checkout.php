<?php
require __DIR__ . '/bootstrap.php';
ensure_json_request();
$user = api_require_user();
$body = read_json_body();

$name = trim($body['name'] ?? '');
$email = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = trim($body['phone'] ?? '');
$address = trim($body['address'] ?? '');
$payment = $body['payment'] ?? 'COD';
$designKey = $body['design_key'] ?? null;
$couponCode = strtoupper(trim($body['coupon'] ?? ''));

if (!$name || !$email || !$phone || !$address) {
    json_response(['success' => false, 'error' => 'MISSING_FIELDS'], 422);
}

$cartItems = cart_items_detailed($user['id']);
if (!$cartItems) {
    json_response(['success' => false, 'error' => 'CART_EMPTY'], 400);
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}
$coupon = $couponCode ? find_coupon($couponCode) : null;
$discount = $coupon ? calculate_coupon_discount($subtotal, $coupon) : 0;
$grandTotal = max($subtotal - $discount, 0);

$designId = null;
if ($designKey) {
    $designStmt = db()->prepare('SELECT id FROM designs WHERE design_key = ? AND user_id = ? LIMIT 1');
    $designStmt->execute([$designKey, $user['id']]);
    $designId = $designStmt->fetchColumn() ?: null;
}

db()->beginTransaction();
try {
    $orderStmt = db()->prepare('
        INSERT INTO orders (user_id, order_number, design_id, coupon_code, subtotal, discount_total, total_amount, status, payment_method, shipping_name, shipping_email, shipping_phone, shipping_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $orderNumber = 'BTL' . strtoupper(bin2hex(random_bytes(3)));
    $status = 'pending';
    $orderStmt->execute([
        $user['id'],
        $orderNumber,
        $designId,
        $coupon ? $coupon['code'] : null,
        $subtotal,
        $discount,
        $grandTotal,
        $status,
        $payment,
        $name,
        $email,
        $phone,
        $address,
    ]);
    $orderId = (int) db()->lastInsertId();

    $itemStmt = db()->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
    foreach ($cartItems as $item) {
        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
    }

    $timeline = db()->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
    $timeline->execute([$orderId, $status, 'Order received via API']);

    $clear = db()->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $clear->execute([$user['id']]);

    send_order_confirmation_email($orderId);

    db()->commit();
    json_response(['success' => true, 'order_number' => $orderNumber]);
} catch (Throwable $e) {
    db()->rollBack();
    json_response(['success' => false, 'error' => 'SERVER_ERROR'], 500);
}





