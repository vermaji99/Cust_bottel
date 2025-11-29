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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired. Please refresh.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $role = $_POST['role'] ?? 'admin';

        if (!$name || !$email || !$password) {
            $error = 'All fields are required.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!in_array($role, ['admin', 'staff'], true)) {
            $error = 'Invalid role selected.';
        } else {
            // Check if email already exists
            $check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Email already registered.';
            } else {
                try {
                    db()->beginTransaction();
                    $stmt = db()->prepare('INSERT INTO users (name, email, password, role, email_verified_at) VALUES (?, ?, ?, ?, NOW())');
                    $stmt->execute([$name, $email, hash_password($password), $role]);
                    db()->commit();
                    
                    $success = 'Admin account created successfully! Please login.';
                    // Auto-redirect after 2 seconds
                    header('refresh:2;url=login.php');
                } catch (Throwable $e) {
                    db()->rollBack();
                    error_log('Admin registration failed: ' . $e->getMessage());
                    $error = 'Registration failed. Please try again.';
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
    <title>Admin Registration | Bottle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <style>
        .register-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #0b0b0b, #000);
            padding: 20px;
        }
        .register-card {
            background: #141414;
            padding: 50px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 480px;
        }
        .register-card h1 {
            color: #00bcd4;
            margin-bottom: 10px;
            font-size: 2rem;
            text-align: center;
        }
        .register-card p {
            color: #aaa;
            margin-bottom: 30px;
            text-align: center;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="register-page">
        <div class="register-card">
            <h1><i class="fas fa-user-plus"></i> Admin Registration</h1>
            <p>Create a new admin account</p>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= esc($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <?= esc($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_field(); ?>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small style="color: #777; font-size: 0.85rem;">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm">Confirm Password</label>
                    <input type="password" id="confirm" name="confirm" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="register-link" style="text-align: center; margin-top: 20px;">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>

