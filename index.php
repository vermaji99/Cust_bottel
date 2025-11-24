<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <title>Bottel | Custom Branded Water Bottles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"> 

    <style>
    /* === Base === */
    body {
        font-family: "Poppins", sans-serif;
        margin: 0;
        background-color: #292727ff;
        color: #f0f0f0;
    }
    a { text-decoration: none; color: inherit; }
    img { max-width: 100%; display: block; }
    h2 { font-weight: 600; text-align: center; }

    /* === Navbar === */
    header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 8%;
        z-index: 1000;
        backdrop-filter: blur(10px);
        box-sizing: border-box;
        transition: background-color 0.3s, box-shadow 0.3s; /* Added transition */
    }

    /* New style for scrolled header */
    header.scrolled {
        background-color: rgba(0, 0, 0, 0.95);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    .logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: #00bcd4;
    }

    nav ul {
        list-style: none;
        display: flex;
        gap: 25px;
    }

    nav a {
        color: #fff;
        font-weight: 500;
        transition: 0.3s;
    }

    nav a:hover {
        color: #00bcd4;
    }

    .icons {
        display: flex;
        align-items: center;
        gap: 20px;
        font-size: 1.2rem;
        color: #fff;
        position: relative;
        z-index: 1100;
        padding-right:10px;
    }

    .menu-toggle {
        display: none;
        cursor: pointer;
        color: #fff;
        font-size: 1.7rem;
        z-index: 1101;
        transition: transform 0.3s ease;
    }

    .menu-toggle:hover {
        transform: scale(1.1);
    }

    /* --- Responsive Navbar --- */
    @media (max-width: 992px) {
        header {
            padding: 12px 5%;
        }

        .menu-toggle {
            display: block;
        }

        nav {
            position: fixed;
            top: 70px;
            right: -100%;
            width: 240px;
            height: calc(100vh - 70px);
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(15px);
            border-left: 2px solid rgba(0, 188, 212, 0.3);
            transition: right 0.4s ease;
            z-index: 1000;
        }

        nav.active {
            right: 0;
        }

        nav ul {
            flex-direction: column;
            gap: 25px;
            padding: 60px 30px;
        }

        .icons {
            gap: 15px;
        }
    }

    /* Extra fix for very small phones */
    @media (max-width: 480px) {
        header {
            padding: 10px 20px;
        }
        .logo {
            font-size: 1.3rem;
        }
        .icons {
            gap: 12px;
        }
    }

    /* === Hero Slideshow === */
    .hero {
        position: relative;
        height: 100vh;
        overflow: hidden;
    }
    .slide {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: top;
        opacity: 0;
        transition: opacity 1s ease;
    }
    .slide.active { opacity: 1; }
    .hero-content {
        position: absolute;
        inset: 0;
        background: transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 0 10%;
    }
    .hero-content h1 {
        font-size: 2.8rem;
        color: #fff;
        margin-bottom: 1rem;
    }
    .hero-content p { max-width: 700px; color: #ccc; margin-bottom: 1.5rem; }
    .btn {
        background: linear-gradient(45deg, #00bcd4, #007bff);
        padding: 10px 20px;
        border-radius: 30px;
        color: #fff;
        transition: 0.3s;
        display: inline-block;
        margin:5px;
    }
    .btn:hover { transform: scale(1.05); }

    /* Dots */
    .dots {
        position: absolute;
        bottom: 25px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
    }
    .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #666;
        cursor: pointer;
    }
    .dot.active { background: #00bcd4; }

    /* === Sections === */
    section { padding: 80px 10%; }
    .why {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
        justify-content: center;
    }
    .why .card {
        background: #1a1a1a;
        padding: 30px;
        width: 280px;
        border-radius: 16px;
        text-align: center;
    }
    .why i { font-size: 2rem; color: #00bcd4; }

    /* === Products === */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
        margin-top: 40px;
    }
    .product-card {
        background: #141414;
        border-radius: 12px;
        padding-bottom: 15px;
        transition: transform 0.3s;
    }
    .product-card:hover { transform: translateY(-5px); }
    .product-card img { border-radius: 12px 12px 0 0; }
    .product-card h3 { text-align: center; margin: 10px 0; }
    .price { color: #00bcd4; text-align: center; margin-bottom: 10px; }

    /* === Custom Banner === */
    .custom-banner {
        background: linear-gradient(145deg, #0d0d0d, #161616);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        border-radius: 24px;
        overflow: hidden;
        padding: 60px 80px;
        gap: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .custom-banner .text {
        flex: 1;
        min-width: 300px;
        padding: 20px 40px;
        color: #fff;
    }

    .custom-banner h2 {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 20px;
        line-height: 1.3;
    }

    .custom-banner h2 span {
        color: #00bcd4;
    }

    .custom-banner p {
        font-size: 1.1rem;
        line-height: 1.7;
        color: #ccc;
        max-width: 550px;
    }

    .custom-banner img {
        width: 420px;
        max-width: 100%;
        border-radius: 16px;
        object-fit: cover;
        filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.4));
    }

    @media (max-width: 992px) {
        .custom-banner {
            flex-direction: column;
            text-align: center;
            padding: 50px 30px;
        }
        .custom-banner .text {
            padding: 20px 0;
        }
        .custom-banner img {
            width: 80%;
            margin-top: 20px;
        }
    }

    /* === Testimonials === */
    .testimonials { text-align: center; }
    .testimonial-grid {
        display: grid;
        gap: 25px;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        margin-top: 40px;
    }
    .testimonial {
        background: #1a1a1a;
        padding: 25px;
        border-radius: 12px;
        font-style: italic;
    }
    .testimonial h4 { margin-top: 10px; color: #00bcd4; }

    /* === Newsletter === */
    .newsletter {
        background: linear-gradient(45deg, #007bff, #00bcd4);
        padding: 60px 10%;
        text-align: center;
        border-radius: 16px;
        color: #fff;
    }
    .newsletter input {
        padding: 10px 15px;
        border: none;
        border-radius: 30px;
        width: 250px;
        margin-right: 10px;
    }
    .newsletter button {
        padding: 10px 25px;
        border: none;
        border-radius: 30px;
        background: #000;
        color: #00bcd4;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .newsletter button:hover {
        background: #333;
    }

    /* === Footer === */
    footer {
        background: #080808;
        padding: 60px 10% 30px;
    }
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 25px;
    }
    .footer-grid h4 { color: #00bcd4; margin-bottom: 10px; }
    .social { display: flex; gap: 15px; margin-top: 10px; }
    .social a { color: #00bcd4; font-size: 1.3rem; transition: color 0.3s; }
    .social a:hover { color: #007bff; }

    @media(max-width:768px){
        .custom-banner { flex-direction: column; text-align: center; }
        .custom-banner img { width: 80%; margin-top: 20px; }
    }

    /* === Back to Top Button === */
    #back-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #00bcd4;
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: none; /* Hidden by default */
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: 0.8;
        transition: opacity 0.3s, transform 0.3s;
        z-index: 999;
        border: none;
    }
    #back-to-top:hover {
        opacity: 1;
        transform: translateY(-2px);
    }
</style>

</head>

<body>
<header id="main-header">
    <div class="logo">Bottel</div>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="category.php">Shop</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="user/dashboard.php">Dashboard</a></li>
                <li><a href="user/orders.php">Orders</a></li>
                <li><a href="user/wishlist.php">Wishlist</a></li>
                <li><a href="user/profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
           
        </ul>
    </nav>
    <div class="icons">
        <a href="user/cart.php"><i class="fas fa-shopping-cart"></i></a>
        <a href="<?php echo $isLoggedIn ? 'user/profile.php' : 'login.php'; ?>"><i class="fas fa-user"></i></a>
        <div class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>

<section class="hero">
    <div class="slide active" style="background-image:url('assets/images/hero1.png');"></div>
    <div class="slide" style="background-image:url('assets/images/hero2.png');"></div>
    <div class="slide" style="background-image:url('assets/images/hero3.png');"></div>

    <div class="hero-content">
        <h1>Make Your Brand Shine with Every Bottle</h1>
        <p>We design & deliver personalized water bottles for restaurants, cafés and premium events.</p>
        <a href="category.php" class="btn">Shop Now</a>
    </div>
    <div class="dots">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
</section>

<section>
    <h2 data-aos="fade-up">Why Choose <span style="color:#00bcd4;">Bottel</span></h2>
    <div class="why">
        <div class="card" data-aos="fade-up" data-aos-delay="100"><i class="fas fa-tint"></i><h3>Pure Hydration</h3><p>Eco-friendly & safe water bottles built for elegance.</p></div>
        <div class="card" data-aos="fade-up" data-aos-delay="200"><i class="fas fa-brush"></i><h3>Custom Labeling</h3><p>Add your café or restaurant logo effortlessly.</p></div>
        <div class="card" data-aos="fade-up" data-aos-delay="300"><i class="fas fa-truck"></i><h3>Fast Shipping</h3><p>Nationwide express delivery within days.</p></div>
        <div class="card" data-aos="fade-up" data-aos-delay="400"><i class="fas fa-crown"></i><h3>Royal Quality</h3><p>Designed to match your premium brand identity.</p></div>
    </div>
</section>

<section>
    <h2 data-aos="fade-up">Featured Bottles</h2>
    <div class="products-grid">
        <?php
        // Prepare to use PDO (assuming config.php sets up a $pdo object)
        try {
            // Using ORDER BY RAND() for a dynamic/random selection on each page load
            $stmt = $pdo->query("SELECT * FROM products WHERE is_active=1 ORDER BY RAND() LIMIT 6");
            $delay = 0;
            while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "
                <div class='product-card' data-aos='zoom-in' data-aos-delay='{$delay}'>
                    <img src='admin/uploads/{$p['image']}' alt='{$p['name']}'>
                    <h3>{$p['name']}</h3>
                    <p class='price'>₹{$p['price']}</p>
                    <div style='text-align:center;'>
                        <a href='product.php?id={$p['id']}' class='btn'>Details</a>
                        <a href='customize.php?id={$p['id']}' class='btn'>Customize</a>
                        <a href='#' class='btn' data-wishlist-add='{$p['id']}' title='Add to wishlist'><i class=\"fas fa-heart\"></i></a>
                    </div>
                </div>";
                $delay += 100; // Increase delay for staggered animation
            }
        } catch (PDOException $e) {
            echo "<p style='text-align:center; color:red;'>Database Error: Failed to load products.</p>";
        }
        ?>
    </div>
</section>

<section class="custom-banner" data-aos="fade-right">
    <div class="text">
        <h2>Design <span>Your Own</span> Bottle</h2>
        <p>Upload your restaurant’s logo and choose your bottle color & packaging.
        We print it for you and ship within 48 hours.</p>
        <a href="customize.php" class="btn">Start Customizing</a>
    </div>
    <img src="assets/images/custom-bottle.jpg" alt="Custom Bottle" data-aos="fade-left">
</section>

<section class="testimonials">
    <h2 data-aos="fade-up">What Our Partners Say</h2>
    <div class="testimonial-grid">
        <?php
        // Sample testimonials - replace with your actual DB query logic
        $testimonials_data = [
            ["quote" => "We love Bottel’s quality and quick turnaround. Our customers notice the branding.", "author" => "Café Blue"],
            ["quote" => "These bottles elevate our restaurant’s image instantly!", "author" => "Urban Dine"],
            ["quote" => "Amazing customization options and durable packaging.", "author" => "Aqua Events"],
            ["quote" => "The service was prompt and the bottles exceeded our expectations. Highly recommend!", "author" => "The Luxe Hotel"],
        ];
        
        // Randomize the array of testimonials for dynamic display on each page load
        shuffle($testimonials_data);
        
        $delay = 0;
        foreach (array_slice($testimonials_data, 0, 3) as $t) { // Show up to 3 random testimonials
            echo "<div class='testimonial' data-aos='flip-up' data-aos-delay='{$delay}'>
                    “{$t['quote']}”
                    <h4>— {$t['author']}</h4>
                  </div>";
            $delay += 100;
        }
        ?>
    </div>
</section>

<section class="newsletter" data-aos="zoom-in">
    <h2>Stay Hydrated with Updates 💧</h2>
    <p>Subscribe for discounts & design inspiration.</p>
    <form action="subscribe.php" method="POST"> <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Subscribe</button>
    </form>
</section>

<footer>
    <div class="footer-grid">
        <div data-aos="fade-right">
            <h4>About Bottel</h4>
            <p>We craft personalized premium water bottles for restaurants & events across India.</p>
        </div>
        <div data-aos="fade-up" data-aos-delay="100">
            <h4>Quick Links</h4>
            <p><a href="category.php">Shop</a></p>
            <p><a href="about.php">About</a></p>
            <p><a href="contact.php">Contact</a></p>
        </div>
        <div data-aos="fade-up" data-aos-delay="200">
            <h4>Support</h4>
            <p>Email: support@bottel.com</p>
            <p>Phone: +91 98765 43210</p>
        </div>
        <div data-aos="fade-left">
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

<button id="back-to-top" title="Go to top"><i class="fas fa-arrow-up"></i></button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="assets/js/app.js" defer></script>

<script>
// Initialize AOS (Animate On Scroll)
AOS.init({
    duration: 800, // Duration of animation (in ms)
    once: true,    // Only animate once
});

const menuToggle = document.getElementById('menu-toggle');
const nav = document.querySelector('nav');
const header = document.getElementById('main-header');
const backToTopButton = document.getElementById('back-to-top');

// 1. Mobile Menu Toggle
menuToggle.addEventListener('click', () => {
    nav.classList.toggle('active');
    // Toggle the icon for visual feedback
    const icon = menuToggle.querySelector('i');
    icon.classList.toggle('fa-bars');
    icon.classList.toggle('fa-xmark');
});

// 2. Header Scroll Effect & Back to Top Button Logic
window.addEventListener('scroll', () => {
    // Header Scroll Effect
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }

    // Back to Top Button Visibility
    if (window.scrollY > 300) {
        backToTopButton.style.display = 'flex'; // Show button
    } else {
        backToTopButton.style.display = 'none'; // Hide button
    }
});

// Back to Top Button Click
backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth' // Smooth scroll to the top
    });
});

// 3. Simple Slideshow Logic
let slides = document.querySelectorAll(".slide");
let dots = document.querySelectorAll(".dot");
let index = 0;

function showSlide(i) {
    slides.forEach((s, n) => s.classList.toggle("active", n === i));
    dots.forEach((d, n) => d.classList.toggle("active", n === i));
}

// Manual dot navigation
dots.forEach((d, i) => d.addEventListener("click", () => {
    index = i;
    showSlide(index);
}));

// Auto-advance slideshow
setInterval(() => {
    index = (index + 1) % slides.length;
    showSlide(index);
}, 5000); // Change slide every 5 seconds (5000ms)
</script>
</body>
</html>