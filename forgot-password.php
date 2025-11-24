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
            $stmt = db()->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $token = issue_password_reset_token((int) $user['id'], $user['email']);
                send_password_reset_email($user, $token);
            }
            $success = 'If that email exists, a reset link is on its way.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Bottel</title>
  <style>
    body{margin:0;font-family:Poppins,Arial,sans-serif;background:#050505;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;}
    .card{background:#101010;padding:40px;border-radius:16px;max-width:420px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.5);}
    input{width:100%;padding:12px;border-radius:10px;border:none;background:#1c1c1c;color:#fff;margin:12px 0;}
    button{width:100%;padding:12px;border:none;border-radius:30px;background:#00bcd4;color:#000;font-weight:600;cursor:pointer;}
    a{color:#00bcd4;text-decoration:none;}
  </style>
</head>
<body>
  <div class="card">
    <h1>Reset password</h1>
    <form method="POST">
      <?= csrf_field(); ?>
      <input type="email" name="email" placeholder="Email address" required>
      <button type="submit">Send reset link</button>
    </form>
    <?php if ($message): ?><p style="color:#ff6b6b;"><?= esc($message); ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color:#4ade80;"><?= esc($success); ?></p><?php endif; ?>
    <p style="margin-top:20px;"><a href="login.php">Back to login</a></p>
  </div>
</body>
</html>





