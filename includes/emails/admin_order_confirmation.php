<?php /** @var array $order */ ?>
<div style="font-family:'Poppins',Arial,sans-serif;background:#0b0b0b;color:#fff;padding:30px;">
  <div style="text-align:center;margin-bottom:30px;">
    <h1 style="color:#00bcd4;margin:0;font-size:2rem;">📧 New Order Confirmation</h1>
    <p style="color:#4caf50;font-size:1.2rem;margin:10px 0 0 0;font-weight:600;">Order #<?= esc($orderNumber ?? ($order['order_number'] ?? 'N/A')); ?></p>
  </div>
  
  <p>Hello Admin,</p>
  <p>A new order has been confirmed and the customer has been notified. Here are the complete order details:</p>
  
  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #00bcd4;">
    <h3 style="color:#00bcd4;margin-top:0;">Customer Information</h3>
    <p><strong>Name:</strong> <?= esc($userName ?? $order['name'] ?? 'N/A'); ?></p>
    <p><strong>Email:</strong> <a href="mailto:<?= esc($userEmail ?? $order['email'] ?? ''); ?>" style="color:#00bcd4;"><?= esc($userEmail ?? $order['email'] ?? 'N/A'); ?></a></p>
    <p><strong>Phone:</strong> <a href="tel:<?= esc($order['shipping_phone'] ?? $order['phone'] ?? ''); ?>" style="color:#00bcd4;"><?= esc($order['shipping_phone'] ?? $order['phone'] ?? 'N/A'); ?></a></p>
    <p><strong>Shipping Address:</strong><br><?= nl2br(esc($order['shipping_address'] ?? $order['address'] ?? 'N/A')); ?></p>
  </div>

  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:20px 0;">
    <h3 style="color:#00bcd4;margin-top:0;">Order Summary</h3>
    <p><strong>Order Number:</strong> <?= esc($orderNumber ?? ($order['order_number'] ?? 'N/A')); ?></p>
    <p><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'] ?? 'now')); ?></p>
    <p><strong>Payment Method:</strong> <span style="color:#ffb347;"><?= esc($order['payment_method'] ?? 'COD'); ?></span></p>
    <p><strong>Order Status:</strong> <span style="color:#ffb347;"><?= esc(ucfirst($order['status'] ?? 'pending')); ?></span></p>
    <?php if (isset($order['coupon_code']) && !empty($order['coupon_code'])): ?>
      <p><strong>Coupon Used:</strong> <?= esc($order['coupon_code']); ?></p>
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
  
  <div style="background:#1a1a1a;padding:20px;border-radius:8px;margin:20px 0;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <span><strong>Subtotal:</strong></span>
      <span>₹<?= number_format($order['subtotal'] ?? $order['total'] ?? 0, 2); ?></span>
    </div>
    <?php if (isset($order['discount_total']) && (float)$order['discount_total'] > 0): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;color:#4caf50;">
        <span><strong>Discount:</strong></span>
        <span>-₹<?= number_format($order['discount_total'], 2); ?></span>
      </div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:15px;padding-top:15px;border-top:2px solid #333;font-size:1.2rem;">
      <span style="font-weight:700;color:#4caf50;"><strong>Total Amount:</strong></span>
      <span style="font-weight:700;color:#4caf50;">₹<?= number_format($order['total_amount'] ?? $order['total'] ?? 0, 2); ?></span>
    </div>
  </div>
  
  <div style="background:#2a2a2a;padding:20px;border-radius:8px;margin:30px 0;text-align:center;">
    <p style="margin:0;color:#aaa;">This is a confirmation notification. A detailed action-required email has also been sent.</p>
    <p style="margin:10px 0 0 0;color:#777;font-size:0.9rem;">The customer has been notified via email about their order confirmation.</p>
  </div>
</div>

