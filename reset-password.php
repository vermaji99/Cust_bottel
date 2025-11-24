<?php
require __DIR__ . '/includes/bootstrap.php';

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$payload = $token ? validate_password_reset_token($token) : null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$payload) {
        $error = 'Invalid or expired reset link.';
    } elseif (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if ($password === '' || $password !== $confirm) {
            $error = 'Passwords must match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $stmt = db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([hash_password($password), $payload['sub']]);
            mark_password_token_consumed($token);
            header('Location: login.php?reset=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Choose New Password | Bottel</title>
  <style>
    body{margin:0;font-family:Poppins,Arial,sans-serif;background:#050505;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;}
    .card{background:#101010;padding:40px;border-radius:16px;max-width:420px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.5);}
    input{width:100%;padding:12px;border-radius:10px;border:none;background:#1c1c1c;color:#fff;margin:12px 0;}
    button{width:100%;padding:12px;border:none;border-radius:30px;background:#00bcd4;color:#000;font-weight:600;cursor:pointer;}
  </style>
</head>
<body>
  <div class="card">
    <h1>Set a new password</h1>
    <?php if (!$payload): ?>
      <p>Reset link expired or invalid.</p>
    <?php else: ?>
      <form method="POST">
        <?= csrf_field(); ?>
        <input type="hidden" name="token" value="<?= esc($token); ?>">
        <input type="password" name="password" placeholder="New password" required>
        <input type="password" name="confirm" placeholder="Confirm password" required>
        <button type="submit">Update password</button>
      </form>
    <?php endif; ?>
    <?php if ($error): ?><p style="color:#ff6b6b;"><?= esc($error); ?></p><?php endif; ?>
  </div>
</body>
</html>



