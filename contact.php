<?php 
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;
$currentPage = 'contact';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>Contact Us | Bottle</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <link rel="stylesheet" href="assets/css/navbar.css">
  
  <style>
    /* --- RESET & BASICS --- */
    *, *::before, *::after {
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
    }

    a { text-decoration: none; color: inherit; transition: 0.3s; }
    ul { list-style: none; }
    img, video { display: block; max-width: 100%; height: auto; }

    /* --- LAYOUT UTILITIES --- */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      width: 100%;
    }

    .section-padding {
      padding: 100px 0;
    }

    /* --- TYPOGRAPHY UTILITIES --- */
    .text-highlight {
      background: linear-gradient(90deg, #00bcd4, #007bff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: inline-block;
    }

    .section-label {
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: #00bcd4;
      margin-bottom: 12px;
      display: block;
      opacity: 0.9;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 25px;
      line-height: 1.2;
    }

    .section-desc {
      color: #a0a0a0;
      font-size: 1.05rem;
      margin-bottom: 20px;
      font-weight: 300;
    }

    /* --- BUTTONS --- */
    .btn {
      display: inline-block;
      padding: 14px 32px;
      background: linear-gradient(135deg, #00bcd4, #007bff);
      color: #fff;
      border-radius: 50px;
      font-weight: 500;
      font-size: 0.95rem;
      box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }

    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
    }

    /* --- HERO SECTION --- */
    .hero {
      position: relative;
      height: 50vh;
      min-height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      background: url('assets/images/contact-bg.jpg') center/cover no-repeat;
      margin-top: 5px; 
    }

    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(to bottom, rgba(11,11,11,0.3), #0b0b0b);
      z-index: 1;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 800px;
      padding: 0 20px;
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    /* --- CONTACT SECTION --- */
    .contact-card {
      background: #161616;
      border-radius: 20px;
      padding: 25px;
      border: 1px solid #252525;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
      margin-bottom: 60px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }

    .contact-card-header {
      margin-bottom: 20px;
    }

    .contact-card-header h2 {
      font-size: 1.75rem;
      color: #fff;
      margin-bottom: 0;
      font-weight: 600;
    }

    .contact-card-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
      align-items: start;
    }

    .contact-map-wrapper {
      border-radius: 15px;
      overflow: hidden;
      border: 1px solid #252525;
      height: 100%;
      min-height: 220px;
    }

    .contact-map-wrapper iframe {
      width: 100%;
      height: 100%;
      min-height: 220px;
      border: none;
      display: block;
    }

    .contact-details {
      padding-left: 20px;
    }

    .contact-details i {
      color: #00bcd4;
      margin-right: 12px;
      width: 20px;
      text-align: center;
    }

    .contact-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 18px;
      padding-bottom: 18px;
      border-bottom: 1px solid #252525;
    }

    .contact-item:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .contact-item-content {
      flex: 1;
    }

    .contact-item-content strong {
      color: #fff;
      display: block;
      margin-bottom: 5px;
      font-size: 0.95rem;
    }

    .contact-item-content span {
      color: #a0a0a0;
      font-size: 0.9rem;
    }

    /* --- CONTACT FORM (BELOW) --- */
    .contact-form {
      background: #161616;
      border-radius: 20px;
      padding: 25px;
      border: 1px solid #252525;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
      max-width: 900px;
      margin: 0 auto;
    }

    .contact-form h2 {
      font-size: 1.75rem;
      color: #fff;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      color: #fff;
      margin-bottom: 8px;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 14px 18px;
      border-radius: 12px;
      border: 1px solid #252525;
      background: #1a1a1a;
      color: #fff;
      font-size: 0.95rem;
      font-family: inherit;
      transition: all 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #00bcd4;
      box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
    }

    .form-group textarea {
      height: 140px;
      resize: vertical;
    }

    .alert {
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.9rem;
    }

    .alert-success {
      background: rgba(0, 188, 212, 0.1);
      border: 1px solid #00bcd4;
      color: #00bcd4;
    }

    .alert-error {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid #dc3545;
      color: #dc3545;
    }


    /* --- FOOTER --- */
    footer {
      background: #080808;
      padding: 70px 0 30px;
      border-top: 1px solid #1a1a1a;
    }

    .footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 40px;
      margin-bottom: 50px;
    }

    .footer-col h4 {
      color: #fff;
      font-size: 1.1rem;
      margin-bottom: 20px;
      position: relative;
      display: inline-block;
    }
    
    .footer-col h4::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: -8px;
      width: 30px;
      height: 2px;
      background: #00bcd4;
    }

    .footer-col p { color: #888; font-size: 0.9rem; margin-bottom: 10px; }
    .footer-col a { color: #888; }
    .footer-col a:hover { color: #00bcd4; padding-left: 5px; }

    .social-links { display: flex; gap: 15px; margin-top: 15px; }
    .social-links a { 
      width: 36px; 
      height: 36px; 
      background: #1a1a1a; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      border-radius: 50%;
      color: #fff;
      transition: 0.3s;
    }
    .social-links a:hover { background: #00bcd4; transform: translateY(-3px); }

    .copyright {
      text-align: center;
      padding-top: 30px;
      border-top: 1px solid #1a1a1a;
      color: #555;
      font-size: 0.85rem;
    }

    /* --- RESPONSIVE --- */
    @media (max-width: 992px) {
      .contact-card-content { 
        grid-template-columns: 1fr; 
        gap: 30px; 
      }
      .hero h1 { font-size: 2.5rem; }
      .contact-map-wrapper {
        min-height: 200px;
      }
      .contact-details {
        padding-left: 0;
      }
    }

    @media (max-width: 768px) {
      .section-padding { padding: 60px 0; }
      .section-title { font-size: 2rem; }
      .contact-card,
      .contact-form {
        padding: 30px 20px;
      }
      .contact-card {
        margin-bottom: 40px;
      }
      .contact-map-wrapper {
        min-height: 180px;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="hero">
  <div class="hero-content">
    <h1><span class="text-highlight">Contact Us</span></h1>
    <p style="color: #ccc; font-size: 1.1rem; margin-top: 10px;">We're here to help you create something amazing.</p>
  </div>
</section>

<section class="section-padding">
  <div class="container">
    <div class="contact-card">
      <div class="contact-card-header">
        <span class="section-label">Get in Touch</span>
        <h2 class="section-title">Let's Start a <span class="text-highlight">Conversation</span></h2>
      </div>
      
      <div class="contact-card-content">
        <div class="contact-map-wrapper">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3680.289802836553!2d75.8577258750727!3d22.719568079403187!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3962fdcd5ba9c7e1%3A0x8b5820f0abcbca2c!2sIndore%2C%20Madhya%20Pradesh!5e0!3m2!1sen!2sin!4v1709554787365!5m2!1sen!2sin"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
        
        <div class="contact-details">
          <div class="contact-item">
            <i class="fas fa-map-marker-alt"></i>
            <div class="contact-item-content">
              <strong>Address</strong>
              <span>45/7 Creative Park, Indore, India</span>
            </div>
          </div>
          
          <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <div class="contact-item-content">
              <strong>Email</strong>
              <span>support@bottle.com</span>
            </div>
          </div>
          
          <div class="contact-item">
            <i class="fas fa-phone"></i>
            <div class="contact-item-content">
              <strong>Phone</strong>
              <span>+91 98765 43210</span>
            </div>
          </div>
        </div>
      </div>
  </div>

  <div class="contact-form">
        <span class="section-label">Send Message</span>
        <h2 class="section-title">Drop Us a <span class="text-highlight">Line</span></h2>
    <?php
    $success = false;
    $errorMsg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $name = trim($_POST['name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $message = trim($_POST['message'] ?? '');
      if ($name && $email && $message) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
          try {
            $checkTable = db()->query("SHOW TABLES LIKE 'messages'");
            if ($checkTable->rowCount() === 0) {
              db()->exec("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
            $stmt = db()->prepare("INSERT INTO messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $message]);
            
            try {
                send_contact_form_notification($name, $email, $message);
            } catch (Exception $e) {
                error_log('Contact form email notification error: ' . $e->getMessage());
            }
            
            $success = true;
            $name = $email = $message = '';
          } catch (Exception $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $errorMsg = 'Error sending message. Please try again.';
          }
        } else {
          $errorMsg = 'Please enter a valid email address.';
        }
      } else {
        $errorMsg = 'Please fill all fields.';
      }
    }
    ?>
    <?php if ($success): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Thank you! Your message has been sent. We'll reach out to you within 24 hours.</span>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= esc($errorMsg) ?></span>
      </div>
    <?php endif; ?>
    <form method="POST" action="" id="contactForm">
      <div class="form-group">
            <label for="name">Your Name</label>
            <input type="text" id="name" name="name" placeholder="Enter your name" value="<?= esc($name ?? '') ?>" required>
      </div>
      <div class="form-group">
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" value="<?= esc($email ?? '') ?>" required>
      </div>
      <div class="form-group">
            <label for="message">Your Message</label>
            <textarea id="message" name="message" placeholder="Tell us about your project..." required><?= esc($message ?? '') ?></textarea>
      </div>
          <button type="submit" class="btn">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>
    </form>
      </div>

  </div>
</section>

<footer>
  <div class="container">
  <div class="footer-grid">
      <div class="footer-col">
      <h4>About Bottle</h4>
        <p>We craft personalized premium water bottles for restaurants & events across India. Quality meets elegance.</p>
    </div>
      <div class="footer-col">
      <h4>Quick Links</h4>
        <p><a href="category.php">Shop Now</a></p>
        <p><a href="about.php">About Us</a></p>
      <p><a href="contact.php">Contact</a></p>
    </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <p>support@bottle.com</p>
        <p>+91 98765 43210</p>
        <p>Indore, India</p>
    </div>
      <div class="footer-col">
      <h4>Follow Us</h4>
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
      </div>
    </div>
  </div>
    <div class="copyright">
      <p>&copy; <?= date('Y'); ?> Bottle. All rights reserved.</p>
    </div>
  </div>
</footer>

<?php if (!$isLoggedIn): ?>
<!-- Login Popup Modal -->
<style>
.login-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.login-popup-overlay.show {
    display: flex;
    opacity: 1;
}

.login-popup-modal {
    background: rgba(22, 22, 22, 0.95);
    border: 1px solid #252525;
    border-radius: 20px;
    padding: 30px;
    width: 90%;
    max-width: 380px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    position: relative;
    transform: scale(0.9);
    transition: transform 0.3s ease;
}

.login-popup-overlay.show .login-popup-modal {
    transform: scale(1);
}

.login-popup-close {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid #252525;
    color: #aaa;
    font-size: 20px;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
    z-index: 10001;
}

.login-popup-close:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    border-color: #00bcd4;
    transform: rotate(90deg);
}

.login-popup-modal .input-group {
    margin-bottom: 15px;
    text-align: left;
}

.login-popup-modal .input-group label {
    display: block;
    font-size: 0.9rem;
    color: #aaa;
    margin-bottom: 8px;
}

.login-popup-modal .input-group input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #252525;
    border-radius: 12px;
    background: #1a1a1a;
    color: #fff;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s;
    box-sizing: border-box;
}

.login-popup-modal .input-group input:focus {
    outline: none;
    border-color: #00bcd4;
    box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
}

.login-popup-modal .btn-login {
    background: linear-gradient(135deg, #00bcd4, #007bff);
    border: none;
    padding: 14px 32px;
    border-radius: 50px;
    color: #fff;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    width: 100%;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
}

.login-popup-modal .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
}

.login-popup-modal .error {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 0.9rem;
    border: 1px solid #dc3545;
}

.login-popup-modal .toggle-buttons {
    display: flex;
    gap: 0;
    margin-bottom: 25px;
    border-radius: 50px;
    overflow: hidden;
    background: #1a1a1a;
    border: 1px solid #252525;
    padding: 4px;
}

.login-popup-modal .toggle-btn {
    flex: 1;
    padding: 10px 20px;
    border: none;
    background: transparent;
    color: #aaa;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    border-radius: 50px;
}

.login-popup-modal .toggle-btn:first-child {
    border-radius: 50px 0 0 50px;
}

.login-popup-modal .toggle-btn:last-child {
    border-radius: 0 50px 50px 0;
}

.login-popup-modal .toggle-btn:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #00bcd4;
}

.login-popup-modal .toggle-btn.active {
    background: linear-gradient(135deg, #00bcd4, #007bff);
    color: #fff;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
}

.login-popup-modal .forgot-link {
    text-align: right;
    margin-bottom: 15px;
}

.login-popup-modal .forgot-link a {
    color: #00bcd4;
    font-size: 0.9rem;
    text-decoration: none;
}

.login-popup-modal .forgot-link a:hover {
    text-decoration: underline;
}

.login-popup-modal .footer-link {
    margin-top: 15px;
    text-align: center;
    font-size: 0.9rem;
    color: #aaa;
}

.login-popup-modal .footer-link a {
    color: #00bcd4;
    text-decoration: none;
}

.login-popup-modal .footer-link a:hover {
    text-decoration: underline;
}

body.blurred {
    overflow: hidden;
}
</style>

<div class="login-popup-overlay" id="loginPopup">
    <div class="login-popup-modal">
        <button class="login-popup-close" onclick="closeLoginPopup()" title="Close">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="toggle-buttons">
            <button type="button" class="toggle-btn active" id="loginToggleBtn" onclick="showLoginForm()">Login</button>
            <button type="button" class="toggle-btn" id="registerToggleBtn" onclick="showRegisterForm()">Register</button>
        </div>
        
        <div id="loginFormContainer">
            <div id="loginError" class="error" style="display: none;"></div>
            <form id="loginPopupForm">
                <div class="input-group">
                    <label for="popupEmail">Email</label>
                    <input type="email" id="popupEmail" name="email" required>
                </div>
                <div class="input-group">
                    <label for="popupPassword">Password</label>
                    <input type="password" id="popupPassword" name="password" required>
                </div>
                <div class="forgot-link">
                    <a href="forgot-password.php">Forgot password?</a>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
        
        <div id="registerFormContainer" style="display: none;">
            <div id="registerError" class="error" style="display: none;"></div>
            <form id="registerPopupForm">
                <div class="input-group">
                    <label for="popupName">Full Name</label>
                    <input type="text" id="popupName" name="name" required>
                </div>
                <div class="input-group">
                    <label for="popupRegEmail">Email</label>
                    <input type="email" id="popupRegEmail" name="email" required>
                </div>
                <div class="input-group">
                    <label for="popupRegPassword">Password</label>
                    <input type="password" id="popupRegPassword" name="password" required minlength="8">
                </div>
                <div class="input-group">
                    <label for="popupConfirmPassword">Confirm Password</label>
                    <input type="password" id="popupConfirmPassword" name="confirm" required minlength="8">
                </div>
                <button type="submit" class="btn-login">Register</button>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const popup = document.getElementById('loginPopup');
    const form = document.getElementById('loginPopupForm');
    const errorDiv = document.getElementById('loginError');
    const loginFormContainer = document.getElementById('loginFormContainer');
    const registerFormContainer = document.getElementById('registerFormContainer');
    const registerForm = document.getElementById('registerPopupForm');
    const registerErrorDiv = document.getElementById('registerError');
    const loginToggleBtn = document.getElementById('loginToggleBtn');
    const registerToggleBtn = document.getElementById('registerToggleBtn');
    
    window.showLoginPopup = function() {
        popup.classList.add('show');
        document.body.classList.add('blurred');
        showLoginForm();
    };
    
    window.closeLoginPopup = function() {
        popup.classList.remove('show');
        document.body.classList.remove('blurred');
    };
    
    window.showLoginForm = function() {
        registerFormContainer.style.display = 'none';
        loginFormContainer.style.display = 'block';
        errorDiv.style.display = 'none';
        registerToggleBtn.classList.remove('active');
        loginToggleBtn.classList.add('active');
    };
    
    window.showRegisterForm = function() {
        loginFormContainer.style.display = 'none';
        registerFormContainer.style.display = 'block';
        registerErrorDiv.style.display = 'none';
        loginToggleBtn.classList.remove('active');
        registerToggleBtn.classList.add('active');
    };
    
    popup.addEventListener('click', function(e) {
        if (e.target === popup) {
            closeLoginPopup();
        }
    });
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        errorDiv.style.display = 'none';
        
        const email = document.getElementById('popupEmail').value;
        const password = document.getElementById('popupPassword').value;
        
        try {
            const response = await fetch('api/login_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.reload();
            } else {
                let errorMsg = 'Invalid email or password';
                if (data.message) {
                    errorMsg = data.message;
                } else if (data.error === 'INVALID_CREDENTIALS') {
                    errorMsg = 'Invalid email or password';
                } else if (data.error === 'EMAIL_NOT_VERIFIED') {
                    errorMsg = 'Please verify your email before logging in';
                } else if (data.error === 'RATE_LIMIT') {
                    errorMsg = 'Too many failed attempts. Try again in a few minutes.';
                }
                errorDiv.textContent = errorMsg;
                errorDiv.style.display = 'block';
            }
        } catch (error) {
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.style.display = 'block';
        }
    });
    
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        registerErrorDiv.style.display = 'none';
        
        const name = document.getElementById('popupName').value.trim();
        const email = document.getElementById('popupRegEmail').value;
        const password = document.getElementById('popupRegPassword').value;
        const confirm = document.getElementById('popupConfirmPassword').value;
        
        if (!name || !email || !password || !confirm) {
            registerErrorDiv.textContent = 'All fields are required!';
            registerErrorDiv.style.display = 'block';
            return;
        }
        
        if (password !== confirm) {
            registerErrorDiv.textContent = 'Passwords do not match!';
            registerErrorDiv.style.display = 'block';
            return;
        }
        
        if (password.length < 8) {
            registerErrorDiv.textContent = 'Password must be at least 8 characters.';
            registerErrorDiv.style.display = 'block';
            return;
        }
        
        try {
            const response = await fetch('api/register_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ name, email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                const emailParam = encodeURIComponent(email);
                const emailFailed = data.email_sent ? '' : '&email_failed=1';
                window.location.href = `verify-otp.php?email=${emailParam}&purpose=email_verification${emailFailed}`;
            } else {
                let errorMsg = 'Registration failed. Please try again.';
                if (data.message) {
                    errorMsg = data.message;
                } else if (data.error === 'EMAIL_EXISTS') {
                    errorMsg = 'Email already registered!';
                } else if (data.error === 'INVALID_INPUT') {
                    errorMsg = 'Please provide valid name, email and password (min 8 chars).';
                }
                registerErrorDiv.textContent = errorMsg;
                registerErrorDiv.style.display = 'block';
            }
        } catch (error) {
            registerErrorDiv.textContent = 'Network error. Please try again.';
            registerErrorDiv.style.display = 'block';
        }
    });
    
    // Handle profile icon click for login popup
    document.addEventListener('DOMContentLoaded', function() {
        const profileLoginBtn = document.getElementById('profileLoginBtn');
        if (profileLoginBtn) {
            profileLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (typeof window.showLoginPopup === 'function') {
                    window.showLoginPopup();
                } else {
                    const popup = document.getElementById('loginPopup');
                    if (popup) {
                        popup.classList.add('show');
                        document.body.classList.add('blurred');
                        if (typeof showLoginForm === 'function') {
                            showLoginForm();
                        }
                    } else {
                        window.location.href = 'login.php';
                    }
                }
                return false;
            });
        }
    });
})();
</script>
<?php endif; ?>

<script src="assets/js/navbar.js" defer></script>
<script src="assets/js/app.js" defer></script>
</body>
</html>
