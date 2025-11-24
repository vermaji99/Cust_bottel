<?php
require __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    header('Location: cart.php');
    exit;
}

$action = $_POST['action'] ?? '';
$productId = (int) ($_POST['product_id'] ?? 0);
$redirect = $_POST['redirect'] ?? 'cart.php';

if ($action === 'add' && $productId > 0) {
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));
    $stmt = db()->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$authUser['id'], $productId]);
    $existing = (int) ($stmt->fetchColumn() ?: 0);
    cart_set_quantity($authUser['id'], $productId, $existing + $qty);
}

header('Location: ' . $redirect);
exit;





