<?php
require __DIR__ . '/includes/bootstrap.php';
$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Security token expired.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $message = 'Enter a valid email.';
        } else {
            $stmt = db()->prepare('SELECT id, name, email, email_verified_at FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $message = 'No account found for that email.';
            } elseif (!empty($user['email_verified_at'])) {
                $message = 'Email already verified.';
            } else {
                // Generate and send OTP
                $otp = create_and_send_otp((int) $user['id'], $email, 'email_verification');
                send_otp_email($user, $otp, 'email_verification');
                
                $_SESSION['otp_verification_email'] = $email;
                $success = 'OTP has been resent to your email.';
                
                // Redirect to OTP verification page
                header('Location: verify-otp.php?email=' . urlencode($email) . '&purpose=email_verification');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resend Verification | Bottle</title>
  <style>
    body{margin:0;font-family:Poppins,Arial,sans-serif;background:#050505;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;}
    .card{background:#101010;padding:40px;border-radius:16px;max-width:420px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.5);}
    input{width:100%;padding:12px;border-radius:10px;border:none;background:#1c1c1c;color:#fff;margin:12px 0;}
    button{width:100%;padding:12px;border:none;border-radius:30px;background:#00bcd4;color:#000;font-weight:600;cursor:pointer;}
    p{color:#bbb;}
  </style>
</head>
<body>
  <div class="card">
    <h1>Resend OTP</h1>
    <form method="POST">
      <?= csrf_field(); ?>
      <input type="email" name="email" placeholder="Email address" required>
      <button type="submit">Resend OTP</button>
    </form>
    <?php if ($message): ?><p style="color:#ff6b6b;"><?= esc($message); ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color:#4ade80;"><?= esc($success); ?></p><?php endif; ?>
  </div>
</body>
</html>



