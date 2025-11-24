<?php /** @var array $user */ ?>
<div style="font-family: 'Poppins', Arial, sans-serif; background:#0b0b0b; color:#fff; padding:30px;">
  <h2 style="color:#00bcd4;">Verify your Bottel account</h2>
  <p>Hi <?= esc($user['name'] ?? 'there'); ?>,</p>
  <p>Thanks for signing up. Please confirm your email address so we can keep your account secure.</p>
  <p style="margin:30px 0;">
    <a href="<?= esc($verificationLink); ?>" style="background:#00bcd4;color:#000;padding:14px 28px;border-radius:30px;text-decoration:none;font-weight:600;">
      Verify Email
    </a>
  </p>
  <p>This link expires in 24 hours. If you didn’t create an account, you can safely ignore this message.</p>
  <p style="margin-top:40px;color:#aaa;">— Bottel Security Team</p>
</div>





