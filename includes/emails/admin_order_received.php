<?php /** @var array $order */ ?>
<div style="font-family:'Poppins',Arial,sans-serif;background:#0b0b0b;color:#fff;padding:30px;">
  <h2 style="color:#ffb347;border-bottom:2px solid #ffb347;padding-bottom:10px;">🚨 New Order Received - Action Required</h2>
  <p style="font-size:1.1rem;color:#ffb347;font-weight:600;">Order #<?= esc($orderNumber ?? ($order['order_number'] ?? 'N/A')); ?> needs your attention!</p>
  
  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #ffb347;">
    <h3 style="color:#00bcd4;margin-top:0;">Customer Information</h3>
    <p><strong>Name:</strong> <?= esc($userName ?? $order['name'] ?? 'N/A'); ?></p>
    <p><strong>Email:</strong> <?= esc($userEmail ?? $order['email'] ?? 'N/A'); ?></p>
    <p><strong>Phone:</strong> <?= esc($userPhone ?? $order['shipping_phone'] ?? $order['phone'] ?? 'N/A'); ?></p>
    <p><strong>Shipping Address:</strong><br><?= nl2br(esc($userAddress ?? $order['shipping_address'] ?? $order['address'] ?? 'N/A')); ?></p>
  </div>

  <h3 style="color:#00bcd4;margin-top:30px;">Order Details</h3>
  <table style="width:100%;margin:20px 0;border-collapse:collapse;">
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
  
  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:20px 0;">
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
    <p><strong>Subtotal:</strong> ₹<?= number_format($finalSubtotal, 2); ?></p>
    <?php if ($discountTotal > 0): ?>
      <p><strong>Discount Applied:</strong> -₹<?= number_format($discountTotal, 2); ?></p>
    <?php endif; ?>
    <p style="font-size:1.3rem;color:#4caf50;margin-top:15px;padding-top:15px;border-top:2px solid #333;"><strong>Total Payable Amount:</strong> ₹<?= number_format($finalTotal, 2); ?></p>
  </div>

  <div style="background:#2a2a2a;padding:20px;border-radius:8px;margin:20px 0;">
    <h3 style="color:#00bcd4;margin-top:0;">Payment & Status</h3>
    <p><strong>Payment Method:</strong> <span style="color:#ffb347;"><?= esc($order['payment_method'] ?? 'COD'); ?></span></p>
    <p><strong>Current Status:</strong> <span style="color:#ffb347;"><?= esc(ucfirst($order['status'] ?? 'pending')); ?></span></p>
    <p><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'] ?? 'now')); ?></p>
    <?php if (isset($order['coupon_code']) && !empty($order['coupon_code'])): ?>
      <p><strong>Coupon Used:</strong> <?= esc($order['coupon_code']); ?></p>
    <?php endif; ?>
  </div>

  <div style="background:#1a3a1a;padding:20px;border-radius:8px;margin:30px 0;border-left:4px solid #4caf50;">
    <p style="margin:0;font-weight:600;color:#4caf50;">📋 Next Steps:</p>
    <ul style="margin:10px 0 0 20px;color:#ccc;">
      <li>Review the order details</li>
      <li>Update order status in admin panel</li>
      <li>Process the order for production</li>
      <li>Contact customer if needed</li>
    </ul>
  </div>

  <div style="text-align:center;margin:30px 0;">
    <a href="<?= esc(app_config('app_url')); ?>/admin/orders.php" style="background:#00bcd4;color:#000;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;font-size:1.1rem;">
      View Order in Admin Panel
    </a>
  </div>
  
  <p style="margin-top:30px;color:#aaa;font-size:0.9rem;text-align:center;">Please log in to the admin panel to manage this order and update its status.</p>
</div>

