<?php /** @var string $name */ /** @var string $email */ /** @var string $message */ /** @var string $date */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Poppins', Arial, sans-serif; background: #0b0b0b;">
    <div style="max-width: 600px; margin: 0 auto; background: #0b0b0b; color: #fff; padding: 30px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #00bcd4; margin: 0; font-size: 2rem;">🧴 Bottle</h1>
            <p style="color: #aaa; margin: 5px 0 0 0;">New Contact Form Submission</p>
        </div>
        
        <div style="background: #1a1a1a; border-radius: 12px; padding: 25px; margin-bottom: 20px; border-left: 4px solid #00bcd4;">
            <h2 style="color: #00bcd4; margin-top: 0; font-size: 1.5rem;">New Message Received</h2>
            <p style="color: #ddd; line-height: 1.6;">You have received a new message from the contact form on your website.</p>
        </div>
        
        <div style="background: #1a1a1a; border-radius: 12px; padding: 25px; margin-bottom: 20px;">
            <div style="margin-bottom: 20px;">
                <strong style="color: #00bcd4; display: block; margin-bottom: 5px;">From:</strong>
                <p style="color: #fff; margin: 0; font-size: 1.1rem;"><?= esc($name) ?></p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <strong style="color: #00bcd4; display: block; margin-bottom: 5px;">Email:</strong>
                <p style="color: #fff; margin: 0;">
                    <a href="mailto:<?= esc($email) ?>" style="color: #00bcd4; text-decoration: none;"><?= esc($email) ?></a>
                </p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <strong style="color: #00bcd4; display: block; margin-bottom: 5px;">Date:</strong>
                <p style="color: #aaa; margin: 0;"><?= esc($date) ?></p>
            </div>
            
            <div>
                <strong style="color: #00bcd4; display: block; margin-bottom: 10px;">Message:</strong>
                <div style="background: #0b0b0b; padding: 15px; border-radius: 8px; color: #ddd; line-height: 1.8; white-space: pre-wrap;">
<?= esc($message) ?>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #2a2a2a;">
            <a href="<?= esc(app_config('app_url')); ?>/admin/messages.php" 
               style="background: linear-gradient(45deg, #00bcd4, #007bff); color: #fff; padding: 14px 28px; border-radius: 30px; text-decoration: none; font-weight: 600; display: inline-block;">
                View in Admin Panel
            </a>
        </div>
        
        <p style="text-align: center; color: #666; font-size: 0.85rem; margin-top: 30px;">
            This is an automated notification from your Bottle website.
        </p>
    </div>
</body>
</html>

