<?php
require __DIR__ . '/includes/bootstrap.php';

$error = '';
$success = '';
$email = $_GET['email'] ?? '';
$emailFailed = isset($_GET['email_failed']) && $_GET['email_failed'] == '1';
$showOtpInPage = false;
$displayOtp = '';

// If no email in GET, check session
if (!$email && isset($_SESSION['otp_verification_email'])) {
    $email = $_SESSION['otp_verification_email'];
}

// If email failed, show OTP from session
if ($emailFailed || (isset($_SESSION['registration_otp']) && (time() - ($_SESSION['registration_otp_time'] ?? 0)) < 600)) {
    $purpose = $_GET['purpose'] ?? 'email_verification';
    if ($purpose === 'login' && isset($_SESSION['login_otp'])) {
        $displayOtp = $_SESSION['login_otp'];
        $showOtpInPage = true;
    } elseif ($purpose === 'email_verification' && isset($_SESSION['registration_otp'])) {
        $displayOtp = $_SESSION['registration_otp'];
        $showOtpInPage = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired. Please refresh.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $otp = trim($_POST['otp'] ?? '');
        $purpose = $_POST['purpose'] ?? 'email_verification';

        if (!$email || !$otp || strlen($otp) !== 6) {
            $error = 'Please enter a valid 6-digit OTP.';
        } else {
            if ($purpose === 'email_verification') {
                // Verify email during registration
                $verified = verify_and_activate_user_otp($email, $otp);
                
                // Also check session OTP as fallback (if email failed)
                if (!$verified && isset($_SESSION['registration_otp']) && $_SESSION['registration_otp'] === $otp) {
                    $sessionOtpTime = $_SESSION['registration_otp_time'] ?? 0;
                    if ((time() - $sessionOtpTime) < 600) { // 10 minutes
                        // Get user by email and verify
                        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                        $stmt->execute([$email]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($user) {
                            $updateStmt = db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?');
                            $updateStmt->execute([$user['id']]);
                            $verified = true;
                        }
                    }
                }
                
                if ($verified) {
                    $success = 'Email verified successfully! You can now log in.';
                    unset($_SESSION['otp_verification_email']);
                    unset($_SESSION['registration_otp']);
                    unset($_SESSION['registration_otp_time']);
                    header('Location: login.php?verified=1');
                    exit;
                } else {
                    $error = 'Invalid or expired OTP. Please try again.';
                }
            } elseif ($purpose === 'login') {
                // Verify OTP for login
                $record = verify_otp($email, $otp, 'login');
                
                // Also check session OTP as fallback (if email failed)
                $sessionOtpValid = false;
                if (!$record && isset($_SESSION['login_otp']) && $_SESSION['login_otp'] === $otp) {
                    $sessionOtpTime = $_SESSION['login_otp_time'] ?? 0;
                    if ((time() - $sessionOtpTime) < 600) { // 10 minutes
                        $sessionOtpValid = true;
                        // Get user from session
                        $userId = $_SESSION['login_user_id'] ?? null;
                        if ($userId) {
                            $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                            $stmt->execute([$userId]);
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($user && strtolower($user['email']) === strtolower($email)) {
                                $record = ['user_id' => $user['id']]; // Fake record for processing
                            }
                        }
                    }
                }
                
                if ($record || $sessionOtpValid) {
                    // Get user and log them in
                    $userId = $record['user_id'] ?? $_SESSION['login_user_id'] ?? null;
                    if (!$userId) {
                        $error = 'User not found.';
                    } else {
                        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            // Verify email is verified
                            if (empty($user['email_verified_at'])) {
                                // Auto-verify email if not verified
                                $stmt = db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?');
                                $stmt->execute([$user['id']]);
                                $user['email_verified_at'] = date('Y-m-d H:i:s');
                            }
                            
                            record_login_attempt($email, true);
                            login_user($user);
                            unset($_SESSION['otp_verification_email']);
                            unset($_SESSION['login_user_id']);
                            unset($_SESSION['login_otp']);
                            unset($_SESSION['login_otp_time']);
                            header('Location: index.php');
                            exit;
                        } else {
                            $error = 'User not found.';
                        }
                    }
                } else {
                    $error = 'Invalid or expired OTP. Please try again.';
                }
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
  <title>Verify OTP | Bottle</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: "Poppins", Arial, sans-serif;
      background: #050505;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .card {
      background: #101010;
      padding: 40px;
      border-radius: 16px;
      max-width: 420px;
      width: 100%;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .card h1 {
      color: #00bcd4;
      margin-bottom: 10px;
    }
    .card p {
      color: #aaa;
      margin-bottom: 25px;
    }
    .otp-input {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin: 25px 0;
    }
    .otp-input input {
      width: 50px;
      height: 60px;
      font-size: 1.8rem;
      text-align: center;
      border: 2px solid #333;
      border-radius: 8px;
      background: #1a1a1a;
      color: #fff;
      font-weight: 700;
      font-family: 'Courier New', monospace;
    }
    .otp-input input:focus {
      outline: none;
      border-color: #00bcd4;
    }
    .error {
      background: rgba(255,0,0,0.1);
      color: #ff6b6b;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 0.9rem;
    }
    .success {
      background: rgba(0,188,212,0.1);
      color: #1de9b6;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 0.9rem;
    }
    input[type="email"] {
      width: 100%;
      padding: 12px;
      border-radius: 10px;
      border: 2px solid #333;
      background: #1c1c1c;
      color: #fff;
      margin: 12px 0;
      font-size: 1rem;
    }
    input[type="email"]:focus {
      outline: none;
      border-color: #00bcd4;
    }
    button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 30px;
      background: #00bcd4;
      color: #000;
      font-weight: 600;
      cursor: pointer;
      font-size: 1rem;
      margin-top: 15px;
      transition: transform 0.3s;
    }
    button:hover {
      transform: scale(1.02);
    }
    .resend-link {
      margin-top: 20px;
      color: #aaa;
      font-size: 0.9rem;
    }
    .resend-link a {
      color: #00bcd4;
      text-decoration: none;
    }
    .resend-link a:hover {
      text-decoration: underline;
    }
    .otp-display-box {
      background: linear-gradient(135deg, #1a1a1a, #0f1112);
      padding: 25px;
      border-radius: 12px;
      margin: 20px 0;
      border: 2px solid #00bcd4;
      box-shadow: 0 0 20px rgba(0,188,212,0.2);
    }
    .otp-display-box .otp-label {
      color: #00bcd4;
      font-weight: 600;
      margin-bottom: 10px;
      font-size: 0.9rem;
    }
    .otp-display-box .otp-code {
      font-size: 2.5rem;
      font-weight: 700;
      color: #00bcd4;
      letter-spacing: 8px;
      font-family: 'Courier New', monospace;
      margin: 10px 0;
    }
    .otp-display-box .otp-note {
      color: #aaa;
      font-size: 0.85rem;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1><i class="fas fa-shield-alt"></i> Verify OTP</h1>
    
    <?php if ($emailFailed): ?>
      <div class="error" style="background:rgba(255,193,7,0.1);color:#ffc107;border:1px solid #ffc107;">
        <i class="fas fa-exclamation-triangle"></i> Email could not be sent. Please use the OTP shown below.
      </div>
      <?php if ($showOtpInPage && $displayOtp): ?>
        <div class="otp-display-box">
          <div class="otp-label"><i class="fas fa-key"></i> Your OTP Code:</div>
          <div class="otp-code"><?= esc($displayOtp) ?></div>
          <div class="otp-note">This OTP is valid for 10 minutes. Use it below to verify.</div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <p>Enter the 6-digit OTP sent to your email</p>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="error"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?= esc($success) ?></div>
    <?php endif; ?>

    <form method="POST" id="otpForm">
      <?= csrf_field(); ?>
      <input type="hidden" name="purpose" value="<?= esc($_GET['purpose'] ?? 'email_verification') ?>">
      
      <?php if (!$email): ?>
        <input type="email" name="email" placeholder="Enter your email" required autofocus>
      <?php else: ?>
        <input type="hidden" name="email" value="<?= esc($email) ?>">
        <p style="color:#00bcd4; margin-bottom:20px;">Email: <?= esc($email) ?></p>
      <?php endif; ?>

      <div class="otp-input" id="otpContainer">
        <input type="text" name="otp" id="otpInput" maxlength="6" pattern="[0-9]{6}" required autofocus>
      </div>
      
      <button type="submit">Verify OTP</button>
    </form>

    <div class="resend-link">
      Didn't receive OTP? 
      <a href="resend-otp.php?email=<?= urlencode($email) ?>&purpose=<?= esc($_GET['purpose'] ?? 'email_verification') ?>">Resend</a>
    </div>
  </div>

  <script>
    // Auto-format OTP input
    const otpInput = document.getElementById('otpInput');
    if (otpInput) {
      otpInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
      });
      
      otpInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        this.value = pasted;
      });
    }
  </script>
</body>
</html>

