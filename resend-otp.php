<?php
require __DIR__ . '/includes/bootstrap.php';

$email = $_GET['email'] ?? '';
$purpose = $_GET['purpose'] ?? 'email_verification';
$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Security token expired.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $purpose = $_POST['purpose'] ?? 'email_verification';
        
        if (!$email) {
            $message = 'Enter a valid email.';
        } else {
            // Find user by email
            $stmt = db()->prepare('SELECT id, name, email, email_verified_at FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $message = 'No account found for that email.';
            } elseif ($purpose === 'email_verification' && !empty($user['email_verified_at'])) {
                $message = 'Email already verified.';
            } else {
                // Generate and send OTP
                $otp = create_and_send_otp((int) $user['id'], $email, $purpose);
                
                // Store OTP in session as backup
                $_SESSION['otp_verification_email'] = $email;
                if ($purpose === 'login') {
                    $_SESSION['login_otp'] = $otp;
                    $_SESSION['login_otp_time'] = time();
                } else {
                    $_SESSION['registration_otp'] = $otp;
                    $_SESSION['registration_otp_time'] = time();
                }
                
                // Try to send OTP email
                $emailSent = false;
                try {
                    $emailSent = send_otp_email($user, $otp, $purpose);
                } catch (Throwable $mailEx) {
                    error_log('Resend OTP email failed: ' . $mailEx->getMessage());
                }
                
                $redirectUrl = 'verify-otp.php?email=' . urlencode($email) . '&purpose=' . urlencode($purpose);
                if (!$emailSent) {
                    $redirectUrl .= '&email_failed=1';
                }
                
                // Redirect to verify OTP page
                header('Location: ' . $redirectUrl);
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
  <title>Resend OTP | Bottle</title>
  <style>
    body{margin:0;font-family:Poppins,Arial,sans-serif;background:#050505;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;padding:20px;}
    .card{background:#101010;padding:40px;border-radius:16px;max-width:420px;width:100%;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.5);}
    input{width:100%;padding:12px;border-radius:10px;border:2px solid #333;background:#1c1c1c;color:#fff;margin:12px 0;font-size:1rem;}
    input:focus{outline:none;border-color:#00bcd4;}
    button{width:100%;padding:12px;border:none;border-radius:30px;background:#00bcd4;color:#000;font-weight:600;cursor:pointer;margin-top:10px;}
    p{color:#bbb;}
    .error{background:rgba(255,0,0,0.1);color:#ff6b6b;padding:12px;border-radius:8px;margin-bottom:15px;}
    .success{background:rgba(0,188,212,0.1);color:#1de9b6;padding:12px;border-radius:8px;margin-bottom:15px;}
  </style>
</head>
<body>
  <div class="card">
    <h1 style="color:#00bcd4;">Resend OTP</h1>
    
    <?php if ($message): ?>
      <div class="error"><?= esc($message) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?= esc($success) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field(); ?>
      <input type="hidden" name="purpose" value="<?= esc($purpose) ?>">
      <input type="email" name="email" placeholder="Enter your email" value="<?= esc($email) ?>" required>
      <button type="submit">Resend OTP</button>
    </form>
    
    <p style="margin-top:20px;">
      <a href="verify-otp.php?email=<?= urlencode($email) ?>&purpose=<?= urlencode($purpose) ?>" style="color:#00bcd4;">Back to Verify OTP</a>
    </p>
  </div>
</body>
</html>

