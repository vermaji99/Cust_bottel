<?php
require __DIR__ . '/includes/bootstrap.php';

$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;

// If already logged in, redirect to home
if ($isLoggedIn) {
    header("Location: index.php");
    exit;
}

$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Security token expired. Please try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $message = 'Please enter a valid email address.';
        } else {
            $stmt = db()->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                try {
                    $token = issue_password_reset_token((int) $user['id'], $user['email']);
                    send_password_reset_email($user, $token);
                    $success = 'If that email exists, a password reset link has been sent to your email.';
                } catch (Exception $e) {
                    error_log('Password reset error: ' . $e->getMessage());
                    $message = 'Unable to send reset email. Please try again later.';
                }
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = 'If that email exists, a password reset link has been sent to your email.';
            }
        }
    }
}

$currentPage = 'forgot-password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>Forgot Password | Bottle</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "Poppins", sans-serif;
      background: radial-gradient(circle at 50% 0%, #1a1f25 0%, #0b0b0b 60%);
      color: #e0e0e0;
      line-height: 1.6;
      overflow-x: hidden;
      min-height: 100vh;
      padding-top: 0;
      overflow-y: auto;
    }

    /* Main Container */
    .forgot-password-container {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: calc(100vh - 80px);
      padding-top: clamp(30px, 4vw, 50px);
      padding-bottom: clamp(20px, 3vw, 40px);
      padding-left: clamp(1rem, 4vw, 2rem);
      padding-right: clamp(1rem, 4vw, 2rem);
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
      margin: 0 auto;
    }
    
    @media (min-width: 1024px) {
      .forgot-password-container {
        min-height: calc(100vh - 100px);
        padding-top: 50px;
        padding-bottom: 50px;
        padding-left: clamp(1rem, 4vw, 2rem);
        padding-right: clamp(1rem, 4vw, 2rem);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 100%;
        margin-top:45px;
      }
      
      .forgot-password-card {
        padding: 1.25rem;
        max-width: 380px;
      }
      
      .forgot-password-header {
        margin-bottom: 1rem;
      }
      
      .forgot-password-header .icon {
        width: 48px;
        height: 48px;
        font-size: 1.1rem;
        margin-bottom: 0.625rem;
      }
      
      .forgot-password-header h1 {
        font-size: 1.25rem;
        margin-bottom: 0.375rem;
      }
      
      .forgot-password-header p {
        font-size: 0.8rem;
        line-height: 1.4;
      }
      
      .form-group {
        margin-bottom: 0.75rem;
      }
      
      .form-group label {
        font-size: 0.8rem;
        margin-bottom: 0.4rem;
      }
      
      .form-group input {
        padding: 8px 12px;
        font-size: 0.85rem;
      }
      
      .btn-submit {
        padding: 8px 20px;
        font-size: 0.85rem;
        margin-top: 0.625rem;
      }
      
      .alert {
        padding: 8px 12px;
        font-size: 0.8rem;
        margin-bottom: 0.875rem;
      }
      
      .forgot-password-links {
        margin-top: 1rem;
        padding-top: 0.75rem;
      }
      
      .forgot-password-links a {
        font-size: 0.8rem;
      }
    }
    
    @media (min-width: 1440px) {
      .forgot-password-container {
        padding-top: 50px;
        padding-bottom: 50px;
        padding-left: clamp(1rem, 4vw, 2rem);
        padding-right: clamp(1rem, 4vw, 2rem);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 100%;
      }
      
      .forgot-password-card {
        padding: 1.125rem;
        max-width: 360px;
      }
      
      .forgot-password-header .icon {
        width: 44px;
        height: 44px;
        font-size: 1rem;
      }
      
      .forgot-password-header h1 {
        font-size: 1.15rem;
      }
      
      .forgot-password-header p {
        font-size: 0.75rem;
      }
    }

    /* Card */
    .forgot-password-card {
      background: rgba(22, 22, 22, 0.95);
      border: 1px solid #252525;
      border-radius: 16px;
      padding: clamp(1.5rem, 4vw, 2rem);
      max-width: 420px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
      text-align: center;
      box-sizing: border-box;
      margin: 0 auto;
      flex-shrink: 0;
    }

    /* Header */
    .forgot-password-header {
      margin-bottom: clamp(1.25rem, 3vw, 1.5rem);
    }

    .forgot-password-header .icon {
      width: clamp(48px, 8vw, 56px);
      height: clamp(48px, 8vw, 56px);
      background: linear-gradient(135deg, #00bcd4, #007bff);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto clamp(0.75rem, 2vw, 1rem);
      font-size: clamp(1.1rem, 3vw, 1.4rem);
      color: #fff;
    }

    .forgot-password-header h1 {
      font-size: clamp(1.15rem, 3.5vw, 1.4rem);
      font-weight: 700;
      color: #fff;
      margin-bottom: clamp(0.375rem, 1vw, 0.5rem);
    }

    .forgot-password-header p {
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      color: #a0a0a0;
      margin: 0;
      line-height: 1.5;
    }

    /* Form */
    .forgot-password-form {
      margin-bottom: clamp(1.25rem, 3vw, 1.5rem);
    }

    .form-group {
      margin-bottom: clamp(0.75rem, 2vw, 1rem);
      text-align: left;
    }

    .form-group label {
      display: block;
      font-size: clamp(0.8rem, 1.8vw, 0.85rem);
      color: #ccc;
      margin-bottom: clamp(0.4rem, 1vw, 0.5rem);
      font-weight: 500;
    }

    .form-group input {
      width: 100%;
      padding: clamp(8px, 2vw, 10px) clamp(12px, 3vw, 14px);
      border: 1px solid #252525;
      border-radius: 10px;
      background: #1a1a1a;
      color: #fff;
      font-size: clamp(0.85rem, 2vw, 0.9rem);
      font-family: inherit;
      transition: all 0.3s;
      box-sizing: border-box;
    }

    .form-group input:focus {
      outline: none;
      border-color: #00bcd4;
      box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
      background: #1f1f1f;
    }

    .form-group input::placeholder {
      color: #666;
    }

    /* Button */
    .btn-submit {
      width: 100%;
      padding: clamp(8px, 2vw, 10px) clamp(18px, 4vw, 24px);
      background: linear-gradient(135deg, #00bcd4, #007bff);
      border: none;
      border-radius: 50px;
      color: #fff;
      font-size: clamp(0.85rem, 2vw, 0.9rem);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
      margin-top: clamp(0.5rem, 1.2vw, 0.75rem);
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
    }

    .btn-submit:active {
      transform: translateY(0);
    }

    .btn-submit:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    /* Messages */
    .alert {
      padding: clamp(8px, 2vw, 10px) clamp(12px, 3vw, 15px);
      border-radius: 10px;
      margin-bottom: clamp(0.875rem, 2vw, 1rem);
      display: flex;
      align-items: center;
      gap: clamp(6px, 1.5vw, 10px);
      font-size: clamp(0.8rem, 1.8vw, 0.85rem);
      text-align: left;
      animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-error {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid #dc3545;
      color: #dc3545;
    }

    .alert-success {
      background: rgba(0, 188, 212, 0.1);
      border: 1px solid #00bcd4;
      color: #00bcd4;
    }

    .alert i {
      font-size: clamp(0.9rem, 2vw, 1rem);
      flex-shrink: 0;
    }

    /* Links */
    .forgot-password-links {
      margin-top: clamp(1rem, 3vw, 1.5rem);
      padding-top: clamp(0.75rem, 2vw, 1rem);
      border-top: 1px solid #252525;
    }

    .forgot-password-links a {
      color: #00bcd4;
      text-decoration: none;
      font-size: clamp(0.8rem, 1.8vw, 0.85rem);
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .forgot-password-links a:hover {
      color: #007bff;
      text-decoration: underline;
    }

    .forgot-password-links a i {
      font-size: clamp(0.8rem, 1.8vw, 0.85rem);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .forgot-password-container {
        min-height: calc(100vh - 70px);
        padding-top: clamp(20px, 3vw, 30px);
        padding-bottom: clamp(20px, 3vw, 30px);
        padding-left: clamp(1rem, 3vw, 1.5rem);
        padding-right: clamp(1rem, 3vw, 1.5rem);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 100%;
        /* margin-top:80px; */
      }

      .forgot-password-card {
        padding: clamp(1.5rem, 4vw, 2rem);
        border-radius: 16px;
      }

      .forgot-password-header .icon {
        width: 56px;
        height: 56px;
        font-size: 1.5rem;
      }
    }

    @media (max-width: 480px) {
      .forgot-password-container {
        min-height: calc(100vh - 65px);
        padding-top: clamp(15px, 2.5vw, 20px);
        padding-bottom: clamp(15px, 2.5vw, 20px);
        padding-left: clamp(1rem, 2.5vw, 1.25rem);
        padding-right: clamp(1rem, 2.5vw, 1.25rem);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 100%;
      }

      .forgot-password-card {
        padding: clamp(1.25rem, 3vw, 1.5rem);
        border-radius: 12px;
      }

      .forgot-password-header .icon {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
      }
    }

    @media (max-width: 360px) {
      .forgot-password-container {
        min-height: calc(100vh - 60px);
        padding: 15px 1rem;
      }
      
      .forgot-password-card {
        padding: 1.25rem 1rem;
      }
    }

    /* Loading State */
    .btn-submit.loading {
      position: relative;
      color: transparent;
    }

    .btn-submit.loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
      to {
        transform: translate(-50%, -50%) rotate(360deg);
      }
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="forgot-password-container">
  <div class="forgot-password-card">
    <div class="forgot-password-header">
      <div class="icon">
        <i class="fas fa-key"></i>
      </div>
      <h1>Forgot Password?</h1>
      <p>No worries! Enter your email address and we'll send you a link to reset your password.</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= esc($message); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?= esc($success); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" class="forgot-password-form" id="forgotPasswordForm">
      <?= csrf_field(); ?>
      
      <div class="form-group">
        <label for="email">
          <i class="fas fa-envelope"></i> Email Address
        </label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          placeholder="Enter your email address" 
          required 
          autocomplete="email"
          value="<?= esc($_POST['email'] ?? ''); ?>"
        >
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">
        <i class="fas fa-paper-plane"></i> Send Reset Link
      </button>
    </form>

    <div class="forgot-password-links">
      <a href="login.php">
        <i class="fas fa-arrow-left"></i> Back to Login
      </a>
    </div>
  </div>
</div>

<script>
// Form submission with loading state
(function() {
  const form = document.getElementById('forgotPasswordForm');
  const submitBtn = document.getElementById('submitBtn');
  
  if (form && submitBtn) {
    form.addEventListener('submit', function(e) {
      const email = document.getElementById('email').value.trim();
      
      if (!email) {
        e.preventDefault();
        return false;
      }
      
      // Add loading state
      submitBtn.disabled = true;
      submitBtn.classList.add('loading');
      submitBtn.innerHTML = '';
      
      // Form will submit normally
    });
  }
})();
</script>

<script src="assets/js/navbar.js" defer></script>
<script src="assets/js/app.js" defer></script>
</body>
</html>
