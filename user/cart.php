<?php
require __DIR__ . '/init.php';
$userId = $authUser['id'];
$messages = [];
$errors = [];
$appliedCouponCode = $_SESSION['cart_coupon'] ?? null;
$appliedCoupon = $appliedCouponCode ? find_coupon($appliedCouponCode) : null;

cart_clear_unavailable($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired. Please refresh.';
    } else {
        if (isset($_POST['update_cart']) && isset($_POST['qty'])) {
            foreach ((array) $_POST['qty'] as $productId => $qty) {
                $quantity = max(0, (int) $qty);
                cart_set_quantity($userId, (int) $productId, $quantity);
            }
            $messages[] = 'Cart updated.';
        } elseif (isset($_POST['remove_id'])) {
            cart_remove($userId, (int) $_POST['remove_id']);
            $messages[] = 'Item removed.';
        } elseif (isset($_POST['apply_coupon'])) {
            $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
            $coupon = $code ? find_coupon($code) : null;
            if ($coupon) {
                $_SESSION['cart_coupon'] = $coupon['code'];
                $appliedCouponCode = $coupon['code'];
                $appliedCoupon = $coupon;
                $messages[] = 'Coupon applied.';
            } else {
                $errors[] = 'Invalid or expired coupon code.';
            }
        } elseif (isset($_POST['remove_coupon'])) {
            unset($_SESSION['cart_coupon']);
            $appliedCoupon = null;
            $appliedCouponCode = null;
        }
    }
}

$items = cart_items_detailed($userId);
$subtotal = 0;
foreach ($items as &$item) {
    $quantity = (int) $item['quantity'];
    if ($quantity > (int) $item['stock']) {
        $quantity = (int) $item['stock'];
        cart_set_quantity($userId, $item['product_id'], $quantity);
        $item['quantity'] = $quantity;
        $messages[] = "{$item['name']} quantity adjusted based on stock.";
    }
    $item['line_total'] = $quantity * (float) $item['price'];
    $subtotal += $item['line_total'];
}
$discount = $appliedCoupon ? calculate_coupon_discount($subtotal, $appliedCoupon) : 0;
$grandTotal = max($subtotal - $discount, 0);
$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart | Bottel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS is unchanged, it was already well-written */
        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #0b0b0b;
            color: #f0f0f0;
        }

        header {
            background: rgba(0, 0, 0, 0.9);
            padding: 15px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo { font-size: 1.5rem; font-weight: bold; color: #00bcd4; }
        nav ul { list-style: none; display: flex; gap: 25px; }
        nav a { text-decoration: none; color: white; transition: 0.3s; }
        nav a:hover { color: #00bcd4; }

        .icons { /* Added icons style for consistency with index.php */
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 1.2rem;
            color: #fff;
        }
        
        main {
            padding: 80px 8% 60px;
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
            font-size: 1.3rem;
            color: #00bcd4;
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
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Bottel</div>
    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../category.php">Shop</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="icons">
        <a href="cart.php" style="color:#00bcd4;"><i class="fas fa-shopping-cart"></i></a>
        <a href="profile.php"><i class="fas fa-user"></i></a>
    </div>
</header>

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
                    <td>₹<?= number_format($item['price'], 2); ?></td>
                    <td><input type="number" name="qty[<?= $item['product_id']; ?>]" value="<?= (int)$item['quantity']; ?>" min="0"></td>
                    <td>₹<?= number_format($item['line_total'], 2); ?></td>
                    <td>
                        <button type="submit" name="remove_id" value="<?= $item['product_id']; ?>" class="remove" style="background:none;border:none;color:#ff4444;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="cart-total">
                <p>Subtotal: ₹<?= number_format($subtotal, 2); ?></p>
                <?php if ($appliedCoupon): ?>
                    <p>Coupon (<?= esc($appliedCoupon['code']); ?>): -₹<?= number_format($discount, 2); ?>
                        <button type="submit" name="remove_coupon" style="background:none;border:none;color:#ff6b6b;cursor:pointer;">Remove</button>
                    </p>
                <?php endif; ?>
                <p><strong>Total: ₹<?= number_format($grandTotal, 2) ?></strong></p>
                <button type="submit" name="update_cart" class="btn">Update Cart</button>
                <a href="checkout.php" class="btn">Proceed to Checkout</a>
            </div>
        </form>
        <form method="POST" style="margin-top:20px;max-width:320px;">
            <?= csrf_field(); ?>
            <label for="coupon_code" style="display:block;margin-bottom:6px;">Coupon code</label>
            <input type="text" id="coupon_code" name="coupon_code" placeholder="Enter code" style="width:100%;padding:10px;border-radius:8px;border:1px solid #333;background:#1c1c1c;color:#fff;">
            <button type="submit" name="apply_coupon" class="btn" style="margin-top:10px;width:100%;">Apply Coupon</button>
        </form>
    <?php endif; ?>
</main>

<footer>
    <p>© 2025 Bottel. All Rights Reserved.</p>
</footer>

</body>
<script src="../assets/js/app.js" defer></script>
</html>
</body>
</html>