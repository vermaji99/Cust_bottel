<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function make_mailer(): PHPMailer
{
    $mailer = new PHPMailer(true);
    $smtp = app_config('smtp');

    $mailer->isSMTP();
    $mailer->Host = $smtp['host'];
    $mailer->SMTPAuth = true;
    $mailer->Username = $smtp['username'];
    $mailer->Password = is_string($smtp['password'])
        ? preg_replace('/\s+/', '', $smtp['password'])
        : $smtp['password'];
    $mailer->SMTPSecure = $smtp['encryption'] === 'tls'
        ? PHPMailer::ENCRYPTION_STARTTLS
        : ($smtp['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : $smtp['encryption']);
    $mailer->Port = $smtp['port'];
    $mailer->CharSet = 'UTF-8';
    $mailer->isHTML(true);
    if ($smtp['encryption'] === 'none') {
        $mailer->SMTPSecure = false;
        $mailer->SMTPAutoTLS = false;
    }
    if (app_config('env') !== 'production') {
        $mailer->SMTPDebug = SMTP::DEBUG_OFF;
    }

    $mailer->setFrom($smtp['from_email'], $smtp['from_name']);
    if (!empty($smtp['reply_to'])) {
        $mailer->addReplyTo($smtp['reply_to']);
    }

    return $mailer;
}

function render_email_template(string $template, array $data = []): string
{
    $templatePath = app_config('paths.email_templates') . '/' . $template . '.php';
    if (!file_exists($templatePath)) {
        throw new RuntimeException("Email template {$template} not found.");
    }

    extract($data, EXTR_SKIP);
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

function send_verification_email(array $user, string $token): void
{
    $mailer = make_mailer();
    $mailer->addAddress($user['email'], $user['name']);
    $mailer->Subject = 'Verify your Bottel account';

    $verificationLink = app_config('app_url') . '/verify-email.php?token=' . urlencode($token);
    $mailer->Body = render_email_template('verify', [
        'user' => $user,
        'verificationLink' => $verificationLink,
    ]);

    $mailer->send();
}

function send_password_reset_email(array $user, string $token): void
{
    $mailer = make_mailer();
    $mailer->addAddress($user['email'], $user['name']);
    $mailer->Subject = 'Reset your Bottel password';

    $resetLink = app_config('app_url') . '/reset-password.php?token=' . urlencode($token);
    $mailer->Body = render_email_template('password_reset', [
        'user' => $user,
        'resetLink' => $resetLink,
    ]);

    $mailer->send();
}

function send_order_confirmation_email(int $orderId): void
{
    $stmt = db()->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return;
    }

    $itemsStmt = db()->prepare('SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $mailer = make_mailer();
    $mailer->addAddress($order['email'], $order['name']);
    $mailer->Subject = "Order #{$order['order_number']} confirmed";
    $mailer->Body = render_email_template('order_confirmation', [
        'order' => $order,
        'items' => $items,
    ]);
    $mailer->send();
}

function notify_admin(string $subject, string $message): void
{
    $mailer = make_mailer();
    $mailer->addAddress(app_config('smtp.reply_to') ?: app_config('smtp.from_email'), 'Bottel Admin');
    $mailer->Subject = $subject;
    $mailer->Body = render_email_template('admin_notification', [
        'message' => $message,
    ]);
    $mailer->send();
}

function send_design_upload_email(array $user, array $design): void
{
    $mailer = make_mailer();
    $mailer->addAddress($user['email'], $user['name']);
    $mailer->Subject = 'Your custom design has been saved';
    $mailer->Body = render_email_template('custom_design', [
        'user' => $user,
        'design' => $design,
    ]);
    $mailer->send();
}

