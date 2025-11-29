<?php
require __DIR__ . '/includes/bootstrap.php';

$message = '';
$success = '';
$manualVerificationUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $message = "Security token expired. Please refresh and try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if ($name === '' || !$email || $password === '') {
            $message = "All fields are required!";
        } elseif ($password !== $confirm) {
            $message = "Passwords do not match!";
        } elseif (strlen($password) < 8) {
            $message = "Password must be at least 8 characters.";
        } else {
            $check = db()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $check->execute([$email]);

            if ($check->fetch()) {
                $message = "Email already registered!";
            } else {
                try {
                    db()->beginTransaction();
                    $hash = hash_password($password);
                    $stmt = db()->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $hash]);
                    $userId = (int) db()->lastInsertId();

                    // Create and send OTP for email verification
                    $otp = create_and_send_otp($userId, $email, 'email_verification');
                    db()->commit();

                    // Store email and OTP in session for OTP verification (in case email fails)
                    $_SESSION['otp_verification_email'] = $email;
                    $_SESSION['registration_otp'] = $otp; // Store OTP in session as backup
                    $_SESSION['registration_otp_time'] = time();

                    // Try to send OTP email (but don't fail if email fails)
                    $emailSent = false;
                    try {
                        $emailSent = send_otp_email(
                            ['id' => $userId, 'name' => $name, 'email' => $email],
                            $otp,
                            'email_verification'
                        );
                    } catch (Throwable $mailEx) {
                        error_log('OTP email failed: ' . $mailEx->getMessage());
                        // Continue even if email fails - OTP is stored in DB and session
                    }

                    // Redirect to OTP verification page (with email status)
                    $redirectUrl = "verify-otp.php?email=" . urlencode($email) . "&purpose=email_verification";
                    if (!$emailSent) {
                        $redirectUrl .= "&email_failed=1";
                    }
                    header("Location: " . $redirectUrl);
                    exit;

                } catch (Throwable $e) {
                    if (db()->inTransaction()) {
                        db()->rollBack();
                    }
                    error_log('Registration failed: ' . $e->getMessage());
                    $message = "Something went wrong. Try again.";
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
<title>Bottle | Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {
  box-sizing: border-box;
}

body {
  font-family: "Poppins", sans-serif;
  background: radial-gradient(circle at 50% 0%, #1a1f25 0%, #0b0b0b 60%);
  color: #e0e0e0;
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
}

.container {
  background: rgba(22, 22, 22, 0.95);
  border: 1px solid #252525;
  border-radius: 20px;
  padding: 30px;
  width: 90%;
  max-width: 380px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  position: relative;
}

.toggle-buttons {
  display: flex;
  gap: 0;
  margin-bottom: 25px;
  border-radius: 50px;
  overflow: hidden;
  background: #1a1a1a;
  border: 1px solid #252525;
  padding: 4px;
}

.toggle-btn {
  flex: 1;
  padding: 10px 20px;
  border: none;
  background: transparent;
  color: #aaa;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  text-align: center;
  border-radius: 50px;
}

.toggle-btn:first-child {
  border-radius: 50px 0 0 50px;
}

.toggle-btn:last-child {
  border-radius: 0 50px 50px 0;
}

.toggle-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #00bcd4;
}

.toggle-btn.active {
  background: linear-gradient(135deg, #00bcd4, #007bff);
  color: #fff;
  box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
}

.input-group {
  margin-bottom: 15px;
  text-align: left;
}

.input-group label {
  display: block;
  font-size: 0.9rem;
  color: #aaa;
  margin-bottom: 8px;
}

.input-group input {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid #252525;
  border-radius: 12px;
  background: #1a1a1a;
  color: #fff;
  font-size: 1rem;
  font-family: inherit;
  transition: all 0.3s;
  box-sizing: border-box;
}

.input-group input:focus {
  outline: none;
  border-color: #00bcd4;
  box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
}

button {
  width: 100%;
  background: linear-gradient(135deg, #00bcd4, #007bff);
  border: none;
  padding: 14px 32px;
  border-radius: 50px;
  color: #fff;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
}

button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
}

.msg {
  background: rgba(220, 53, 69, 0.1);
  color: #dc3545;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 15px;
  font-size: 0.9rem;
  border: 1px solid #dc3545;
}

.footer-link {
  margin-top: 20px;
  text-align: center;
  font-size: 0.9rem;
  color: #aaa;
}

.footer-link a {
  color: #00bcd4;
  text-decoration: none;
}

.footer-link a:hover {
  text-decoration: underline;
}
</style>
</head>
<body>
<div class="container">
  <div class="toggle-buttons">
    <button type="button" class="toggle-btn" onclick="window.location.href='login.php'">Login</button>
    <button type="button" class="toggle-btn active">Register</button>
  </div>

  <?php if ($message): ?>
    <div class="msg"><?= esc($message) ?></div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field(); ?>
    <div class="input-group">
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" placeholder="Enter your full name" required>
    </div>
    <div class="input-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="Enter your email" required>
    </div>
    <div class="input-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter password (min 8 characters)" required minlength="8">
    </div>
    <div class="input-group">
      <label for="confirm">Confirm Password</label>
      <input type="password" id="confirm" name="confirm" placeholder="Confirm your password" required minlength="8">
    </div>
    <button type="submit">Register</button>
  </form>

  <div class="footer-link">
    Already have an account? <a href="login.php">Login here</a>
  </div>
</div>
</body>
</html>
