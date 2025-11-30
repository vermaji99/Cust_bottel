<?php
require __DIR__ . '/../includes/bootstrap.php';

// Get current user first
$currentUser = current_user();
if (!$currentUser) {
    header('Location: ../login.php');
    exit;
}

$orderParam = $_GET['order'] ?? $_GET['order_id'] ?? null;
if (!$orderParam) {
    header('Location: orders.php');
    exit;
}

// Optimized: Try ID first (most common case)
if (ctype_digit($orderParam) || is_numeric($orderParam)) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([(int) $orderParam, $currentUser['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Try order_number (if exists)
    $stmt = db()->prepare('SELECT * FROM orders WHERE (order_number = ? OR order_number = ?) AND user_id = ? LIMIT 1');
    $stmt->execute([$orderParam, 'ORD-' . $orderParam, $currentUser['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Generate order number for display
$orderNumber = $order['order_number'] ?? ('ORD-' . $order['id']);

// Calculate total amount - comprehensive approach
$totalAmount = 0;
$subtotal = 0;
$discount = 0;

// First, try to get from order record (most reliable)
if (isset($order['total_amount']) && (float)$order['total_amount'] > 0) {
    $totalAmount = (float)$order['total_amount'];
} elseif (isset($order['total']) && (float)$order['total'] > 0) {
    $totalAmount = (float)$order['total'];
} else {
    // Calculate from order_items if total_amount/total is 0 or missing
    try {
        // Get subtotal from order_items - handle both unit_price and price columns
        $itemsStmt = db()->prepare("
            SELECT 
                COALESCE(SUM(quantity * COALESCE(NULLIF(unit_price, 0), NULLIF(price, 0), 0)), 0) as calculated_subtotal
            FROM order_items 
            WHERE order_id = ?
        ");
        $itemsStmt->execute([$order['id']]);
        $result = $itemsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['calculated_subtotal']) && (float)$result['calculated_subtotal'] > 0) {
            $subtotal = (float)$result['calculated_subtotal'];
        } else {
            // Try to get subtotal from order record
            if (isset($order['subtotal']) && (float)$order['subtotal'] > 0) {
                $subtotal = (float)$order['subtotal'];
            }
        }
        
        // Get discount
        if (isset($order['discount_total']) && (float)$order['discount_total'] > 0) {
            $discount = (float)$order['discount_total'];
        }
        
        // Calculate final total
        if ($subtotal > 0) {
            $totalAmount = max(0, $subtotal - $discount);
        } else {
            // Last resort: try to calculate from individual items with proper column detection
            $itemDetailsStmt = db()->prepare("
                SELECT quantity, unit_price, price 
                FROM order_items 
                WHERE order_id = ?
            ");
            $itemDetailsStmt->execute([$order['id']]);
            $itemDetails = $itemDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($itemDetails as $item) {
                $itemPrice = 0;
                if (isset($item['unit_price']) && (float)$item['unit_price'] > 0) {
                    $itemPrice = (float)$item['unit_price'];
                } elseif (isset($item['price']) && (float)$item['price'] > 0) {
                    $itemPrice = (float)$item['price'];
                }
                $quantity = (int)($item['quantity'] ?? 1);
                $subtotal += $itemPrice * $quantity;
            }
            
            if ($subtotal > 0) {
                $totalAmount = max(0, $subtotal - $discount);
            }
        }
    } catch (Exception $e) {
        error_log('Error calculating order total in thank_you.php: ' . $e->getMessage());
        // Final fallback
        if (isset($order['total']) && (float)$order['total'] > 0) {
            $totalAmount = (float)$order['total'];
        }
    }
}

// Ensure we have a valid total
if ($totalAmount <= 0 && isset($order['subtotal']) && (float)$order['subtotal'] > 0) {
    $subtotal = (float)$order['subtotal'];
    $discount = isset($order['discount_total']) ? (float)$order['discount_total'] : 0;
    $totalAmount = max(0, $subtotal - $discount);
}

$paymentMethod = $order['payment_method'] ?? 'COD';
$orderDate = $order['created_at'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bottle | Thank You</title>
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Poppins", sans-serif;
            background: radial-gradient(circle at 50% 0%, #1a1f25 0%, #0b0b0b 60%);
            color: #e0e0e0;
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }

        a { text-decoration: none; color: inherit; transition: 0.3s; }
        img { max-width: 100%; display: block; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 60px;
            width: 100%;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .confirmation-box {
            background: #161616;
            padding: 50px;
            border-radius: 20px;
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            border: 1px solid rgba(0, 188, 212, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 188, 212, 0.1);
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .confirmation-box i {
            font-size: 4.5rem;
            color: #4caf50;
            margin-bottom: 25px;
            animation: scaleIn 0.6s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .confirmation-box h1 {
            background: linear-gradient(90deg, #00bcd4, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .confirmation-box > p {
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 35px;
            color: #a0a0a0;
        }

        .order-details {
            text-align: left;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 188, 212, 0.15);
        }

        .order-details h3 {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 22px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0, 188, 212, 0.15);
        }

        .order-details p {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            margin-bottom: 10px;
            font-weight: 500;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .order-details p:last-child {
            border-bottom: none;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 2px solid rgba(0, 188, 212, 0.2);
        }

        .order-details span:first-child {
            color: #a0a0a0;
            font-weight: 500;
        }

        .order-details strong {
            color: #00bcd4;
            font-weight: 600;
        }

        .order-details p:last-child strong {
            background: linear-gradient(90deg, #4caf50, #388e3c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .btn-group {
            margin-top: 32px;
            display: flex;
            gap: 16px;
            justify-content: center;
            align-items: center;
            flex-wrap: nowrap;
        }

        .btn {
            background: linear-gradient(135deg, #00bcd4, #007bff);
            padding: 14px 32px;
            border-radius: 50px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            font-family: inherit;
            box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
        }

        .btn i {
            font-size: 1rem;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
        }

        .btn.btn-success {
            background: linear-gradient(135deg, #4caf50, #388e3c);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn.btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
        }

        /* --- FOOTER --- */
        footer {
            background: #080808;
            padding: 70px 0 30px;
            border-top: 1px solid #1a1a1a;
            margin-top: 80px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 50px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 20px;
        }

        .footer-col h4 {
            color: #fff;
            font-size: 1.1rem;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 30px;
            height: 2px;
            background: #00bcd4;
        }

        .footer-col p {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .footer-col a {
            color: #888;
            transition: 0.3s;
        }

        .footer-col a:hover {
            color: #00bcd4;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .social-links a {
            width: 36px;
            height: 36px;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
            transition: 0.3s;
        }

        .social-links a:hover {
            background: #00bcd4;
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #1a1a1a;
            color: #555;
            font-size: 0.85rem;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 60px 4%;
            }

            .confirmation-box {
                padding: 32px 24px;
            }

            .confirmation-box h1 {
                font-size: 1.75rem;
            }

            .btn-group {
                flex-direction: row;
                gap: 12px;
            }

            .btn {
                min-width: auto;
                padding: 6px 12px;
                font-size: 0.75rem;
            }

            .btn i {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>

<?php
$currentPage = 'thankyou';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div class="confirmation-box">
        <i class="fas fa-check-circle"></i>
        <h1>Thank You for Your Order!</h1>
        <p>Your order has been successfully placed. We've sent a confirmation email (if email service is configured).</p>
        
        <div class="order-details" style="text-align:left;">
            <h3 style="color:white; border-bottom:1px solid #00bcd4;">Order Summary</h3>
            <p><span>Order #:</span> <strong><?= esc($orderNumber); ?></strong></p>
            <p><span>Order Date:</span> <span><?= date('d M Y', strtotime($orderDate)) ?></span></p>
            <p><span>Payment Method:</span> <span><?= htmlspecialchars($paymentMethod) ?></span></p>
            <p style="font-size:1.3rem; color:#4caf50;"><span>Total Amount:</span> <strong>₹<?= number_format((float)$totalAmount, 2) ?></strong></p>
        </div>

        <div class="btn-group">
            <a href="../index.php" class="btn">
                Continue Shopping
            </a>
            <a href="orders.php" class="btn btn-success">
                View My Orders
            </a>
        </div>
    </div>
</div>

<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4>About Bottle</h4>
            <p>We craft personalized premium water bottles for restaurants & events across India. Quality meets elegance.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <p><a href="../category.php">Shop Now</a></p>
            <p><a href="../about.php">About Us</a></p>
            <p><a href="../contact.php">Contact</a></p>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p>support@bottle.com</p>
            <p>+91 98765 43210</p>
            <p>Indore, India</p>
        </div>
        <div class="footer-col">
            <h4>Follow Us</h4>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; <?= date('Y'); ?> Bottle. All rights reserved.</p>
    </div>
</footer>

<script src="../assets/js/navbar.js" defer></script>
<script src="../assets/js/app.js" defer></script>
</body>
</html>