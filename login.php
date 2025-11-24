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
        record_login_attempt($email, true);
        login_user($user);
        header("Location: user/dashboard.php");
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
  <title>User Login | Bottel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: radial-gradient(circle at top, #101010, #000);
      color: #f0f0f0;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }

    .login-container {
      background: rgba(20,20,20,0.9);
      padding: 40px 50px;
      border-radius: 16px;
      box-shadow: 0 0 25px rgba(0,188,212,0.3);
      width: 380px;
      text-align: center;
    }

    .login-container h2 {
      color: #00bcd4;
      margin-bottom: 25px;
      font-size: 1.8rem;
      letter-spacing: 1px;
    }

    .input-group {
      margin-bottom: 20px;
      text-align: left;
    }

    .input-group label {
      display: block;
      font-size: 0.9rem;
      color: #aaa;
      margin-bottom: 6px;
    }

    .input-group input {
      width: 100%;
      padding: 10px 15px;
      border: none;
      border-radius: 8px;
      background: #1a1a1a;
      color: #fff;
      font-size: 1rem;
    }

    .input-group input:focus {
      outline: 2px solid #00bcd4;
    }

    .btn {
      background: linear-gradient(45deg, #00bcd4, #007bff);
      border: none;
      padding: 12px 20px;
      border-radius: 30px;
      color: #fff;
      font-size: 1rem;
      cursor: pointer;
      transition: transform 0.3s;
      width: 100%;
    }

    .btn:hover {
      transform: scale(1.05);
    }

    .error {
      background: rgba(255,0,0,0.1);
      color: #ff6b6b;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 0.9rem;
    }

    .footer-link {
      margin-top: 20px;
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
    <h2><i class="fas fa-user-circle"></i> User Login</h2>

    <?php if ($notice): ?>
      <div class="error" style="background:rgba(0,188,212,0.1);color:#1de9b6"><?= esc($notice) ?></div>
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

      <div style="text-align:right;margin-bottom:10px;">
        <a href="forgot-password.php" style="color:#00bcd4;font-size:0.9rem;">Forgot password?</a>
      </div>

      <button type="submit" class="btn">Login</button>
    </form>

    <div class="footer-link">
      Don’t have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</body>
</html>
