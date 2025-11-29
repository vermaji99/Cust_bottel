<?php /** @var array $order */ ?>
<div style="font-family:'Poppins',Arial,sans-serif;background:#0b0b0b;color:#fff;padding:30px;">
  <div style="text-align:center;margin-bottom:30px;">
    <h1 style="color:#00bcd4;margin:0;font-size:2rem;">✅ Order Confirmed!</h1>
    <p style="color:#4caf50;font-size:1.2rem;margin:10px 0 0 0;font-weight:600;">Order #<?= esc($order['order_number'] ?? 'ORD-' . ($order['id'] ?? 'N/A')); ?></p>
  </div>
  
  <p style="font-size:1.1rem;">Hi <?= esc($order['shipping_name'] ?? $order['name'] ?? 'Customer'); ?>,</p>
  <p>Thank you for your order! We've received your order and our team has started preparing your custom bottles. Here are your order details:</p>
  
  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #00bcd4;">
    <h3 style="color:#00bcd4;margin-top:0;">Order Information</h3>
    <p><strong>Order Number:</strong> <?= esc($order['order_number'] ?? 'ORD-' . ($order['id'] ?? 'N/A')); ?></p>
    <p><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'] ?? 'now')); ?></p>
    <p><strong>Payment Method:</strong> <?= esc($order['payment_method'] ?? 'COD'); ?></p>
    <p><strong>Order Status:</strong> <span style="color:#4caf50;"><?= esc(ucfirst($order['status'] ?? 'Pending')); ?></span></p>
  </div>

  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:20px 0;">
    <h3 style="color:#00bcd4;margin-top:0;">Shipping Address</h3>
    <p style="margin:5px 0;"><?= esc($order['shipping_name'] ?? $order['name'] ?? ''); ?></p>
    <p style="margin:5px 0;"><?= nl2br(esc($order['shipping_address'] ?? $order['address'] ?? '')); ?></p>
    <?php if (!empty($order['shipping_phone'] ?? $order['phone'] ?? '')): ?>
      <p style="margin:5px 0;"><strong>Phone:</strong> <?= esc($order['shipping_phone'] ?? $order['phone'] ?? ''); ?></p>
    <?php endif; ?>
  </div>

  <h3 style="color:#00bcd4;margin-top:30px;">Order Items</h3>
  <table style="width:100%;margin:20px 0;border-collapse:collapse;background:#1a1a1a;">
    <thead>
      <tr style="background:#222;">
        <th style="text-align:left;padding:12px;border-bottom:2px solid #333;">Item</th>
        <th style="text-align:right;padding:12px;border-bottom:2px solid #333;">Qty</th>
        <th style="text-align:right;padding:12px;border-bottom:2px solid #333;">Unit Price</th>
        <th style="text-align:right;padding:12px;border-bottom:2px solid #333;">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td style="padding:12px;border-bottom:1px solid #181818;"><?= esc($item['product_name']); ?></td>
          <td style="padding:12px;text-align:right;border-bottom:1px solid #181818;"><?= (int) $item['quantity']; ?></td>
          <td style="padding:12px;text-align:right;border-bottom:1px solid #181818;">₹<?= number_format($item['unit_price'] ?? $item['price'] ?? 0, 2); ?></td>
          <td style="padding:12px;text-align:right;border-bottom:1px solid #181818;font-weight:600;">₹<?= number_format($item['quantity'] * ($item['unit_price'] ?? $item['price'] ?? 0), 2); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  
  <div style="background:#1a3a1a;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #4caf50;">
    <?php
      // Calculate totals from items if needed
      $calculatedSubtotal = 0;
      foreach ($items as $item) {
        $itemPrice = (float)($item['unit_price'] ?? $item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 1);
        $calculatedSubtotal += $itemPrice * $quantity;
      }
      $discountTotal = (float)($order['discount_total'] ?? 0);
      $finalSubtotal = (float)($order['subtotal'] ?? $calculatedSubtotal);
      if ($finalSubtotal == 0) {
        $finalSubtotal = $calculatedSubtotal;
      }
      $finalTotal = (float)($order['total_amount'] ?? $order['total'] ?? max(0, $finalSubtotal - $discountTotal));
      if ($finalTotal == 0) {
        $finalTotal = max(0, $finalSubtotal - $discountTotal);
      }
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <span><strong>Subtotal:</strong></span>
      <span>₹<?= number_format($finalSubtotal, 2); ?></span>
    </div>
    <?php if ($discountTotal > 0): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;color:#4caf50;">
        <span><strong>Discount:</strong></span>
        <span>-₹<?= number_format($discountTotal, 2); ?></span>
      </div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:15px;padding-top:15px;border-top:2px solid #333;font-size:1.3rem;">
      <span style="font-weight:700;color:#4caf50;"><strong>Total Payable Amount:</strong></span>
      <span style="font-weight:700;color:#4caf50;">₹<?= number_format($finalTotal, 2); ?></span>
    </div>
  </div>

  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:30px 0;">
    <h3 style="color:#00bcd4;margin-top:0;">What's Next?</h3>
    <p style="margin:10px 0;">We'll keep you updated on your order status. You'll receive notifications when:</p>
    <ul style="margin:10px 0 0 20px;color:#ccc;">
      <li>Your order is being processed</li>
      <li>Your custom bottles are being printed</li>
      <li>Your order is shipped</li>
      <li>Your order is delivered</li>
    </ul>
  </div>

  <div style="text-align:center;margin:30px 0;">
    <a href="<?= esc($trackOrderUrl ?? app_config('app_url') . '/user/orders.php'); ?>" style="background:#00bcd4;color:#000;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;font-size:1.1rem;">
      📦 Track Your Order
    </a>
  </div>
  
  <p style="margin-top:40px;color:#aaa;text-align:center;">Need help? Reply to this email anytime or contact our support team.</p>
  <p style="color:#777;text-align:center;font-size:0.9rem;margin-top:20px;">© <?= date('Y'); ?> Bottle. All Rights Reserved.</p>
</div>





