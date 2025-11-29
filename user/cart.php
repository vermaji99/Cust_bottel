<?php
require __DIR__ . '/init.php';
$userId = $authUser['id'];
$messages = [];
$errors = [];
$appliedCouponCode = $_SESSION['cart_coupon'] ?? null;
$appliedCoupon = $appliedCouponCode ? find_coupon($appliedCouponCode) : null;

cart_clear_unavailable($userId);

// Clean up duplicate cart items (remove duplicates, keep the one with highest quantity)
$cleanupDuplicates = db()->prepare('
    DELETE c1 FROM cart_items c1
    INNER JOIN cart_items c2 
    WHERE c1.id < c2.id 
    AND c1.user_id = c2.user_id 
    AND c1.product_id = c2.product_id
    AND c1.user_id = ?
');
$cleanupDuplicates->execute([$userId]);

// Display messages from cart actions
if (isset($_SESSION['cart_success'])) {
    $messages[] = $_SESSION['cart_success'];
    unset($_SESSION['cart_success']);
}
if (isset($_SESSION['cart_error'])) {
    $errors[] = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired. Please refresh.';
    } else {
        if (isset($_POST['update_cart']) && isset($_POST['qty'])) {
            foreach ((array) $_POST['qty'] as $productId => $qty) {
                $quantity = max(0, (int) $qty);
                cart_set_quantity($userId, (int) $productId, $quantity);
            }
            $_SESSION['cart_success'] = 'Cart updated successfully.';
            header('Location: cart.php');
            exit;
        } elseif (isset($_POST['remove_id'])) {
            cart_remove($userId, (int) $_POST['remove_id']);
            $_SESSION['cart_success'] = 'Item removed.';
            header('Location: cart.php');
            exit;
        }
    }
}

