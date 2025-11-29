<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$message = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (!$name || !$email) {
            $error = 'Name and email are required.';
        } else {
            try {
                // Check email uniqueness
                $checkStmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $checkStmt->execute([$email, $admin['id']]);
                if ($checkStmt->fetch()) {
                    $error = 'Email already in use.';
                } else {
                    if ($password) {
                        // Update with password
                        $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
                        $stmt->execute([$name, $email, hash_password($password), $admin['id']]);
                    } else {
                        // Update without password
                        $stmt = db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                        $stmt->execute([$name, $email, $admin['id']]);
                    }
                    $message = 'Profile updated successfully!';
                    // Refresh admin data
                    $_SESSION['admin_name'] = $name;
                    $_SESSION['admin_email'] = $email;
                }
            } catch (Throwable $e) {
                error_log('Profile update error: ' . $e->getMessage());
                $error = 'Failed to update profile.';
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
    <title>Settings | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('settings') ?>
        
        <div class="admin-main">
            <?= admin_header('Settings', 'Manage your admin account settings') ?>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= esc($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= esc($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Settings -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-cog"></i> Profile Settings</h3>
                    </div>
                    <form method="POST" style="padding: 20px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?= esc($admin['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= esc($admin['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   minlength="8" placeholder="Leave empty to keep current password">
                            <small style="color: #777; font-size: 0.85rem;">Minimum 8 characters. Leave empty to keep current password.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- System Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> System Information</h3>
                    </div>
                    <div style="padding: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <strong style="color: var(--accent);">PHP Version:</strong>
                                <p><?= PHP_VERSION ?></p>
                            </div>
                            <div>
                                <strong style="color: var(--accent);">Server:</strong>
                                <p><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
                            </div>
                            <div>
                                <strong style="color: var(--accent);">Database:</strong>
                                <p>MySQL/MariaDB</p>
                            </div>
                            <div>
                                <strong style="color: var(--accent);">Admin Role:</strong>
                                <p><?= esc(ucfirst($admin['role'] ?? 'admin')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
