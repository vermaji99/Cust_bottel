<?php
require __DIR__ . '/../includes/bootstrap.php';
$orderParam = $_GET['order'] ?? $_GET['order_id'] ?? null;
if (!$orderParam) {
    header('Location: dashboard.php');
    exit;
}

if (ctype_digit($orderParam)) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $orderParam]);
} else {
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
    $stmt->execute([$orderParam]);
}
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bottel | Thank You</title>
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            margin: 0;
            background: #0b0b0b;
            color: #eee;
            text-align: center;
        }
        header {
            background: rgba(0,0,0,0.95);
            padding: 15px 8%;
        }
        .logo { font-size: 1.5rem; color: #00bcd4; font-weight: bold; }
        .container {
            padding: 80px 8%;
            min-height: 70vh;
        }
        .confirmation-box {
            background: #141414;
            padding: 40px;
            border-radius: 15px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,188,212,0.2);
        }
        .confirmation-box i {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 20px;
        }
        .confirmation-box h1 {
            color: #00bcd4;
            margin-top: 0;
            font-size: 2.5rem;
        }
        .confirmation-box p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .order-details p {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #222;
            padding-bottom: 8px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .order-details span:first-child { color: #ccc; }
        .btn-group {
            margin-top: 30px;
        }
        .btn {
            background: linear-gradient(45deg, #00bcd4, #007bff);
            padding: 10px 25px;
            border-radius: 25px;
            color: #fff;
            text-decoration: none;
            transition: 0.3s;
            margin: 0 10px;
            display: inline-block;
        }
        .btn:hover { transform: scale(1.05); }
        footer {
            background: #080808;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Bottel</div>
</header>

<div class="container">
    <div class="confirmation-box">
        <i class="fas fa-check-circle"></i>
        <h1>Thank You for Your Order!</h1>
        <p>Your order has been successfully placed. We've sent a confirmation email (if email service is configured).</p>
        
        <div class="order-details" style="text-align:left;">
            <h3 style="color:white; border-bottom:1px solid #00bcd4;">Order Summary</h3>
            <p><span>Order #:</span> <strong><?= esc($order['order_number']); ?></strong></p>
            <p><span>Order Date:</span> <span><?= date('d M Y', strtotime($order['created_at'])) ?></span></p>
            <p><span>Payment Method:</span> <span><?= htmlspecialchars($order['payment_method']) ?></span></p>
            <p style="font-size:1.3rem; color:#4caf50;"><span>Total Amount:</span> <strong>₹<?= number_format($order['total_amount'], 2) ?></strong></p>
        </div>

        <div class="btn-group">
            <a href="../index.php" class="btn">Continue Shopping</a>
            <a href="orders.php" class="btn" style="background:#4caf50;">View My Orders</a>
        </div>
    </div>
</div>

<footer>
    © <?= date("Y") ?> Bottel. All rights reserved.
</footer>

</body>
<script src="../assets/js/app.js" defer></script>
</html>