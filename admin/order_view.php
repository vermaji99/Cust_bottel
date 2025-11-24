<?php
include '../includes/config.php';
include 'auth_check.php';

// agar ID missing ya galat hai
if (!isset($_GET['id']) || empty($_GET['id'])) {
  die("<h2 style='color:white;text-align:center;margin-top:50px;'>Invalid Order ID.</h2>");
}

$order_id = intval($_GET['id']);

// Order fetch karo
$stmt = $pdo->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
  die("<h2 style='color:white;text-align:center;margin-top:50px;'>Order not found.</h2>");
}

// Items fetch karo (agar table hai)
$items = [];
try {
  $itemStmt = $pdo->prepare("SELECT oi.*, p.name AS product_name, p.price 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
  $itemStmt->execute([$order_id]);
  $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // agar order_items table nahi hai, ignore karo
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin | View Order #<?= $order_id ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      background: #0b0b0b;
      color: #eee;
      font-family: "Poppins", sans-serif;
      margin: 0;
      padding: 0;
    }
    header {
      background: #111;
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }
    header h2 { color: #00bcd4; }
    a.back-btn {
      background: linear-gradient(45deg, #00bcd4, #007bff);
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      transition: 0.3s;
    }
    a.back-btn:hover { transform: scale(1.05); }

    .container {
      max-width: 900px;
      margin: 40px auto;
      background: #141414;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,188,212,0.15);
    }

    h3 {
      color: #00bcd4;
      border-bottom: 1px solid #222;
      padding-bottom: 8px;
    }

    .order-info p, .customer-info p {
      margin: 8px 0;
      color: #ccc;
    }

    .order-info strong, .customer-info strong {
      color: #00bcd4;
    }

    table {
      width: 100%;
      margin-top: 20px;
      border-collapse: collapse;
      color: #eee;
    }

    th, td {
      padding: 10px;
      border-bottom: 1px solid #333;
      text-align: left;
    }

    th {
      background: #1a1a1a;
      color: #00bcd4;
    }

    tr:hover { background: #1b1b1b; }

    .total {
      text-align: right;
      margin-top: 15px;
      font-size: 1.1rem;
      color: #00bcd4;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 8px;
      font-size: 0.9rem;
      text-transform: capitalize;
    }
    .Pending { background: #ff9800; color: #111; }
    .Processing { background: #2196f3; color: #fff; }
    .Shipped { background: #9c27b0; color: #fff; }
    .Delivered { background: #4caf50; color: #fff; }
    .Cancelled { background: #f44336; color: #fff; }
  </style>
</head>
<body>

<header>
  <h2>📦 Order #<?= htmlspecialchars($order['id']) ?></h2>
  <a href="orders.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
</header>

<div class="container">
  <h3>Customer Information</h3>
  <div class="customer-info">
    <p><strong>Name:</strong> <?= htmlspecialchars($order['name'] ?: $order['user_name'] ?: 'N/A') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($order['email'] ?: $order['user_email'] ?: '-') ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone'] ?: '-') ?></p>
    <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['address'] ?: '-')) ?></p>
  </div>

  <h3>Order Details</h3>
  <div class="order-info">
    <p><strong>Status:</strong> 
      <span class="status-badge <?= htmlspecialchars($order['status']) ?>">
        <?= htmlspecialchars($order['status']) ?>
      </span>
    </p>
    <p><strong>Placed On:</strong> <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></p>
    <p><strong>Total Amount:</strong> ₹<?= number_format($order['total_amount'], 2) ?></p>
  </div>

  <?php if ($items && count($items) > 0): ?>
    <h3>Ordered Products</h3>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Quantity</th>
          <th>Price (₹)</th>
          <th>Subtotal (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td><?= htmlspecialchars($item['quantity'] ?? 1) ?></td>
            <td><?= number_format($item['price'], 2) ?></td>
            <td><?= number_format(($item['quantity'] ?? 1) * $item['price'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color:#777;">No product details available for this order.</p>
  <?php endif; ?>

  <div class="total"><strong>Total:</strong> ₹<?= number_format($order['total_amount'], 2) ?></div>
</div>

</body>
</html>
