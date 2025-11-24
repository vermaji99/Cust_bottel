<?php
declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// -----------------------------------------------------------------------------
// Config helpers
// -----------------------------------------------------------------------------

function app_config(?string $key = null, mixed $default = null): mixed
{
    global $config;

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function db(): PDO
{
    global $pdo;
    return $pdo;
}

// -----------------------------------------------------------------------------
// Output helpers
// -----------------------------------------------------------------------------

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response([
            'success' => false,
            'error' => 'INVALID_JSON',
            'message' => 'Malformed JSON payload.',
        ], 400);
    }
    return $data;
}

function ensure_json_request(): void
{
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
        json_response([
            'success' => false,
            'error' => 'UNSUPPORTED_MEDIA_TYPE',
            'message' => 'Use application/json requests.',
        ], 415);
    }
}

// -----------------------------------------------------------------------------
// CSRF
// -----------------------------------------------------------------------------

function csrf_token(): string
{
    $key = app_config('security.csrf_key');
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$key];
}

function verify_csrf(?string $token): bool
{
    $sessionToken = $_SESSION[app_config('security.csrf_key')] ?? '';
    return is_string($token) && $sessionToken && hash_equals($sessionToken, $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrf_token()) . '">';
}

// -----------------------------------------------------------------------------
// Auth helpers
// -----------------------------------------------------------------------------

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function current_user(): ?array
{
    static $cached;
    if ($cached !== null) {
        return $cached;
    }

    if (empty($_SESSION['user_id'])) {
        $cached = null;
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $cached = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$cached) {
        logout_user();
    }
    return $cached;
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    $stmt = db()->prepare('INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $user['id'],
        session_id(),
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}

function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) {
        $stmt = db()->prepare('DELETE FROM user_sessions WHERE session_id = ?');
        $stmt->execute([session_id()]);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

function api_require_user(): array
{
    $user = current_user();
    if (!$user) {
        json_response([
            'success' => false,
            'error' => 'UNAUTHENTICATED',
            'message' => 'Please log in.',
        ], 401);
    }
    return $user;
}

function require_admin(): array
{
    $user = require_user();
    if (($user['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}

// -----------------------------------------------------------------------------
// Rate limiting
// -----------------------------------------------------------------------------

function record_login_attempt(string $identifier, bool $success): void
{
    try {
        $stmt = db()->prepare('INSERT INTO login_attempts (identifier, ip_address, status) VALUES (?, ?, ?)');
        $stmt->execute([
            $identifier,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $success ? 'success' : 'failed',
        ]);
    } catch (PDOException $e) {
        error_log('record_login_attempt failed: ' . $e->getMessage());
    }
}

function too_many_attempts(string $identifier): bool
{
    $limit = app_config('security.login_rate_limit.max_attempts');
    $window = app_config('security.login_rate_limit.window');
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND created_at >= (NOW() - INTERVAL ? SECOND) AND status = "failed"');
        $stmt->execute([$identifier, $window]);
        return (int) $stmt->fetchColumn() >= $limit;
    } catch (PDOException $e) {
        error_log('too_many_attempts failed: ' . $e->getMessage());
        return false;
    }
}

// -----------------------------------------------------------------------------
// Tokens
// -----------------------------------------------------------------------------

function generate_token(int $length = 64): string
{
    return bin2hex(random_bytes($length / 2));
}

function issue_password_reset_token(int $userId, string $email): string
{
    $now = time();
    $expiry = $now + app_config('security.password_reset_expiry');
    $payload = [
        'sub' => $userId,
        'email' => $email,
        'iat' => $now,
        'exp' => $expiry,
        'purpose' => 'password_reset',
    ];

    $token = JWT::encode($payload, app_config('security.jwt_secret'), 'HS256');
    $stmt = db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))');
    $stmt->execute([$userId, hash('sha256', $token), $expiry]);
    return $token;
}

function validate_password_reset_token(string $token): ?array
{
    try {
        $payload = (array) JWT::decode($token, new Key(app_config('security.jwt_secret'), 'HS256'));
    } catch (Throwable $e) {
        return null;
    }

    if (($payload['purpose'] ?? null) !== 'password_reset') {
        return null;
    }

    $stmt = db()->prepare('SELECT 1 FROM password_resets WHERE token = ? AND consumed_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    if (!$stmt->fetch()) {
        return null;
    }

    return $payload;
}

function mark_password_token_consumed(string $token): void
{
    $stmt = db()->prepare('UPDATE password_resets SET consumed_at = NOW() WHERE token = ?');
    $stmt->execute([hash('sha256', $token)]);
}

function create_email_verification_token(int $userId): string
{
    $token = generate_token(64);
    $stmt = db()->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))');
    $stmt->execute([$userId, hash('sha256', $token), app_config('security.email_verification_expiry')]);
    return $token;
}

