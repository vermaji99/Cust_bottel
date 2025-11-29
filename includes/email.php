<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function make_mailer(): PHPMailer
{
    $mailer = new PHPMailer(true);
    $smtp = app_config('smtp');

    try {
        $mailer->isSMTP();
        $mailer->Host = $smtp['host'] ?? 'smtp.gmail.com';
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtp['username'] ?? '';
        $mailer->Password = is_string($smtp['password'] ?? '')
            ? preg_replace('/\s+/', '', $smtp['password'])
            : ($smtp['password'] ?? '');
        
        // Set encryption
        if (($smtp['encryption'] ?? 'tls') === 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (($smtp['encryption'] ?? '') === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = false;
            $mailer->SMTPAutoTLS = false;
        }
        
        $mailer->Port = $smtp['port'] ?? 587;
        $mailer->CharSet = 'UTF-8';
        $mailer->isHTML(true);
        
        // Enable debug in development
        if (app_config('env') === 'local' || app_config('env') === 'development') {
            $mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $mailer->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
        } else {
            $mailer->SMTPDebug = SMTP::DEBUG_OFF;
        }
        
        // Timeout settings
        $mailer->Timeout = 30;
        $mailer->SMTPKeepAlive = false;

        $mailer->setFrom($smtp['from_email'] ?? $smtp['username'], $smtp['from_name'] ?? 'Bottle');
        if (!empty($smtp['reply_to'])) {
            $mailer->addReplyTo($smtp['reply_to']);
        }
    } catch (Throwable $e) {
        error_log('PHPMailer initialization failed: ' . $e->getMessage());
        throw new RuntimeException('Email service configuration error: ' . $e->getMessage(), 0, $e);
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

function get_admin_email(): ?string
{
    // First try to get admin email from database
    try {
        $stmt = db()->prepare('SELECT email FROM users WHERE role IN ("admin", "staff") ORDER BY id ASC LIMIT 1');
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && !empty($admin['email']) && filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            return $admin['email'];
        }
    } catch (Throwable $e) {
        error_log('Error fetching admin email from database: ' . $e->getMessage());
    }
    
    // Fallback to config admin_email
    $adminEmail = app_config('smtp.admin_email');
    if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return $adminEmail;
    }
    
    // Fallback to config reply_to
    $adminEmail = app_config('smtp.reply_to');
    if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return $adminEmail;
    }
    
    // Final fallback to config from_email
    $adminEmail = app_config('smtp.from_email');
    if ($adminEmail && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return $adminEmail;
    }
    
    return null;
}

// OLD TOKEN-BASED - DEPRECATED
function send_verification_email(array $user, string $token): void
{
    $mailer = make_mailer();
    $mailer->addAddress($user['email'], $user['name']);
    $mailer->Subject = 'Verify your Bottle account';

    $verificationLink = app_config('app_url') . '/verify-email.php?token=' . urlencode($token);
    $mailer->Body = render_email_template('verify', [
        'user' => $user,
        'verificationLink' => $verificationLink,
    ]);

    $mailer->send();
}

// NEW OTP-BASED VERIFICATION EMAIL
function send_otp_email(array $user, string $otp, string $purpose = 'email_verification'): bool
{
    try {
        $mailer = make_mailer();
        
        // Validate email address
        if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email address: " . ($user['email'] ?? 'empty'));
            return false;
        }
        
        $mailer->addAddress($user['email'], $user['name'] ?? 'User');
        
        if ($purpose === 'login') {
            $mailer->Subject = 'Your Bottle Login OTP';
        } else {
            $mailer->Subject = 'Verify your Bottle account - OTP';
        }

        $mailer->Body = render_email_template('otp_verification', [
            'user' => $user,
            'otp' => $otp,
            'purpose' => $purpose,
        ]);

        $result = $mailer->send();
        
        if (!$result) {
            error_log("PHPMailer Error: " . $mailer->ErrorInfo);
        }
        
        return $result;
    } catch (Throwable $e) {
        error_log("Email sending failed for {$user['email']}: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        return false;
    }
}

function send_password_reset_email(array $user, string $token): void
{
    $mailer = make_mailer();
    $mailer->addAddress($user['email'], $user['name']);
    $mailer->Subject = 'Reset your Bottle password';

    $resetLink = app_config('app_url') . '/reset-password.php?token=' . urlencode($token);
    $mailer->Body = render_email_template('password_reset', [
        'user' => $user,
        'resetLink' => $resetLink,
    ]);

    $mailer->send();
}