$items = cart_items_detailed($userId);
$subtotal = 0;
foreach ($items as &$item) {
    $quantity = (int) $item['quantity'];
    
    // Validate product is still available
    if (empty($item['name']) || !isset($item['price'])) {
        // Product was deleted, remove from cart
        cart_remove($userId, $item['product_id']);
        continue;
    }
    
    if ($quantity > (int) $item['stock']) {
        $quantity = max(0, (int) $item['stock']);
        cart_set_quantity($userId, $item['product_id'], $quantity);
        $item['quantity'] = $quantity;
        if ($quantity > 0) {
            $messages[] = "{$item['name']} quantity adjusted based on stock.";
        }
    }
    
    // Use price_snapshot if available (price when added to cart), otherwise current price
    $itemPrice = !empty($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$item['price'];
    $item['display_price'] = $itemPrice;
    $item['line_total'] = $quantity * $itemPrice;
    $subtotal += $item['line_total'];
}
unset($item); // Unset reference

// Calculate discount and total - ensure proper rounding
$subtotal = round($subtotal, 2);
$discount = $appliedCoupon ? calculate_coupon_discount($subtotal, $appliedCoupon) : 0;
$discount = round($discount, 2);
$grandTotal = round(max($subtotal - $discount, 0), 2);
$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart | Bottle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        /* CSS is unchanged, it was already well-written */
        body {
            margin: 0;
            font-family: 'Space Grotesk', 'Poppins', sans-serif;
            background: #0b0b0b;
            color: #f0f0f0;
            padding-top: 0;
        }
        .icon {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
        
        main {
            padding: 120px 8% 60px;
            min-height: 80vh;
        }

        h1 {
            text-align: center;
            color: #00bcd4;
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #141414;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: center;
        }

        th {
            background: #1c1c1c;
            color: #00bcd4;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        tr:nth-child(even) {
            background: #111;
        }

        img {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
        }

        input[type="number"] {
            width: 60px;
            background: #222;
            border: 1px solid #333;
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
        }

        .btn {
            background: linear-gradient(45deg, #00bcd4, #007bff);
            border: none;
            padding: 10px 20px;
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            display: inline-block;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .remove {
            color: #ff4444;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .cart-total {
            margin-top: 40px;
            text-align: right;
            background: #141414;
            padding: 25px 30px;
            border-radius: 12px;
            max-width: 400px;
            margin-left: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        .cart-total p {
            margin: 12px 0;
            font-size: 1rem;
            color: #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-total p:first-child {
            margin-top: 0;
        }
        
        .cart-total .total-label {
            color: #b0b0b0;
            font-weight: 500;
        }
        
        .cart-total .total-value {
            color: #00bcd4;
            font-weight: 600;
        }
        
        .cart-total .discount-row {
            color: #4caf50;
        }
        
        .cart-total .discount-value {
            color: #4caf50;
            font-weight: 600;
        }
        
        .cart-total .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 188, 212, 0.3), transparent);
            margin: 15px 0;
            border: none;
        }
        
        .cart-total .grand-total {
            font-size: 1.4rem;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(0, 188, 212, 0.3);
        }
        
        .cart-total .grand-total .total-label {
            color: #00bcd4;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .cart-total .grand-total .total-value {
            color: #00bcd4;
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .cart-total .action-buttons {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .cart-total .action-buttons .btn {
            padding: 12px 25px;
            font-size: 0.95rem;
        }
        
        footer {
            background: #080808;
            text-align: center;
            padding: 40px 10%;
            color: #777;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            table { font-size: 0.9rem; }
            img { width: 60px; height: 60px; }
            
            .cart-total {
                max-width: 100%;
                padding: 20px;
            }
            
            .cart-total p {
                font-size: 0.95rem;
            }
            
            .cart-total .grand-total {
                font-size: 1.2rem;
            }
            
            .cart-total .grand-total .total-label,
            .cart-total .grand-total .total-value {
                font-size: 1.1rem;
            }
            
            .cart-total .action-buttons {
                flex-direction: column;
            }
            
            .cart-total .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php
$currentPage = 'cart';
include __DIR__ . '/includes/navbar.php';
?>

<main>
    <h1>🛒 Your Cart</h1>

    <?php if ($messages): ?>
        <div style="background:#103a2d;padding:12px;border-radius:8px;color:#6ef1c2;margin-bottom:20px;">
            <?= esc(implode(' ', $messages)); ?>
        </div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="background:#3a1010;padding:12px;border-radius:8px;color:#ffb3b3;margin-bottom:20px;">
            <?= esc(implode(' ', $errors)); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <p style="text-align:center;color:#ccc;">Your cart is empty. <a href="../category.php" style="color:#00bcd4;">Start shopping now!</a></p>
    <?php else: ?>
        <form method="POST">
            <?= csrf_field(); ?>
            <table>
                <tr>
                    <th>Product</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Remove</th>
                </tr>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><img src="../admin/uploads/<?= esc($item['image']); ?>" alt="<?= esc($item['name']); ?>"></td>
                    <td>
                        <a href="../product.php?id=<?= $item['product_id']; ?>" style="color:white;"><?= esc($item['name']); ?></a>
                        <?php if ((int)$item['stock'] === 0): ?>
                            <div style="color:#ff6b6b;font-size:0.85rem;">Out of stock</div>
                        <?php endif; ?>
                    </td>
                    <td>₹<span class="item-price" data-price="<?= $item['display_price'] ?? $item['price']; ?>"><?= number_format($item['display_price'] ?? $item['price'], 2); ?></span></td>
                    <td><input type="number" name="qty[<?= $item['product_id']; ?>]" value="<?= (int)$item['quantity']; ?>" min="0" max="<?= (int)$item['stock']; ?>" class="qty-input" data-product-id="<?= $item['product_id']; ?>" data-price="<?= $item['display_price'] ?? $item['price']; ?>"></td>
                    <td>₹<span class="item-total" data-product-id="<?= $item['product_id']; ?>"><?= number_format($item['line_total'], 2); ?></span></td>
                    <td>
                        <button type="submit" name="remove_id" value="<?= $item['product_id']; ?>" class="remove" style="background:none;border:none;color:#ff4444;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="cart-total">
                <p>
                    <span class="total-label">Subtotal:</span>
                    <span class="total-value">₹<span id="cart-subtotal"><?= number_format($subtotal, 2); ?></span></span>
                </p>
                <hr class="divider">
                <p class="grand-total">
                    <span class="total-label">Total:</span>
                    <span class="total-value">₹<span id="cart-grandtotal"><?= number_format($subtotal, 2) ?></span></span>
                </p>
                <div class="action-buttons">
                    <button type="submit" name="update_cart" class="btn">Update Cart</button>
                    <a href="checkout.php" class="btn" style="background: linear-gradient(45deg, #4caf50, #388e3c);">Proceed to Checkout</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>

<footer>
    <p>© 2025 Bottle. All Rights Reserved.</p>
</footer>

</body>
<script src="../assets/js/navbar.js" defer></script>
<script src="../assets/js/app.js" defer></script>
<script>
// Real-time cart total calculation
(function() {
    const qtyInputs = document.querySelectorAll('.qty-input');
    const couponData = <?= json_encode($appliedCoupon ? ['code' => $appliedCoupon['code'], 'type' => $appliedCoupon['type'], 'value' => (float)$appliedCoupon['value'], 'max_discount' => isset($appliedCoupon['max_discount']) ? (float)$appliedCoupon['max_discount'] : null, 'min_amount' => isset($appliedCoupon['min_amount']) ? (float)$appliedCoupon['min_amount'] : null] : null); ?>;
    
    function calculateTotals() {
        let subtotal = 0;
        
        qtyInputs.forEach(input => {
            const quantity = parseInt(input.value) || 0;
            const price = parseFloat(input.getAttribute('data-price')) || 0;
            const productId = input.getAttribute('data-product-id');
            const lineTotal = quantity * price;
            subtotal += lineTotal;
            
            // Update line total display
            const totalElement = document.querySelector(`.item-total[data-product-id="${productId}"]`);
            if (totalElement) {
                totalElement.textContent = lineTotal.toFixed(2);
            }
        });
        
        // Calculate discount exactly like server-side PHP function
        let discount = 0;
        if (couponData && subtotal > 0) {
            // Check minimum amount requirement
            if (couponData.min_amount && subtotal < couponData.min_amount) {
                discount = 0;
            } else {
                if (couponData.type === 'percent') {
                    discount = subtotal * (parseFloat(couponData.value) / 100);
                    if (couponData.max_discount && discount > parseFloat(couponData.max_discount)) {
                        discount = parseFloat(couponData.max_discount);
                    }
                } else if (couponData.type === 'flat') {
                    discount = parseFloat(couponData.value);
                }
            }
            // Round to 2 decimal places (like PHP round function)
            discount = Math.round(discount * 100) / 100;
            // Ensure discount is not negative
            discount = Math.max(0, discount);
        }
        
        const grandTotal = Math.max(0, subtotal - discount);
        
        // Update display
        const subtotalEl = document.getElementById('cart-subtotal');
        const discountEl = document.getElementById('cart-discount');
        const grandTotalEl = document.getElementById('cart-grandtotal');
        const discountRow = document.getElementById('discount-row');
        
        if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2);
        
        // Update discount display
        if (discountEl) {
            // Find the span inside discount-value that contains the amount
            const discountAmountSpan = discountEl.closest('.discount-value')?.querySelector('span');
            if (discountAmountSpan && discountAmountSpan.id === 'cart-discount') {
                discountAmountSpan.textContent = discount.toFixed(2);
            } else if (discountEl.id === 'cart-discount') {
                discountEl.textContent = discount.toFixed(2);
            }
        }
        
        // Show/hide discount row
        const discountRowElement = document.querySelector('.discount-row') || document.getElementById('discount-row');
        if (discountRowElement) {
            if (couponData && discount > 0) {
                discountRowElement.style.display = 'flex';
            } else {
                discountRowElement.style.display = 'none';
            }
        }
        
        // Update grand total
        if (grandTotalEl) {
            const grandTotalSpan = grandTotalEl.querySelector('span');
            if (grandTotalSpan && grandTotalSpan.id === 'cart-grandtotal') {
                grandTotalSpan.textContent = grandTotal.toFixed(2);
            } else if (grandTotalEl.id === 'cart-grandtotal') {
                grandTotalEl.textContent = grandTotal.toFixed(2);
            }
        }
    }
    
    // Add event listeners to all quantity inputs
    // Only update when user actually changes the value, not on page load
    qtyInputs.forEach(input => {
        let lastValue = input.value;
        input.addEventListener('input', function() {
            if (this.value !== lastValue) {
                calculateTotals();
                lastValue = this.value;
            }
        });
        input.addEventListener('change', function() {
            if (this.value !== lastValue) {
                calculateTotals();
                lastValue = this.value;
            }
        });
    });
    
    // Don't run calculation on page load - server-side values are already correct
    // JavaScript only updates when user changes quantity
})();
</script>
</html>