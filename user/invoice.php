<?php
require __DIR__ . '/init.php';
$userId = $authUser['id'];

if (!isset($_GET['order_id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = (int) $_GET['order_id'];

// Fetch order details
$orderStmt = db()->prepare('
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
    LIMIT 1
');
$orderStmt->execute([$orderId, $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Check if order is delivered
if (strtolower($order['status']) !== 'delivered') {
    header('Location: orders.php');
    exit;
}

// Fetch order items
$itemsStmt = db()->prepare('
    SELECT oi.*, p.name as product_name, p.image as product_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
');
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total from items if needed
$orderTotal = (float)($order['total_amount'] ?? $order['total'] ?? 0);
if ($orderTotal == 0 && !empty($items)) {
    $orderTotal = 0;
    foreach ($items as $item) {
        $priceColumn = isset($item['unit_price']) ? 'unit_price' : 'price';
        $orderTotal += (float)($item[$priceColumn] ?? 0) * (int)($item['quantity'] ?? 1);
    }
}

$subtotal = (float)($order['subtotal'] ?? $orderTotal);
$discount = (float)($order['discount_total'] ?? 0);
$couponCode = $order['coupon_code'] ?? null;

// Get coupon details to show percentage if available
$discountPercentage = null;
if ($couponCode && $discount > 0 && $subtotal > 0) {
    try {
        $couponStmt = db()->prepare('SELECT type, value FROM coupons WHERE code = ? LIMIT 1');
        $couponStmt->execute([$couponCode]);
        $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);
        if ($coupon && $coupon['type'] === 'percent') {
            $discountPercentage = (float)$coupon['value'];
        }
    } catch (Exception $e) {
        // Ignore if coupon table doesn't exist or query fails
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice - Order #<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Space Grotesk', 'Poppins', sans-serif;
    background: #f5f5f5;
    padding: 20px;
    color: #333;
}
.invoice-container {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    padding: 40px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid #00bcd4;
}
.company-info h1 {
    color: #00bcd4;
    font-size: 2rem;
    margin-bottom: 10px;
}
.company-info p {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.6;
}
.invoice-title {
    text-align: right;
}
.invoice-title h2 {
    color: #333;
    font-size: 1.8rem;
    margin-bottom: 10px;
}
.invoice-title p {
    color: #666;
    font-size: 0.9rem;
}
.invoice-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}
.detail-section h3 {
    color: #00bcd4;
    font-size: 1rem;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.detail-section p {
    color: #333;
    margin-bottom: 8px;
    line-height: 1.6;
}
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
}
.items-table thead {
    background: #00bcd4;
    color: #fff;
}
.items-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}
.items-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}
.items-table tbody tr:hover {
    background: #f9f9f9;
}
.items-table .text-right {
    text-align: right;
}
.items-table .text-center {
    text-align: center;
}
.total-section {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}
.total-box {
    width: 300px;
}
.total-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}
.total-row:last-child {
    border-bottom: none;
}
.total-row.grand-total {
    font-size: 1.2rem;
    font-weight: 700;
    color: #00bcd4;
    padding-top: 15px;
    margin-top: 10px;
    border-top: 2px solid #00bcd4;
}
.footer {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    text-align: center;
    color: #666;
    font-size: 0.85rem;
}
.print-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #00bcd4;
    color: #fff;
    border: none;
    padding: 15px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 188, 212, 0.3);
    transition: all 0.3s;
    z-index: 1000;
}
.print-btn:hover {
    background: #0097a7;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 188, 212, 0.4);
}
@media print {
    .print-btn {
        display: none;
    }
    body {
        padding: 0;
    }
    .invoice-container {
        box-shadow: none;
    }
}
</style>
</head>
<body>
<div class="invoice-container">
    <div class="invoice-header">
        <div class="company-info">
            <h1>Bottle</h1>
            <p>Custom Bottle Design & Printing</p>
            <p style="margin-top: 10px;">
                Email: support@bottle.com<br>
                Phone: +91 1234567890
            </p>
        </div>
        <div class="invoice-title">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> <?= esc($order['order_number'] ?? ('ORD-' . $order['id'])); ?></p>
            <p><strong>Date:</strong> <?= date('d M Y', strtotime($order['created_at'])); ?></p>
        </div>
    </div>

    <div class="invoice-details">
        <div class="detail-section">
            <h3>Bill To</h3>
            <p><strong><?= esc($order['shipping_name'] ?? $order['user_name']); ?></strong></p>
            <p><?= esc($order['shipping_email'] ?? $order['user_email']); ?></p>
            <p><?= esc($order['shipping_phone'] ?? ''); ?></p>
            <p style="margin-top: 10px;"><?= nl2br(esc($order['shipping_address'] ?? '')); ?></p>
        </div>
        <div class="detail-section">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> <?= esc($order['order_number'] ?? ('ORD-' . $order['id'])); ?></p>
            <p><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
            <p><strong>Payment Method:</strong> <?= esc(ucfirst($order['payment_method'] ?? 'COD')); ?></p>
            <p><strong>Status:</strong> <span style="color: #4caf50; font-weight: 600;"><?= esc(ucfirst($order['status'])); ?></span></p>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-center">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $item): ?>
                <?php
                $priceColumn = isset($item['unit_price']) ? 'unit_price' : 'price';
                $unitPrice = (float)($item[$priceColumn] ?? 0);
                $quantity = (int)($item['quantity'] ?? 1);
                $itemTotal = $unitPrice * $quantity;
                ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= esc($item['product_name'] ?? 'Unknown Product'); ?></td>
                    <td class="text-center"><?= $quantity; ?></td>
                    <td class="text-right">₹<?= number_format($unitPrice, 2); ?></td>
                    <td class="text-right">₹<?= number_format($itemTotal, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-box">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₹<?= number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($discount > 0): ?>
                <div class="total-row">
                    <span>
                        Discount<?= $couponCode ? ' (' . esc($couponCode) . ')' : ''; ?>:
                        <?php if ($discountPercentage): ?>
                            <span style="color: #4caf50; font-size: 0.9rem;">(<?= number_format($discountPercentage, 0); ?>% off)</span>
                        <?php endif; ?>
                    </span>
                    <span style="color: #4caf50;">-₹<?= number_format($discount, 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span>Total Amount:</span>
                <span>₹<?= number_format($orderTotal, 2); ?></span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p style="margin-top: 10px;">This is a computer-generated invoice and does not require a signature.</p>
    </div>
</div>

<button class="print-btn" onclick="window.print()">
    <i class="fas fa-print"></i> Print Invoice
</button>

<script>
// Auto print on load (optional - uncomment if needed)
// window.onload = function() {
//     window.print();
// }
</script>
</body>
</html>

