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
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate name
    if (empty($name) || strlen($name) < 2) {
        $message = 'Name must be at least 2 characters long.';
    } else {
        try {
            // Update user profile
            $stmt = db()->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $user_id]);
            
            $message = 'Profile updated successfully!';
            $status = 'success';
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $message = 'Failed to update profile. Please try again.';
        }
    }
} else {
    $message = 'Invalid request method.';
}

// Redirect back to profile page
header("Location: profile.php?message=" . urlencode($message) . "&status=" . $status);
exit;
