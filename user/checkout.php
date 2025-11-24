<?php
require __DIR__ . '/init.php';
$userId = $authUser['id'];
$messages = [];
$errors = [];
$userStmt = db()->prepare('SELECT name, email, phone, address, city, pincode FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

cart_clear_unavailable($userId);
$cartItems = cart_items_detailed($userId);
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$appliedCouponCode = $_SESSION['cart_coupon'] ?? null;
$appliedCoupon = $appliedCouponCode ? find_coupon($appliedCouponCode) : null;

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}
$discount = $appliedCoupon ? calculate_coupon_discount($subtotal, $appliedCoupon) : 0;
$grandTotal = max($subtotal - $discount, 0);

$designStmt = db()->prepare('SELECT id, design_key, thumbnail_path FROM designs WHERE user_id = ? ORDER BY created_at DESC');
$designStmt->execute([$userId]);
$designs = $designStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired. Please refresh.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $payment = $_POST['payment'] ?? 'COD';
        $designKey = $_POST['design_key'] ?? null;

        if (!$name || !$email || !$phone || !$address) {
            $errors[] = 'Please fill out all shipping details.';
        }

        $selectedDesignId = null;
        if ($designKey) {
            $designLookup = db()->prepare('SELECT id FROM designs WHERE design_key = ? AND user_id = ? LIMIT 1');
            $designLookup->execute([$designKey, $userId]);
            $selectedDesignId = $designLookup->fetchColumn() ?: null;
        }

        if (!$errors) {
            db()->beginTransaction();
            try {
                $orderStmt = db()->prepare('
                    INSERT INTO orders
                    (user_id, order_number, design_id, coupon_code, subtotal, discount_total, total_amount, status, payment_method, shipping_name, shipping_email, shipping_phone, shipping_address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $orderNumber = 'BTL' . strtoupper(bin2hex(random_bytes(3)));
                $status = 'pending';
                $orderStmt->execute([
                    $userId,
                    $orderNumber,
                    $selectedDesignId,
                    $appliedCouponCode,
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

                $timelineStmt = db()->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
                $timelineStmt->execute([$orderId, $status, 'Order received.']);

                $clearCart = db()->prepare('DELETE FROM cart_items WHERE user_id = ?');
                $clearCart->execute([$userId]);
                unset($_SESSION['cart_coupon']);

                send_order_confirmation_email($orderId);

                db()->commit();
                header('Location: thank_you.php?order=' . urlencode($orderNumber));
                exit;
            } catch (Throwable $e) {
                db()->rollBack();
                $errors[] = 'Failed to place order. Please try again.';
            }
        }
    }
}
$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout | Bottel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #0b0b0b;
            color: #f0f0f0;
        }

        header {
            background: rgba(0,0,0,0.9);
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

        main {
            padding: 80px 8% 60px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            min-height: 70vh;
        }

        .checkout-form, .summary {
            background: #141414;
            padding: 25px;
            border-radius: 12px;
        }

        h1 {
            color: #00bcd4;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-prompt {
            background: #00bcd415;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #00bcd4;
            text-align: center;
            font-weight: 500;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #333;
            background: #1a1a1a;
            color: white;
            font-size: 1rem;
        }

        textarea { resize: none; height: 100px; }

        .btn {
            background: linear-gradient(45deg, #00bcd4, #007bff);
            border: none;
            padding: 12px 20px;
            color: white;
            border-radius: 25px;
            width: 100%;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn:hover { transform: scale(1.02); }

        .summary h3 {
            color: #00bcd4;
            border-bottom: 1px solid #222;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #ccc;
        }

        .total {
            border-top: 1px solid #333;
            margin-top: 15px;
            padding-top: 10px;
            font-size: 1.2rem;
            color: #00bcd4;
            font-weight: bold;
        }
        
        .error {
            background: #842029;
            color: #f8d7da;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        footer {
            background: #080808;
            text-align: center;
            padding: 40px 10%;
            color: #777;
            margin-top: 50px;
        }

        @media (max-width: 900px) {
            main { grid-template-columns: 1fr; }
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
            <li><a href="cart.php" style="color:#00bcd4;">Cart</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
        </ul>
    </nav>
</header>

<main>
    <form method="POST" class="checkout-form">
        <?= csrf_field(); ?>
        <h1>Checkout Details</h1>

        <?php if ($errors): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= esc(implode(' ', $errors)); ?></div>
        <?php endif; ?>

        <h2>1. Shipping Information</h2>
        
        <label>Full Name</label>
        <input type="text" name="name" value="<?= esc($userData['name'] ?? $authUser['name']); ?>" required>

        <label>Email Address</label>
        <input type="email" name="email" value="<?= esc($userData['email'] ?? $authUser['email']); ?>" required>

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?= esc($userData['phone'] ?? ''); ?>" required>

        <label>Shipping Address</label>
        <?php
            $fullAddress = '';
            if (!empty($userData['address'])) {
                $fullAddress = $userData['address'];
                if (!empty($userData['city'])) {
                    $fullAddress .= ', ' . $userData['city'];
                }
                if (!empty($userData['pincode'])) {
                    $fullAddress .= ' - ' . $userData['pincode'];
                }
            }
        ?>
        <textarea name="address" required><?= esc($fullAddress); ?></textarea>

        <h2>2. Attach Design (optional)</h2>
        <?php if ($designs): ?>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                <?php foreach ($designs as $design): ?>
                    <label style="background:#1c1c1c;padding:10px;border-radius:10px;display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="radio" name="design_key" value="<?= esc($design['design_key']); ?>">
                        <img src="../<?= esc($design['thumbnail_path']); ?>" alt="Design thumbnail" style="width:60px;border-radius:8px;">
                        <?= esc($design['design_key']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#aaa;">No saved designs yet. Save one from the customizer.</p>
        <?php endif; ?>
        
        <h2>3. Payment Method</h2>
        
        <label>Select Payment Option</label>
        <select name="payment" required>
            <option value="COD">Cash on Delivery</option>
            <option value="Online">Online Payment (e.g., UPI/Card)</option>
        </select>

        <button type="submit" name="place_order" class="btn">Place Order (₹<?= number_format($total, 2) ?>)</button>
    </form>

    <div class="summary">
        <h3>Order Summary</h3>
        <?php foreach ($cartItems as $item): ?>
            <div class="summary-item">
                <span><?= esc($item['name']); ?> × <?= (int) $item['quantity']; ?></span>
                <span>₹<?= number_format($item['quantity'] * $item['price'], 2); ?></span>
            </div>
        <?php endforeach; ?>
        <div class="summary-item" style="margin-top:15px;">
            <span>Subtotal</span>
            <span>₹<?= number_format($subtotal, 2); ?></span>
        </div>
        <?php if ($appliedCoupon): ?>
            <div class="summary-item">
                <span>Coupon (<?= esc($appliedCoupon['code']); ?>)</span>
                <span>-₹<?= number_format($discount, 2); ?></span>
            </div>
        <?php endif; ?>
        <div class="total">
            Total Payable: ₹<?= number_format($grandTotal, 2) ?>
        </div>
    </div>
</main>

<footer>
    <p>© 2025 Bottel. All Rights Reserved.</p>
</footer>

</body>
<script src="../assets/js/app.js" defer></script>
</html>