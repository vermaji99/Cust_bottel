<?php /** @var array $design */ ?>
<div style="font-family:'Poppins',Arial,sans-serif;background:#0b0b0b;color:#fff;padding:30px;">
  <h2 style="color:#00bcd4;">Design saved successfully</h2>
  <p>Hi <?= esc($user['name']); ?>,</p>
  <p>Your custom bottle design <strong>#<?= esc($design['design_key']); ?></strong> is safe in your dashboard.</p>
  <?php if (!empty($design['thumbnail_url'])): ?>
    <p style="margin:20px 0;">
      <img src="<?= esc($design['thumbnail_url']); ?>" alt="Design thumbnail" style="border-radius:12px;width:220px;">
    </p>
  <?php endif; ?>
  <p>You can re-open it anytime from “My Designs” or attach it to a new order.</p>
  <p style="margin-top:40px;color:#aaa;">Cheers,<br>The Bottle studio</p>
</div>





