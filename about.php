<?php require __DIR__ . '/includes/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>About | Bottel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      background: #0b0b0b;
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

    /* Hero Section */
    .hero {
      background: url('assets/images/about-bg.jpg') center/cover no-repeat;
      height: 60vh;
      display: flex; align-items: center; justify-content: center;
      text-align: center;
      color: #fff;
      position: relative;
    }
    .hero::after {
      content: "";
      position: absolute; top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
    }
    .hero-content {
      position: relative;
      z-index: 2;
    }
    .hero-content h1 {
      font-size: 3rem;
      margin-bottom: 10px;
      background: linear-gradient(90deg, #00bcd4, #007bff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .hero-content p { font-size: 1.2rem; color: #ccc; }

    /* About Content */
    .about-section {
      max-width: 1100px;
      margin: 100px auto;
      padding: 0 20px;
      display: flex;
      gap: 40px;
      align-items: center;
      flex-wrap: wrap;
    }
    .about-section img {
      flex: 1 1 400px;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,188,212,0.2);
    }
    .about-text {
      flex: 1 1 400px;
    }
    .about-text h2 {
      font-size: 2rem;
      color: #00bcd4;
      margin-bottom: 15px;
    }
    .about-text p {
      color: #bbb;
      line-height: 1.8;
      margin-bottom: 15px;
    }

    /* Stats */
    .stats {
      display: flex;
      justify-content: space-around;
      background: #111;
      padding: 60px 10%;
      border-top: 1px solid #222;
      border-bottom: 1px solid #222;
      flex-wrap: wrap;
      gap: 30px;
    }
    .stat {
      text-align: center;
    }
    .stat h3 {
      font-size: 2rem;
      color: #00bcd4;
      margin-bottom: 8px;
    }
    .stat p { color: #aaa; }

    /* Mission Section */
    .mission {
      max-width: 1000px;
      margin: 80px auto;
      text-align: center;
      padding: 0 20px;
    }
    .mission h2 {
      color: #00bcd4;
      margin-bottom: 15px;
    }
    .mission p {
      color: #bbb;
      line-height: 1.8;
      max-width: 800px;
      margin: 0 auto;
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
      .about-section { flex-direction: column; text-align: center; }
      .about-section img { width: 90%; }
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
      <li><a href="about.php" style="color:#00bcd4;">About</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
  </nav>
  <div class="icons">
    <a href="user/cart.php"><i class="fas fa-shopping-cart"></i></a>
    <a href="login.php"><i class="fas fa-user"></i></a>
  </div>
</header>

<section class="hero">
  <div class="hero-content">
    <h1>About Bottel</h1>
    <p>Where personalization meets hydration</p>
  </div>
</section>

<section class="about-section">
  <img src="assets/images/about1.jpg" alt="Custom Bottles">
  <div class="about-text">
    <h2>Who We Are</h2>
    <p><strong>Bottel</strong> is India’s leading custom water bottle brand that empowers restaurants, hotels, and events to showcase their brand identity through personalized premium bottles.</p>
    <p>From minimal matte finishes to luxurious engraved designs, we ensure every drop you serve feels royal — because presentation matters.</p>
    <p>We’re passionate about sustainable design, precision printing, and flawless packaging that represents your name with pride.</p>
  </div>
</section>

<section class="stats">
  <div class="stat">
    <h3>120+</h3>
    <p>Partner Restaurants</p>
  </div>
  <div class="stat">
    <h3>50K+</h3>
    <p>Custom Bottles Delivered</p>
  </div>
  <div class="stat">
    <h3>15+</h3>
    <p>City Presence</p>
  </div>
  <div class="stat">
    <h3>100%</h3>
    <p>Eco-Friendly Materials</p>
  </div>
</section>

<section class="mission">
  <h2>Our Mission</h2>
  <p>To redefine how hospitality brands present their signature touch — transforming a simple bottle of water into a symbol of elegance, care, and identity.  
  We believe every restaurant deserves to serve with pride, and every guest deserves a memorable experience.</p>
</section>

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
