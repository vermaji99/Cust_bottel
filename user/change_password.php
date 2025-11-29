<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentUser = current_user();

// Security check
if (!$currentUser) {
    header("Location: ../login.php");
    exit;
}

$user_id = $currentUser['id'];
$message = '';
$status = 'error';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'All password fields are required.';
    } elseif (strlen($new_password) < 8) {
        $message = 'New password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New password and confirm password do not match.';
    } else {
        try {
            // Get current user password
            $stmt = db()->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $message = 'User not found.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $message = 'Current password is incorrect.';
            } else {
                // Update password
                $hashed_password = hash_password($new_password);
                $updateStmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed_password, $user_id]);
                
                $message = 'Password changed successfully!';
                $status = 'success';
            }
        } catch (Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            $message = 'Failed to change password. Please try again.';
        }
    }
} else {
    $message = 'Invalid request method.';
}

// Redirect back to profile page
header("Location: profile.php?message=" . urlencode($message) . "&status=" . $status . "#password");
exit;