function verify_email_token(string $token): bool
{
    $hash = hash('sha256', $token);
    $stmt = db()->prepare('SELECT * FROM email_verifications WHERE token = ? AND consumed_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->execute([$hash]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        return false;
    }

    db()->beginTransaction();
    try {
        $updateUser = db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?');
        $updateUser->execute([$record['user_id']]);

        $markToken = db()->prepare('UPDATE email_verifications SET consumed_at = NOW() WHERE id = ?');
        $markToken->execute([$record['id']]);

        db()->commit();
        return true;
    } catch (Throwable $e) {
        db()->rollBack();
        return false;
    }
}

// -----------------------------------------------------------------------------
// Wishlist / cart helpers
// -----------------------------------------------------------------------------

function wishlist_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM wishlist_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function cart_count(int $userId): int
{
    $stmt = db()->prepare('SELECT SUM(quantity) FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

function wishlist_items(int $userId): array
{
    $stmt = db()->prepare('
        SELECT wi.product_id, wi.created_at, p.name, p.price, p.image, p.stock
        FROM wishlist_items wi
        JOIN products p ON wi.product_id = p.id
        WHERE wi.user_id = ?
        ORDER BY wi.created_at DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function wishlist_add(int $userId, int $productId): void
{
    $stmt = db()->prepare('INSERT IGNORE INTO wishlist_items (user_id, product_id) VALUES (?, ?)');
    $stmt->execute([$userId, $productId]);
}

function wishlist_remove(int $userId, int $productId): void
{
    $stmt = db()->prepare('DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
}

function cart_items_detailed(int $userId): array
{
    $stmt = db()->prepare('
        SELECT ci.*, p.name, p.price, p.stock, p.image
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cart_set_quantity(int $userId, int $productId, int $quantity): array
{
    $stmt = db()->prepare('SELECT id, stock, price FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new RuntimeException('Product not found.');
    }

    if ($product['stock'] < $quantity) {
        $quantity = max(0, (int) $product['stock']);
    }

    if ($quantity <= 0) {
        $delete = db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $delete->execute([$userId, $productId]);
    } else {
        $upsert = db()->prepare('
            INSERT INTO cart_items (user_id, product_id, quantity, price_snapshot, updated_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), price_snapshot = VALUES(price_snapshot), updated_at = NOW()
        ');
        $upsert->execute([$userId, $productId, $quantity, $product['price']]);
    }

    return ['quantity' => $quantity, 'price' => (float) $product['price']];
}

function cart_clear_unavailable(int $userId): void
{
    $stmt = db()->prepare('
        DELETE ci FROM cart_items ci
        LEFT JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ? AND (p.id IS NULL OR p.stock <= 0 OR p.is_active = 0)
    ');
    $stmt->execute([$userId]);
}

function cart_remove(int $userId, int $productId): void
{
    $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
}

function find_coupon(string $code): ?array
{
    $stmt = db()->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) LIMIT 1');
    $stmt->execute([strtoupper($code)]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    return $coupon ?: null;
}

function calculate_coupon_discount(float $subtotal, array $coupon): float
{
    if ($subtotal <= 0) {
        return 0.0;
    }
    if (!empty($coupon['min_amount']) && $subtotal < (float) $coupon['min_amount']) {
        return 0.0;
    }
    $discount = 0.0;
    if ($coupon['type'] === 'percent') {
        $discount = $subtotal * ((float) $coupon['value'] / 100);
    } elseif ($coupon['type'] === 'flat') {
        $discount = (float) $coupon['value'];
    }
    if (!empty($coupon['max_discount'])) {
        $discount = min($discount, (float) $coupon['max_discount']);
    }
    return round(max($discount, 0), 2);
}

