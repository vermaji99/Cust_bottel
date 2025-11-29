# SMTP Email Configuration Guide

## Problem: SMTP Authentication Error

If you're getting `SMTP Error: Could not authenticate`, follow these steps:

### Step 1: Gmail App Password Setup

1. Go to your Google Account: https://myaccount.google.com/
2. Click on **Security** (left sidebar)
3. Enable **2-Step Verification** if not already enabled
4. After enabling, you'll see **App passwords** option
5. Click **App passwords**
6. Select app: **Mail** and device: **Other (Custom name)** - enter "Bottel"
7. Click **Generate**
8. Copy the 16-character password (no spaces)

### Step 2: Update Config File

Edit `Cust_bottel/includes/config.php`:

```php
$smtpConfig = [
  'host' => 'smtp.gmail.com',
  'port' => 587,
  'encryption' => 'tls',
  'username' => 'your-email@gmail.com',  // Your Gmail address
  'password' => 'your-16-char-app-password',  // App password from Step 1
  'from_email' => 'your-email@gmail.com',
  'from_name' => 'Bottel',
  'reply_to' => 'your-email@gmail.com',
];
```

### Step 3: Alternative - Use Different SMTP Service

If Gmail doesn't work, you can use:

#### Option A: Mailtrap (Testing)
```php
$smtpConfig = [
  'host' => 'smtp.mailtrap.io',
  'port' => 2525,
  'encryption' => 'tls',
  'username' => 'your-mailtrap-username',
  'password' => 'your-mailtrap-password',
  'from_email' => 'test@bottel.com',
  'from_name' => 'Bottel',
];
```

#### Option B: SendGrid
```php
$smtpConfig = [
  'host' => 'smtp.sendgrid.net',
  'port' => 587,
  'encryption' => 'tls',
  'username' => 'apikey',
  'password' => 'your-sendgrid-api-key',
  'from_email' => 'noreply@yourdomain.com',
  'from_name' => 'Bottel',
];
```

### Step 4: Test Email Configuration

Create a test file `test-email.php` in root:

```php
<?php
require __DIR__ . '/includes/bootstrap.php';

try {
    $result = send_otp_email(
        ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com'],
        '123456',
        'email_verification'
    );
    
    if ($result) {
        echo "Email sent successfully!";
    } else {
        echo "Email failed. Check error logs.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Current Implementation Features

✅ **Email failures don't break the flow** - OTP is still stored in database
✅ **OTP displayed on page** if email fails to send
✅ **Session fallback** - OTP stored in session as backup
✅ **Proper error logging** - Check error logs for SMTP issues

## Troubleshooting

### Check Error Logs

Look in your PHP error log (usually in `C:\xampp\php\logs\php_error_log` or Apache logs):

```bash
# Windows
notepad C:\xampp\php\logs\php_error_log

# Or check Apache error log
notepad C:\xampp\apache\logs\error.log
```

### Common Issues

1. **"Could not authenticate"**
   - App password is incorrect
   - 2-Step Verification not enabled
   - Username should be full email address

2. **"Connection timeout"**
   - Firewall blocking port 587
   - Try port 465 with SSL instead

3. **"Email not received"**
   - Check spam folder
   - Email might be sent but not delivered
   - OTP is still available in database and shown on page if email fails

## Important Notes

- **OTP is ALWAYS saved in database** even if email fails
- **OTP is shown on verification page** if email sending fails
- **Session stores OTP** as backup for 10 minutes
- **System continues to work** even if emails don't send

