<?php
declare(strict_types=1);

$env = getenv('APP_ENV') ?: 'local';
$appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/custom_bottel/Cust_bottel', '/');

$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'name' => getenv('DB_NAME') ?: 'bottel_db',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
];

$smtpConfig = [
  'host' => 'smtp.gmail.com',
  'port' => 587,
  'encryption' => 'tls',
  'username' => 'aryanjichoudhary1212@gmail.com',  // Your Gmail
  'password' => 'oyupfienkhjeiqpg',     // Google App Password
  'from_email' => 'aryanjichoudhary1212@gmail.com',
  'from_name' => 'Bottle',
  'reply_to' => 'modiakshat130@gmail.com',  // Admin email for notifications
  'admin_email' => 'modiakshat130@gmail.com',  // Admin email for order notifications
];


$securityConfig = [
    'session_name' => getenv('SESSION_NAME') ?: 'bottel_session',
    'csrf_key' => getenv('CSRF_TOKEN_KEY') ?: 'bottel_csrf',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'base64:VW5zYWZlRGVmYXVsdEtleQ==',
    'password_reset_expiry' => 60 * 30, // 30 minutes
    'email_verification_expiry' => 60 * 60 * 24, // 24 hours
    'login_rate_limit' => [
        'max_attempts' => 5,
        'window' => 900, // 15 minutes
        'lockout' => 900,
    ],
];

$paths = [
    'root' => dirname(__DIR__),
    'uploads' => __DIR__ . '/../admin/uploads',
    'designs' => __DIR__ . '/../admin/uploads/designs',
    'thumbnails' => __DIR__ . '/../admin/uploads/designs/thumbnails',
    'email_templates' => __DIR__ . '/emails',
];

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['name']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false,
    ]);
} catch (PDOException $e) {
    throw new RuntimeException('DB connection failed: ' . $e->getMessage(), 0, $e);
}

return [
    'env' => $env,
    'app_url' => $appUrl,
    'db' => $dbConfig,
    'smtp' => $smtpConfig,
    'security' => $securityConfig,
    'paths' => $paths,
];
