<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in as admin, redirect to dashboard
$admin = admin_current_user();
if ($admin) {
    header('Location: index.php');
    exit;
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired. Please refresh.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Email and password are required.';
        } elseif (too_many_attempts($email)) {
            $error = 'Too many failed attempts. Please try again in a few minutes.';
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND role IN ("admin", "staff") LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || !verify_password($password, $admin['password'])) {
                record_login_attempt($email, false);
                $error = 'Invalid email or password.';
            } else {
                record_login_attempt($email, true);
                admin_login($admin);
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
    <title>Admin Login | Bottle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #0b0b0b, #000);
            padding: 20px;
        }
        .login-card {
            background: #141414;
            padding: 50px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-card h1 {
            color: #00bcd4;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        .login-card p {
            color: #aaa;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #00bcd4;
        }
        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
            border-left: 4px solid #f44336;
        }
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
            border-left: 4px solid #4caf50;
        }
        .register-link {
            margin-top: 20px;
            color: #aaa;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #00bcd4;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <h1><i class="fas fa-shield-alt"></i> Admin Login</h1>
            <p>Access the Bottle admin panel</p>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= esc($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <?= esc($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_field(); ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="register-link">
                Don't have an admin account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
</body>
</html>
