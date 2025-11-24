<?php /** @var array $order */ ?>
<div style="font-family:'Poppins',Arial,sans-serif;background:#0b0b0b;color:#fff;padding:30px;">
  <h2 style="color:#00bcd4;">Order #<?= esc($order['order_number']); ?> confirmed</h2>
  <p>Hi <?= esc($order['name']); ?>,</p>
  <p>Thanks for ordering with Bottel. We’re already prepping your custom bottles. Here’s a quick summary:</p>
  <table style="width:100%;margin:20px 0;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="text-align:left;padding:8px;border-bottom:1px solid #222;">Item</th>
        <th style="text-align:right;padding:8px;border-bottom:1px solid #222;">Qty</th>
        <th style="text-align:right;padding:8px;border-bottom:1px solid #222;">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td style="padding:8px;border-bottom:1px solid #181818;"><?= esc($item['product_name']); ?></td>
          <td style="padding:8px;text-align:right;border-bottom:1px solid #181818;"><?= (int) $item['quantity']; ?></td>
          <td style="padding:8px;text-align:right;border-bottom:1px solid #181818;">₹<?= number_format($item['total'], 2); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p><strong>Total paid:</strong> ₹<?= number_format($order['grand_total'], 2); ?></p>
  <p>We’ll notify you as your order progresses through production and shipping.</p>
  <p style="margin-top:40px;color:#aaa;">Need help? Reply to this email anytime.</p>
</div>





