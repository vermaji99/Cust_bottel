<?php /** @var array $user */ ?>
<div style="font-family:'Poppins',Arial,sans-serif;background:#0b0b0b;color:#fff;padding:30px;">
  <h2 style="color:#00bcd4;">Reset your password</h2>
  <p>Hello <?= esc($user['name'] ?? 'there'); ?>,</p>
  <p>We received a request to reset your Bottel password. Click the button below to continue. This secure link expires in 30 minutes.</p>
  <p style="margin:30px 0;">
    <a href="<?= esc($resetLink); ?>" style="background:#00bcd4;color:#000;padding:14px 28px;border-radius:30px;text-decoration:none;font-weight:600;">
      Reset Password
    </a>
  </p>
  <p>If you didn’t make this request, you can safely ignore this email.</p>
  <p style="margin-top:40px;color:#aaa;">Stay hydrated,<br>Bottel Support</p>
</div>





