<?php
require __DIR__ . '/bootstrap.php';
ensure_json_request();
$payload = read_json_body();

$name = trim($payload['name'] ?? '');
$email = filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $payload['password'] ?? '';

if (!$name || !$email || strlen($password) < 8) {
    json_response([
        'success' => false,
        'error' => 'INVALID_INPUT',
        'message' => 'Provide name, valid email and password (min 8 chars).',
    ], 422);
}

$check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch()) {
    json_response(['success' => false, 'error' => 'EMAIL_EXISTS'], 409);
}

db()->beginTransaction();
try {
    $stmt = db()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, hash_password($password)]);
    $userId = (int) db()->lastInsertId();
    
    // Create and send OTP for email verification
    $otp = create_and_send_otp($userId, $email, 'email_verification');
    
    // Try to send email, but don't fail if it doesn't work
    $emailSent = false;
    try {
        $emailSent = send_otp_email(['id' => $userId, 'name' => $name, 'email' => $email], $otp, 'email_verification');
    } catch (Throwable $e) {
        error_log('Registration OTP email failed: ' . $e->getMessage());
    }
    
    db()->commit();
} catch (Throwable $e) {
    db()->rollBack();
    json_response(['success' => false, 'error' => 'SERVER_ERROR'], 500);
}

json_response([
    'success' => true, 
    'message' => $emailSent 
        ? 'Registered. Please check your email for OTP verification.' 
        : 'Registered. OTP generated. Email sending failed - please contact support.',
    'email' => $email,
    'email_sent' => $emailSent,
    'otp' => $emailSent ? null : $otp, // Show OTP in response if email failed (for debugging/alternative delivery)
]);





