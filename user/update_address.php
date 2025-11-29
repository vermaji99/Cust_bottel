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
    $address = trim($_POST['address_line1'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    
    // Validation
    if (empty($address) || empty($city) || empty($pincode)) {
        $message = 'All address fields are required.';
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $message = 'Please enter a valid 6-digit pincode.';
    } else {
        try {
            // Update address
            $stmt = db()->prepare("UPDATE users SET address = ?, city = ?, pincode = ? WHERE id = ?");
            $stmt->execute([$address, $city, $pincode, $user_id]);
            
            $message = 'Address updated successfully!';
            $status = 'success';
        } catch (Exception $e) {
            error_log('Address update error: ' . $e->getMessage());
            $message = 'Failed to update address. Please try again.';
        }
    }
} else {
    $message = 'Invalid request method.';
}

// Redirect back to profile page
header("Location: profile.php?message=" . urlencode($message) . "&status=" . $status . "#address");
exit;