function send_order_confirmation_email(int $orderId): void
{
    // Check which schema the orders table uses
    $checkColumns = db()->query("SHOW COLUMNS FROM orders");
    $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    $hasNewSchema = in_array('order_number', $columns) && in_array('shipping_email', $columns);
    
    if ($hasNewSchema) {
        // New schema: use shipping_email, shipping_name
        $stmt = db()->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1');
    } else {
        // Old schema: use email, name from orders table
        $stmt = db()->prepare('SELECT o.*, o.name, o.email FROM orders o WHERE o.id = ? LIMIT 1');
    }
    
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return;
    }

    // Get order items - check which price column exists
    $checkItemColumns = db()->query("SHOW COLUMNS FROM order_items");
    $itemColumns = $checkItemColumns->fetchAll(PDO::FETCH_COLUMN);
    $hasUnitPrice = in_array('unit_price', $itemColumns);
    
    if ($hasUnitPrice) {
        $itemsStmt = db()->prepare('SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    } else {
        $itemsStmt = db()->prepare('SELECT oi.*, oi.price AS unit_price, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    }
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total amount from items if total_amount is 0 or missing
    $calculatedSubtotal = 0;
    foreach ($items as $item) {
        $itemPrice = (float)($item['unit_price'] ?? $item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 1);
        $calculatedSubtotal += $itemPrice * $quantity;
    }
    
    // Get discount and calculate final total
    $discountTotal = (float)($order['discount_total'] ?? 0);
    $calculatedTotal = max(0, $calculatedSubtotal - $discountTotal);
    
    // Use calculated values if database values are 0 or missing
    if (!isset($order['subtotal']) || (float)$order['subtotal'] == 0) {
        $order['subtotal'] = $calculatedSubtotal;
    }
    if (!isset($order['total_amount']) || (float)$order['total_amount'] == 0) {
        $order['total_amount'] = $calculatedTotal;
    }
    if (!isset($order['total']) || (float)$order['total'] == 0) {
        $order['total'] = $calculatedTotal;
    }

    // Determine user email and name
    $userEmail = $hasNewSchema ? ($order['shipping_email'] ?? $order['email']) : ($order['email'] ?? '');
    $userName = $hasNewSchema ? ($order['shipping_name'] ?? $order['name']) : ($order['name'] ?? 'User');
    $orderNumber = $hasNewSchema ? ($order['order_number'] ?? 'ORD-' . $orderId) : ('ORD-' . $orderId);
    
    // Generate track order URL
    $trackOrderUrl = app_config('app_url') . '/user/orders.php';

    // Send confirmation email to user only (admin confirmation removed)
    try {
        $mailer = make_mailer();
        $mailer->addAddress($userEmail, $userName);
        $mailer->Subject = "Order #{$orderNumber} confirmed";
        $mailer->Body = render_email_template('order_confirmation', [
            'order' => $order,
            'items' => $items,
            'trackOrderUrl' => $trackOrderUrl,
            'orderId' => $orderId,
        ]);
        $mailer->send();
        error_log("User confirmation email sent to {$userEmail} for order #{$orderNumber}");
    } catch (Throwable $e) {
        error_log("User confirmation email failed for order #{$orderNumber}: " . $e->getMessage());
    }
}

function send_admin_order_received_email(int $orderId): void
{
    // Check which schema the orders table uses
    $checkColumns = db()->query("SHOW COLUMNS FROM orders");
    $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    $hasNewSchema = in_array('order_number', $columns) && in_array('shipping_email', $columns);
    
    if ($hasNewSchema) {
        $stmt = db()->prepare('SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1');
    } else {
        $stmt = db()->prepare('SELECT o.*, o.name, o.email FROM orders o WHERE o.id = ? LIMIT 1');
    }
    
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return;
    }

    // Get order items
    $checkItemColumns = db()->query("SHOW COLUMNS FROM order_items");
    $itemColumns = $checkItemColumns->fetchAll(PDO::FETCH_COLUMN);
    $hasUnitPrice = in_array('unit_price', $itemColumns);
    
    if ($hasUnitPrice) {
        $itemsStmt = db()->prepare('SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    } else {
        $itemsStmt = db()->prepare('SELECT oi.*, oi.price AS unit_price, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    }
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total amount from items if total_amount is 0 or missing
    $calculatedSubtotal = 0;
    foreach ($items as $item) {
        $itemPrice = (float)($item['unit_price'] ?? $item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 1);
        $calculatedSubtotal += $itemPrice * $quantity;
    }
    
    // Get discount and calculate final total
    $discountTotal = (float)($order['discount_total'] ?? 0);
    $calculatedTotal = max(0, $calculatedSubtotal - $discountTotal);
    
    // Use calculated values if database values are 0 or missing
    if (!isset($order['subtotal']) || (float)$order['subtotal'] == 0) {
        $order['subtotal'] = $calculatedSubtotal;
    }
    if (!isset($order['total_amount']) || (float)$order['total_amount'] == 0) {
        $order['total_amount'] = $calculatedTotal;
    }
    if (!isset($order['total']) || (float)$order['total'] == 0) {
        $order['total'] = $calculatedTotal;
    }

    // Determine user details
    $userEmail = $hasNewSchema ? ($order['shipping_email'] ?? $order['email']) : ($order['email'] ?? '');
    $userName = $hasNewSchema ? ($order['shipping_name'] ?? $order['name']) : ($order['name'] ?? 'User');
    $userPhone = $hasNewSchema ? ($order['shipping_phone'] ?? '') : ($order['phone'] ?? '');
    $userAddress = $hasNewSchema ? ($order['shipping_address'] ?? '') : ($order['address'] ?? '');
    $orderNumber = $hasNewSchema ? ($order['order_number'] ?? 'ORD-' . $orderId) : ('ORD-' . $orderId);
    
    // Get admin email
    $adminEmail = get_admin_email();
    if (!$adminEmail) {
        error_log("Admin order received email: No valid admin email found for order #{$orderNumber}");
        return;
    }

    try {
        $mailer = make_mailer();
        $mailer->addAddress($adminEmail, 'Bottle Admin');
        $mailer->Subject = "New Order Received - Order #{$orderNumber} - Action Required";
        $mailer->Body = render_email_template('admin_order_received', [
            'order' => $order,
            'items' => $items,
            'userEmail' => $userEmail,
            'userName' => $userName,
            'userPhone' => $userPhone,
            'userAddress' => $userAddress,
            'orderNumber' => $orderNumber,
        ]);
        $mailer->send();
        error_log("Admin order received email sent to {$adminEmail} for order #{$orderNumber}");
    } catch (Throwable $e) {
        $errorInfo = isset($mailer) && is_object($mailer) ? $mailer->ErrorInfo : 'N/A';
        error_log("Admin order received email failed for order #{$orderNumber}: " . $e->getMessage());
        error_log("PHPMailer Error Info: " . $errorInfo);
    }
}

function notify_admin(string $subject, string $message): void
{
    $mailer = make_mailer();
    $mailer->addAddress(app_config('smtp.reply_to') ?: app_config('smtp.from_email'), 'Bottle Admin');
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

function send_contact_form_notification(string $name, string $email, string $message): bool
{
    try {
        // Get admin email from database (first admin user)
        $adminStmt = db()->prepare('SELECT email, name FROM users WHERE role IN ("admin", "staff") ORDER BY id ASC LIMIT 1');
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        // Fallback to config email if no admin found in database
        $adminEmail = $admin['email'] ?? app_config('smtp.reply_to') ?? app_config('smtp.from_email');
        
        if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('Contact form notification: No valid admin email found');
            return false;
        }
        
        $mailer = make_mailer();
        $mailer->addAddress($adminEmail, $admin['name'] ?? 'Admin');
        $mailer->addReplyTo($email, $name); // Allow admin to reply directly to the sender
        $mailer->Subject = 'New Contact Form Message from ' . esc($name);
        
        $mailer->Body = render_email_template('contact_notification', [
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'date' => date('F j, Y \a\t g:i A'),
        ]);
        
        $result = $mailer->send();
        
        if (!$result) {
            error_log("Contact form notification email failed: " . $mailer->ErrorInfo);
        }
        
        return $result;
    } catch (Throwable $e) {
        error_log("Contact form notification error: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        return false;
    }
}

