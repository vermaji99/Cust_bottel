<?php /** @var array $user */ /** @var string $otp */ /** @var string $purpose */ ?>
<div style="font-family: 'Poppins', Arial, sans-serif; background:#0b0b0b; color:#fff; padding:30px;">
  <h2 style="color:#00bcd4;">
    <?php if ($purpose === 'login'): ?>
      Your Login OTP
    <?php else: ?>
      Verify your Bottle account
    <?php endif; ?>
  </h2>
  <p>Hi <?= esc($user['name'] ?? 'there'); ?>,</p>
  
  <?php if ($purpose === 'login'): ?>
    <p>Use this OTP to complete your login:</p>
  <?php else: ?>
    <p>Thanks for signing up. Please use this OTP to verify your email address:</p>
  <?php endif; ?>
  
  <div style="background:#1a1a1a; padding:20px; border-radius:12px; text-align:center; margin:30px 0;">
    <div style="font-size:2.5rem; font-weight:700; color:#00bcd4; letter-spacing:8px; font-family:'Courier New', monospace;">
      <?= esc($otp); ?>
    </div>
  </div>
  
  <p style="color:#aaa; font-size:0.9rem;">
    This OTP is valid for 10 minutes. Do not share this code with anyone.
  </p>
  
  <?php if ($purpose !== 'login'): ?>
    <p style="margin-top:20px;">
      <a href="<?= esc(app_config('app_url')); ?>/verify-otp.php" style="background:#00bcd4;color:#000;padding:14px 28px;border-radius:30px;text-decoration:none;font-weight:600;display:inline-block;">
        Verify Email
      </a>
    </p>
  <?php endif; ?>
  
  <p style="margin-top:40px;color:#aaa;">— Bottle Security Team</p>
</div>

