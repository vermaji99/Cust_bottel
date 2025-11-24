<?php
require __DIR__ . '/../includes/bootstrap.php';

$user = current_user();
if ($user && ($user['role'] ?? 'user') === 'admin') {
  header('Location: index.php');
  exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $message = "Security token expired.";
  } else {
    $identity = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($identity === '' || $password === '') {
      $message = "Username / email and password are required.";
    } elseif (too_many_attempts($identity)) {
      $message = "Too many attempts. Try again shortly.";
    } else {
      $stmt = db()->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND role IN ('admin','staff') LIMIT 1");
      $stmt->execute([$identity, $identity]);
      $admin = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$admin || !verify_password($password, $admin['password'])) {
        record_login_attempt($identity, false);
        $message = "⚠️ Invalid credentials";
      } else {
        record_login_attempt($identity, true);
        login_user($admin);
        header('Location: index.php');
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
  <title>Admin Login | Bottel</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    body {
      background: radial-gradient(circle at top, #0b0b0b, #000);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: "Poppins", sans-serif;
      color: #f5f5f5;
    }
    .login-box {
      background: #111;
      padding: 40px 50px;
      border-radius: 15px;
      box-shadow: 0 0 30px rgba(0,0,0,0.6);
      width: 380px;
      text-align: center;
    }
    .login-box h2 {
      color: #00bcd4;
      margin-bottom: 10px;
      font-size: 1.8rem;
    }
    .login-box p {
      color: #999;
      margin-bottom: 30px;
      font-size: 0.9rem;
    }
    .login-box input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      background: #1a1a1a;
      border: 1px solid #333;
      border-radius: 8px;
      color: #fff;
      font-size: 0.95rem;
      outline: none;
      transition: border 0.3s;
    }
    .login-box input:focus {
      border-color: #00bcd4;
    }
    .login-box button {
      width: 100%;
      padding: 12px;
      background: linear-gradient(45deg, #00bcd4, #007bff);
      border: none;
      border-radius: 8px;
      color: #fff;
      font-weight: 600;
      margin-top: 10px;
      cursor: pointer;
      transition: 0.3s;
    }
    .login-box button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0,188,212,0.4);
    }
    .error {
      color: #ff5f5f;
      margin-bottom: 10px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Welcome Back 👋</h2>
    <p>Login to manage your customized bottle business</p>

    <?php if ($message): ?>
      <div class="error"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field(); ?>
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
