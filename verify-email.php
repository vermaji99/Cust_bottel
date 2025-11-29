<?php
require __DIR__ . '/includes/bootstrap.php';

$status = 'invalid';
if (!empty($_GET['token'])) {
    $status = verify_email_token($_GET['token']) ? 'success' : 'invalid';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Email | Bottle</title>
  <style>
    body{margin:0;font-family:Poppins,Arial,sans-serif;background:#050505;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;}
    .card{background:#101010;padding:40px;border-radius:16px;max-width:420px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.5);}
    .card h1{color:#00bcd4;}
    .card a{display:inline-block;margin-top:24px;padding:12px 24px;border-radius:30px;background:#00bcd4;color:#000;text-decoration:none;font-weight:600;}
  </style>
</head>
<body>
  <div class="card">
    <?php if ($status === 'success'): ?>
      <h1>Verified 🎉</h1>
      <p>Your email has been confirmed. You can now log in.</p>
      <a href="login.php?verified=1">Go to Login</a>
    <?php else: ?>
      <h1>Link expired</h1>
      <p>The verification link is invalid or already used.</p>
      <a href="resend-verification.php">Resend verification email</a>
    <?php endif; ?>
  </div>
</body>
</html>



