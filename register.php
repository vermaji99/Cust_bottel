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

                    // Create email verification token
                    $token = create_email_verification_token($userId);
                    db()->commit();

                    // Try to send verification email silently
                    try {
                        send_verification_email(
                            ['id' => $userId, 'name' => $name, 'email' => $email],
                            $token
                        );
                    } catch (Throwable $mailEx) {
                        error_log('Verification email failed: ' . $mailEx->getMessage());
                    }

                    // 🔥 AUTO-REDIRECT TO VERIFICATION PAGE
                    header("Location: verify-email.php?token=" . urlencode($token));
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
<title>Bottel | Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
  font-family: "Poppins", sans-serif;
  background: radial-gradient(circle at top, #0d0d0d, #000);
  color: #eee;
  margin: 0;
  display: flex; justify-content: center; align-items: center;
  height: 100vh;
}
.container {
  background: rgba(20, 20, 20, 0.95);
  padding: 40px 50px;
  border-radius: 16px;
  box-shadow: 0 0 25px rgba(0,188,212,0.2);
  width: 400px;
  text-align: center;
}
h2 {
  color: #00bcd4;
  margin-bottom: 25px;
}
input {
  width: 100%;
  padding: 12px 15px;
  margin: 10px 0;
  border-radius: 8px;
  border: none;
  background: #1a1a1a;
  color: #fff;
}
input:focus {
  outline: 2px solid #00bcd4;
}
button {
  width: 100%;
  background: linear-gradient(45deg, #00bcd4, #007bff);
  color: white;
  padding: 12px;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: transform 0.3s;
}
button:hover {
  transform: scale(1.05);
}
.msg {
  margin-top: 15px;
  color: #ff5c5c;
}
a {
  color: #00bcd4;
  text-decoration: none;
}
</style>
</head>
<body>
<div class="container">
  <h2>Create Account</h2>
  <form method="POST">
    <?= csrf_field(); ?>
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="password" name="confirm" placeholder="Confirm Password" required>
    <button type="submit">Register</button>
  </form>

  <?php if ($message): ?>
    <p class="msg"><?= esc($message) ?></p>
  <?php endif; ?>

  <p style="margin-top:15px;">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
