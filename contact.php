<?php require __DIR__ . '/includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>Contact | Bottel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      background: #0a0a0a;
      color: #eee;
    }
    a { text-decoration: none; color: inherit; }

    /* Navbar */
    header {
      position: fixed; top: 0; left: 0; width: 100%;
      display: flex; justify-content: space-between; align-items: center;
      background: rgba(0,0,0,0.85);
      padding: 15px 8%;
      z-index: 1000;
      backdrop-filter: blur(8px);
    }
    .logo { font-size: 1.6rem; font-weight: 700; color: #00bcd4; }
    nav ul { list-style: none; display: flex; gap: 25px; }
    nav a:hover { color: #00bcd4; }
    .icons { display: flex; gap: 20px; font-size: 1.2rem; }

    /* Hero */
    .hero {
      height: 55vh;
      background: url('assets/images/contact-bg.jpg') center/cover no-repeat;
      display: flex; justify-content: center; align-items: center;
      position: relative; color: #fff;
      text-align: center;
    }
    .hero::after {
      content: "";
      position: absolute; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
    }
    .hero h1 {
      position: relative;
      font-size: 3rem;
      z-index: 2;
      background: linear-gradient(90deg,#00bcd4,#007bff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* Contact Section */
    .contact-container {
      max-width: 1100px;
      margin: 100px auto;
      padding: 0 20px;
      display: flex;
      gap: 50px;
      flex-wrap: wrap;
    }
    .contact-info {
      flex: 1 1 350px;
      background: #121212;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 0 20px rgba(0,188,212,0.1);
    }
    .contact-info h2 {
      color: #00bcd4;
      margin-bottom: 15px;
    }
    .contact-info p {
      color: #bbb;
      line-height: 1.8;
    }
    .contact-info i {
      color: #00bcd4;
      margin-right: 10px;
    }

    .contact-form {
      flex: 1 1 500px;
      background: #141414;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 0 15px rgba(0,188,212,0.1);
    }
    .contact-form h2 {
      color: #00bcd4;
      margin-bottom: 20px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border-radius: 10px;
      border: none;
      background: #1a1a1a;
      color: #fff;
    }
    .form-group textarea {
      height: 120px;
      resize: none;
    }
    .btn {
      background: linear-gradient(45deg, #00bcd4, #007bff);
      border: none;
      padding: 12px 25px;
      color: #fff;
      border-radius: 25px;
      cursor: pointer;
      font-size: 1rem;
      transition: 0.3s;
    }
    .btn:hover { transform: scale(1.05); }

    /* Map */
    .map {
      margin: 80px auto;
      width: 100%;
      max-width: 1100px;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0,188,212,0.1);
    }

    /* Footer */
    footer {
      background: #080808;
      padding: 60px 10% 30px;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
      gap: 25px;
    }
    .footer-grid h4 { color: #00bcd4; margin-bottom: 10px; }
    .social { display: flex; gap: 15px; margin-top: 10px; }
    .social a { color: #00bcd4; font-size: 1.3rem; }
    @media(max-width:768px){
      .contact-container { flex-direction: column; }
    }
  </style>
</head>
<body>

<header>
  <div class="logo">Bottel</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="category.php">Shop</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="contact.php" style="color:#00bcd4;">Contact</a></li>
    </ul>
  </nav>
  <div class="icons">
    <a href="user/cart.php"><i class="fas fa-shopping-cart"></i></a>
    <a href="login.php"><i class="fas fa-user"></i></a>
  </div>
</header>

<section class="hero">
  <h1>Contact Us</h1>
</section>

<section class="contact-container">
  <div class="contact-info">
    <h2>Get in Touch</h2>
    <p><i class="fas fa-map-marker-alt"></i> 45/7 Creative Park, Indore, India</p>
    <p><i class="fas fa-envelope"></i> support@bottel.com</p>
    <p><i class="fas fa-phone"></i> +91 98765 43210</p>
    <p>We’re here to help you design your perfect custom bottle experience for restaurants, hotels & corporate gifting.  
    Drop us a message and our team will connect within 24 hours.</p>
  </div>

  <div class="contact-form">
    <h2>Send Message</h2>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $name = $_POST['name'] ?? '';
      $email = $_POST['email'] ?? '';
      $message = $_POST['message'] ?? '';
      if ($name && $email && $message) {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $message]);
        echo "<p style='color:#00bcd4;'>Thank you! We’ll reach out soon.</p>";
      } else {
        echo "<p style='color:red;'>Please fill all fields.</p>";
      }
    }
    ?>
    <form method="POST" action="">
      <div class="form-group">
        <input type="text" name="name" placeholder="Your Name" required>
      </div>
      <div class="form-group">
        <input type="email" name="email" placeholder="Your Email" required>
      </div>
      <div class="form-group">
        <textarea name="message" placeholder="Your Message..." required></textarea>
      </div>
      <button type="submit" class="btn">Send Message</button>
    </form>
  </div>
</section>

<div class="map">
  <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3680.289802836553!2d75.8577258750727!3d22.719568079403187!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3962fdcd5ba9c7e1%3A0x8b5820f0abcbca2c!2sIndore%2C%20Madhya%20Pradesh!5e0!3m2!1sen!2sin!4v1709554787365!5m2!1sen!2sin"
    width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
</div>

<footer>
  <div class="footer-grid">
    <div>
      <h4>About Bottel</h4>
      <p>We craft personalized premium water bottles for restaurants & events across India.</p>
    </div>
    <div>
      <h4>Quick Links</h4>
      <p><a href="category.php">Shop</a></p>
      <p><a href="about.php">About</a></p>
      <p><a href="contact.php">Contact</a></p>
    </div>
    <div>
      <h4>Support</h4>
      <p>Email: support@bottel.com</p>
      <p>Phone: +91 98765 43210</p>
    </div>
    <div>
      <h4>Follow Us</h4>
      <div class="social">
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
      </div>
    </div>
  </div>
  <p style="text-align:center;margin-top:20px;color:#666;">© 2025 Bottel. All rights reserved.</p>
</footer>

</body>
<script src="assets/js/app.js" defer></script>
</html>
