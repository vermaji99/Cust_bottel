<?php
require __DIR__ . '/bootstrap.php';
ensure_json_request();
$payload = read_json_body();

$email = filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $payload['password'] ?? '';

if (!$email || !$password) {
    json_response(['success' => false, 'error' => 'INVALID_INPUT'], 422);
}

if (too_many_attempts($email)) {
    json_response(['success' => false, 'error' => 'RATE_LIMIT'], 429);
}

$stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !verify_password($password, $user['password'])) {
    record_login_attempt($email, false);
    json_response(['success' => false, 'error' => 'INVALID_CREDENTIALS'], 401);
}

if (empty($user['email_verified_at'])) {
    json_response(['success' => false, 'error' => 'EMAIL_NOT_VERIFIED'], 403);
}

record_login_attempt($email, true);
login_user($user);

json_response([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
    ],
]);





