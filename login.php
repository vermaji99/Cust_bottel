<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
  header('Location: index.php');
  exit;
}

$error = "";
$notice = "";

if (isset($_GET['verified'])) {
  $notice = "Email verified successfully. Please log in.";
} elseif (isset($_GET['reset'])) {
  $notice = "Password updated. Sign in with your new password.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $error = "Security token expired. Please refresh and try again.";
  } else {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
      $error = "Email and password are required.";
    } elseif (too_many_attempts($email)) {
      $error = "Too many failed attempts. Try again in a few minutes.";
    } else {
      $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$user || !verify_password($password, $user['password'])) {
        record_login_attempt($email, false);
        $error = "Invalid email or password.";
      } elseif (empty($user['email_verified_at'])) {
        $error = "Please verify your email before logging in.";
      } else {
        // Direct login without OTP verification
        record_login_attempt($email, true);
        login_user($user);
        header("Location: index.php");
        exit();
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
  <title>User Login | Bottle</title>
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

    .login-container {
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

    .btn {
      background: linear-gradient(135deg, #00bcd4, #007bff);
      border: none;
      padding: 14px 32px;
      border-radius: 50px;
      color: #fff;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      width: 100%;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
    }

    .error {
      background: rgba(220, 53, 69, 0.1);
      color: #dc3545;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 0.9rem;
      border: 1px solid #dc3545;
    }

    .error.success {
      background: rgba(0, 188, 212, 0.1);
      color: #00bcd4;
      border: 1px solid #00bcd4;
    }

    .forgot-link {
      text-align: right;
      margin-bottom: 15px;
    }

    .forgot-link a {
      color: #00bcd4;
      font-size: 0.9rem;
      text-decoration: none;
    }

    .forgot-link a:hover {
      text-decoration: underline;
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
  <div class="login-container">
    <div class="toggle-buttons">
      <button type="button" class="toggle-btn active">Login</button>
      <button type="button" class="toggle-btn" onclick="window.location.href='register.php'">Register</button>
    </div>

    <?php if ($notice): ?>
      <div class="error success"><?= esc($notice) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="error"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field(); ?>
      <div class="input-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>

      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>

      <div class="forgot-link">
        <a href="forgot-password.php">Forgot password?</a>
      </div>

      <button type="submit" class="btn">Login</button>
    </form>

    <div class="footer-link">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</body>
</html>
