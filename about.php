<?php 
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;
$currentPage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>About Us | Bottle</title>
  
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
      /* Deep dark background with a subtle central glow for depth */
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
      background: url('assets/images/about-bg.jpg') center/cover no-repeat;
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
      animation: fadeIn Up 1s ease;
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    /* --- ABOUT SECTION --- */
    .about-wrapper {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }

    .about-media {
      position: relative;
    }

    .about-media video, 
    .about-media img {
      width: 100%;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.4);
      border: 1px solid rgba(255,255,255,0.1);
      position: relative;
      z-index: 2;
    }

    .about-media::after {
      content: '';
      position: absolute;
      top: 10%;
      left: 10%;
      width: 80%;
      height: 80%;
      background: #00bcd4;
      filter: blur(80px);
      opacity: 0.15;
      z-index: 1;
    }

    /* --- SKILLS & STATS (BASE) --- */
    .skills-section {
      background: #101010;
      border-top: 1px solid #1f1f1f;
      border-bottom: 1px solid #1f1f1f;
      position: relative;
    }

    .skills-wrapper {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 70px;
    }

    .skill-item { margin-bottom: 30px; }
    
    .skill-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      font-weight: 500;
      font-size: 0.95rem;
    }

    .progress-track {
      width: 100%;
      height: 8px;
      background: #222;
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #00bcd4, #007bff);
      border-radius: 4px;
      width: 0; 
      transition: width 1.5s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 25px;
    }

    .stat-card {
      background: #161616;
      padding: 30px 20px;
      border-radius: 16px;
      text-align: center;
      border: 1px solid #252525;
      transition: 0.3s;
      position: relative;
      overflow: hidden;
    }

    .stat-card:hover {
      border-color: #00bcd4;
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .stat-number {
      display: block;
      font-size: 2.8rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 5px;
    }

    .stat-label {
      color: #888;
      font-size: 0.9rem;
    }

    /* --- SKILLS SECTION (COMPACT VIEW OVERRIDES) --- */
    /* Applies when "compact-view" class is added to section */
    .skills-section.compact-view {
      padding: 50px 0;
    }
    
    .skills-section.compact-view .skills-wrapper {
      gap: 40px; 
      align-items: center; 
    }

    .skills-section.compact-view .section-title {
      font-size: 2rem;
      margin-bottom: 15px;
    }
    
    .skills-section.compact-view .skill-item {
      margin-bottom: 18px; 
    }

    .skills-section.compact-view .progress-track {
      height: 6px; /* Sleeker bars */
    }

    .skills-section.compact-view .stat-card {
      padding: 15px; 
    }

    .skills-section.compact-view .stat-number {
      font-size: 2rem;
      margin-bottom: 0;
    }

    .skills-section.compact-view .stat-label {
      font-size: 0.8rem; 
    }

    /* --- CTA SECTION --- */
    .cta-section {
      position: relative;
      text-align: center;
      background: url('assets/images/about-bg.jpg') center/cover no-repeat fixed;
    }

    .cta-overlay {
      background: rgba(0,0,0,0.8);
      padding: 100px 0;
    }

    .cta-content {
      max-width: 700px;
      margin: 0 auto;
    }

    .btn-light {
      background: #fff;
      color: #000;
    }
    .btn-light:hover {
      background: #f0f0f0;
      color: #00bcd4;
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
      .about-wrapper, .skills-wrapper { grid-template-columns: 1fr; gap: 40px; }
      .hero h1 { font-size: 2.5rem; }
    }

    @media (max-width: 768px) {
      .section-padding { padding: 60px 0; }
      .stats-grid { grid-template-columns: 1fr; }
      .section-title { font-size: 2rem; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="hero">
  <div class="hero-content">
    <h1><span class="text-highlight">About Us</span></h1>
    <p style="color: #ccc; font-size: 1.1rem; margin-top: 10px;">Crafting perfection, one bottle at a time.</p>
  </div>
</section>

<section class="section-padding">
  <div class="container">
    <div class="about-wrapper">
      
      <div class="about-media">
        <video autoplay muted loop playsinline>
          <source src="assets/videos/about.video.mp4" type="video/mp4">
          <img src="assets/images/about-bg.jpg" alt="About Bottle">
        </video>
      </div>

  <div class="about-text">
        <span class="section-label">Who We Are</span>
        <h2 class="section-title">We Always Make <br>The <span class="text-highlight">Best For You</span></h2>
        
        <p class="section-desc">
          <strong>Bottle</strong> is India's leading custom water bottle brand that empowers restaurants, hotels, and events to showcase their brand identity through personalized premium bottles.
        </p>
        <p class="section-desc">
          From minimal matte finishes to luxurious engraved designs, we ensure every drop you serve feels royal. We're passionate about sustainable design, precision printing, and flawless packaging that represents your name with pride.
        </p>
        
        <div style="margin-top: 30px;">
          <a href="contact.php" class="btn">Get in Touch</a>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="skills-section compact-view">
  <div class="container">
    <div class="skills-wrapper">
      
      <div>
        <span class="section-label">Our Expertise</span>
        <h2 class="section-title">Driven by <span class="text-highlight">Quality</span></h2>
        <p class="section-desc" style="margin-bottom: 20px; font-size: 0.95rem;">
          Combining technology with craftsmanship for exceptional results.
        </p>
        
        <div class="skill-item">
          <div class="skill-header">
            <span>Custom Design</span>
            <span style="color:#00bcd4">95%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" data-width="95"></div>
          </div>
        </div>
        
        <div class="skill-item">
          <div class="skill-header">
            <span>Manufacturing</span>
            <span style="color:#00bcd4">98%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" data-width="98"></div>
          </div>
        </div>
        
        <div class="skill-item">
          <div class="skill-header">
            <span>Satisfaction</span>
            <span style="color:#00bcd4">92%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" data-width="92"></div>
          </div>
        </div>

        <div class="skill-item">
          <div class="skill-header">
            <span>Eco-Friendly</span>
            <span style="color:#00bcd4">100%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" data-width="100"></div>
          </div>
        </div>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-number" data-count="5">0</span>
          <span class="stat-label">Years Exp.</span>
        </div>
        <div class="stat-card">
          <span class="stat-number" data-count="50000">0</span>
          <span class="stat-label">Delivered</span>
        </div>
        <div class="stat-card">
          <span class="stat-number" data-count="120">0</span>
          <span class="stat-label">Partners</span>
        </div>
        <div class="stat-card">
          <span class="stat-number" data-count="15">0</span>
          <span class="stat-label">Cities</span>
  </div>
  </div>

  </div>
  </div>
</section>

<section class="cta-section">
  <div class="cta-overlay">
    <div class="container">
      <div class="cta-content">
        <span class="section-label">Hire Us Now</span>
        <h2 class="section-title">Ready to Elevate Your Brand?</h2>
        <p class="section-desc">Transform your brand identity with our premium custom bottle solutions. Let's create something extraordinary together.</p>
        <a href="category.php" class="btn btn-light">View Catalog</a>
      </div>
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

<script>
  document.addEventListener('DOMContentLoaded', () => {
    
    // Observer for Progress Bars
    const progressObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const bar = entry.target;
          bar.style.width = bar.getAttribute('data-width') + '%';
          progressObserver.unobserve(bar);
        }
      });
    }, { threshold: 0.5 });

    document.querySelectorAll('.progress-fill').forEach(bar => progressObserver.observe(bar));

    // Observer for Number Counters
    const countObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const counter = entry.target;
          const target = +counter.getAttribute('data-count');
          const duration = 2000; // 2 seconds
          const inc = target / (duration / 16); 
          let count = 0;

          const updateCount = () => {
            count += inc;
            if (count < target) {
              counter.innerText = Math.ceil(count).toLocaleString();
              requestAnimationFrame(updateCount);
            } else {
              counter.innerText = target.toLocaleString();
            }
          };

          updateCount();
          countObserver.unobserve(counter);
        }
      });
    }, { threshold: 0.5 });

    document.querySelectorAll('.stat-number').forEach(num => countObserver.observe(num));
  });
</script>

<?php if (!$isLoggedIn): ?>
<script>
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
                // Fallback: try to show popup directly
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
</script>
<?php endif; ?>

<script src="assets/js/navbar.js" defer></script>
<script src="assets/js/app.js" defer></script>
</body>
</html>