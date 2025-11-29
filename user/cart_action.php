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
    // Verify product exists and is active
    $productCheck = db()->prepare('SELECT id, stock, is_active FROM products WHERE id = ? LIMIT 1');
    $productCheck->execute([$productId]);
    $product = $productCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['cart_error'] = 'Product not found.';
        header('Location: ' . $redirect);
        exit;
    }
    
    if (!$product['is_active'] || $product['stock'] <= 0) {
        $_SESSION['cart_error'] = 'Product is out of stock or unavailable.';
        header('Location: ' . $redirect);
        exit;
    }
    
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));
    
    // Check for existing quantity - ensure no duplicates
    $stmt = db()->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->execute([$authUser['id'], $productId]);
    $existing = (int) ($stmt->fetchColumn() ?: 0);
    
    // Remove any duplicate entries first (if unique constraint doesn't exist)
    $cleanup = db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
    $cleanup->execute([$authUser['id'], $productId]);
    
    // Now add with correct quantity
    $newQuantity = $existing + $qty;
    if ($newQuantity > $product['stock']) {
        $newQuantity = (int) $product['stock'];
    }
    
    cart_set_quantity($authUser['id'], $productId, $newQuantity);
    $_SESSION['cart_success'] = 'Product added to cart!';
}

header('Location: ' . $redirect);
exit;





