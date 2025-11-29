<?php
// PHP logic remains the same
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$productId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Fetch product if ID provided
$product = null;
if ($productId) {
    try {
        // Assume db() is a function that returns a PDO instance
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error silently
    }
}

$productImage = $product ? 'admin/uploads/' . $product['image'] : null;

// Mock functions for demonstration if not defined in bootstrap.php
if (!function_exists('esc')) {
    function esc($string) { return htmlspecialchars($string, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('app_config')) {
    function app_config($key) { return $key === 'app_url' ? '/' : ''; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <title>3D Bottle Customizer | Luxury Edition</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    
    <style>
        /* CSS reset and basic styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        :root {
            /* Dark + Blue Theme Colors */
            --color-background: #0b0b0b;
            --color-surface: #161616;
            --color-primary: #00bcd4;
            --color-primary-dark: #007bff;
            --color-secondary: #1a1a1a;
            --color-text-light: #e0e0e0;
            --color-text-muted: #a0a0a0;
            --border-glow: 1px solid rgba(0, 188, 212, 0.3);
            --box-shadow-premium: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 188, 212, 0.15);
            --transition-speed: 0.3s;
        }
        
        body {
            margin: 0;
            padding: 0;
            padding-top: 70px;
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at 50% 0%, #1a1f25 0%, #0b0b0b 60%);
            color: var(--color-text-light);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* --- Header Styling --- */
        .header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.4rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00bcd4, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        
        .header p {
            color: var(--color-text-muted);
            font-size: 1rem;
            font-weight: 300;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.9rem;
            }
            .header p {
                font-size: 0.95rem;
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--color-primary);
            text-decoration: none;
            margin-bottom: 30px;
            font-weight: 500;
            transition: all var(--transition-speed);
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(0, 188, 212, 0.1);
            border: 1px solid rgba(0, 188, 212, 0.2);
        }
        
        .back-link:hover {
            color: #fff;
            transform: translateX(-5px);
            background: rgba(0, 188, 212, 0.2);
            border-color: var(--color-primary);
        }
        
        /* --- Main Content Layout --- */
        .main-content {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 30px;
            animation: fadeInUp 1s ease-out 0.3s both;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .main-content > div:last-child {
            animation: fadeInUp 1s ease-out 0.5s both;
        }
        
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr 380px;
                gap: 25px;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }
        
        /* --- 3D Container Styling --- */
        #bottle3d-container {
            width: 100%;
            min-height: 550px;
            max-height: 600px;
            background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
            border-radius: 20px;
            overflow: hidden;
            border: var(--border-glow);
            box-shadow: var(--box-shadow-premium);
            transition: all var(--transition-speed);
            position: relative;
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        
        #bottle3d-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(0, 188, 212, 0.03) 0%, transparent 70%);
            pointer-events: none;
        }
        
        #bottle3d-container:hover {
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.7), 0 0 30px rgba(0, 188, 212, 0.25);
            transform: translateY(-3px);
            border-color: rgba(0, 188, 212, 0.5);
        }

        /* Responsive height adjustment for 3D view */
        @media (max-width: 992px) {
            #bottle3d-container {
                min-height: 500px;
                max-height: 550px;
            }
        }
        
        @media (max-width: 768px) {
            #bottle3d-container {
                min-height: 400px;
                max-height: 450px;
            }
        }
        
        /* --- Info/Control Card Styling --- */
        .info-card {
            background: var(--color-surface);
            border-radius: 20px;
            padding: 28px;
            border: var(--border-glow);
            box-shadow: var(--box-shadow-premium);
            margin-bottom: 20px;
            transition: all var(--transition-speed);
            position: relative;
            overflow: hidden;
        }
        
        .info-card:first-of-type {
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        .info-card:last-of-type {
            animation: fadeInUp 1s ease-out 0.8s both;
        }
        
        @media (max-width: 768px) {
            .info-card {
                padding: 22px;
            }
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00bcd4, #007bff);
            transform: scaleX(0);
            transition: transform var(--transition-speed);
        }
        
        .info-card:hover::before {
            transform: scaleX(1);
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6), 0 0 25px rgba(0, 188, 212, 0.2);
            border-color: rgba(0, 188, 212, 0.5);
        }
        
        .info-card h3 {
            color: var(--color-primary);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.3rem;
            font-weight: 600;
            border-bottom: 1px solid rgba(0, 188, 212, 0.15);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card h3 i {
            font-size: 1.2rem;
        }
        
        /* --- Product Info Block --- */
        .info-card .product-info {
            padding: 25px;
            background: linear-gradient(135deg, rgba(0, 188, 212, 0.1) 0%, rgba(0, 123, 255, 0.1) 100%);
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 4px solid var(--color-primary);
            transition: all var(--transition-speed);
        }
        
        .info-card .product-info:hover {
            background: linear-gradient(135deg, rgba(0, 188, 212, 0.15) 0%, rgba(0, 123, 255, 0.15) 100%);
            transform: translateX(5px);
        }
        
        .info-card .product-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--color-text-light);
            margin-bottom: 8px;
        }
        
        .info-card .product-price {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00bcd4, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }
        
        /* --- Buttons (CTA and Action) --- */
        .btn {
            display: block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #00bcd4, #007bff);
            color: #fff;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--transition-speed);
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 188, 212, 0.5);
            filter: brightness(1.1);
        }
        
        .btn.secondary {
            background: transparent;
            border: 2px solid var(--color-primary);
            color: var(--color-primary);
            box-shadow: none;
        }
        
        .btn.secondary:hover {
            background: rgba(0, 188, 212, 0.1);
            color: #fff;
            border-color: var(--color-primary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
        }

        /* Camera View Buttons */
        .camera-view-btn {
            flex: 1; 
            padding: 12px; 
            background: var(--color-secondary); 
            border: 1px solid rgba(168, 121, 50, 0.2); 
            color: var(--color-text-light); 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 500;
            transition: all var(--transition-speed);
        }

        .camera-view-btn:hover {
            background: rgba(0, 188, 212, 0.15);
            color: var(--color-primary);
            border-color: rgba(0, 188, 212, 0.4);
        }
        
        .camera-view-btn.active {
            background: linear-gradient(135deg, #00bcd4, #007bff) !important;
            border-color: var(--color-primary) !important;
            color: #fff !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
        }

        /* Action Buttons (Reset, Download) */
        .action-btn {
            width: 100%; 
            padding: 12px; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: all var(--transition-speed);
        }

        #resetRotation {
            background: var(--color-secondary); 
            border: 1px solid rgba(0, 188, 212, 0.3); 
            color: var(--color-primary);
        }

        #resetRotation:hover {
            background: rgba(0, 188, 212, 0.1);
            color: #fff;
            border-color: var(--color-primary);
        }

        #downloadPreview {
            background: linear-gradient(135deg, #00bcd4, #007bff);
            border: none;
            color: #fff;
        }

        #downloadPreview:hover {
            background: linear-gradient(135deg, #00d4e6, #0088ff);
            box-shadow: 0 5px 20px rgba(0, 188, 212, 0.5);
            transform: translateY(-2px);
        }

        /* --- Controls Group Styling --- */
        .control-group {
            margin-bottom: 22px;
        }
        
        .control-group:last-child {
            margin-bottom: 0;
        }

        .control-group label {
            display: block; 
            color: var(--color-text-light); 
            margin-bottom: 10px; 
            font-weight: 600;
            font-size: 1rem;
        }

        .control-group small {
            color: var(--color-text-muted); 
            display: block; 
            margin-top: 10px; 
            line-height: 1.5;
            font-size: 0.9rem;
        }
        
        /* Label Upload Input */
        #labelUpload {
            width: 100%; 
            padding: 15px; 
            border-radius: 10px; 
            border: 3px dashed rgba(0, 188, 212, 0.4); 
            background: rgba(0, 0, 0, 0.3); 
            color: #fff; 
            cursor: pointer;
            transition: all var(--transition-speed);
        }

        #labelUpload::-webkit-file-upload-button {
            background: linear-gradient(135deg, #00bcd4, #007bff);
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 15px;
            transition: all var(--transition-speed);
        }

        #labelUpload:hover {
            border-color: var(--color-primary) !important;
            background: rgba(0, 188, 212, 0.1) !important;
        }
        
        #uploadProgress {
            animation: pulse 2s infinite;
            color: var(--color-primary);
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Checkbox/Toggle styling */
        .custom-checkbox-group {
            padding: 12px; 
            background: var(--color-secondary); 
            border-radius: 10px;
        }
        .custom-checkbox-group label {
            display: flex; 
            align-items: center; 
            gap: 10px; 
            cursor: pointer;
            font-weight: 400;
            margin-bottom: 0;
        }

        .custom-checkbox-group input[type="checkbox"] {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid var(--color-primary);
            border-radius: 5px;
            cursor: pointer;
            position: relative;
            transition: all var(--transition-speed);
        }
        .custom-checkbox-group input[type="checkbox"]:checked {
            background: linear-gradient(135deg, #00bcd4, #007bff);
            border-color: var(--color-primary);
        }
        .custom-checkbox-group input[type="checkbox"]:checked::after {
            content: '\f00c'; /* Font Awesome check icon */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 14px;
            color: var(--color-background);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Features list */
        .features {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .features li {
            padding: 8px 0;
            color: var(--color-text-muted);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 400;
        }
        
        .features li i {
            color: var(--color-primary);
            font-size: 1.1rem;
            transition: transform var(--transition-speed);
        }
        
        .features li:hover i {
            transform: scale(1.2);
        }

        /* Toast Styling */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #00bcd4, #007bff);
            color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* --- FOOTER --- */
        footer {
            background: #080808;
            padding: 70px 0 30px;
            border-top: 1px solid #1a1a1a;
            margin-top: 80px;
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
        .footer-col a { color: #888; transition: 0.3s; }
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
        .social-links a:hover { 
            background: #00bcd4; 
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #1a1a1a;
            color: #555;
            font-size: 0.85rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .header h1 { font-size: 2rem; }
            .main-content { grid-template-columns: 1fr; gap: 25px; }
            #bottle3d-container {
                min-height: 500px;
                max-height: 550px;
            }
        }

        @media (max-width: 768px) {
            .container { padding: 30px 15px; }
            .header { margin-bottom: 30px; }
            .header h1 { font-size: 1.8rem; }
            .header p { font-size: 0.95rem; }
            #bottle3d-container {
                min-height: 400px;
                max-height: 450px;
            }
            .info-card { padding: 20px; }
            .control-group { margin-bottom: 18px; }
        }

    </style>
</head>
<body>
    <?php 
    // Assuming includes/navbar.php contains the navigation HTML
    include 'includes/navbar.php'; 
    ?>
    
    <div class="container">
        <a href="<?= $product ? 'product.php?id=' . $product['id'] : 'index.php' ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to <?= $product ? 'Product' : 'Home' ?>
        </a>
        
        <div class="header">
            <h1>Luxury 3D Bottle Customizer</h1>
            <p>Design your signature label and preview it in a photorealistic 3D environment.</p>
        </div>
        
        <div class="main-content">
            <div id="bottle3d-container"></div>
            
            <div>
                <div class="info-card">
                    <h3>
                        <i class="fas fa-image"></i> Label Customization
                    </h3>
                    
                    <div class="control-group">
                        <label>
                            <i class="fas fa-upload"></i> Upload Your Label Image
                        </label>
                        <div style="position: relative;">
                            <input type="file" id="labelUpload" accept="image/*">
                            <div id="uploadProgress" style="display: none; margin-top: 10px; text-align: center; font-weight: 500;">
                                <i class="fas fa-spinner fa-spin"></i> Processing image...
                            </div>
                        </div>
                        <small>
                            <span style="color: var(--color-primary);">✨ **Optimized Scaling:**</span> Image will be automatically fitted to recommended label dimensions.<br>
                            <span style="color: #f5f5f5;">📸 Recommended: High-resolution PNG or JPG for best quality.</span>
                        </small>
                    </div>

                    <div class="control-group">
                        <label>
                            <i class="fas fa-video"></i> Camera View
                        </label>
                        <div style="display: flex; gap: 15px;">
                            <button class="camera-view-btn active" data-view="threequarter">
                                3/4 View
                            </button>
                            <button class="camera-view-btn" data-view="front">
                                Front View
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group" style="margin-bottom: 0;">
                        <label>
                            <i class="fas fa-cog"></i> Actions
                        </label>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <button id="resetRotation" class="action-btn">
                                <i class="fas fa-undo"></i> Reset Rotation
                            </button>
                            <button id="downloadPreview" class="action-btn">
                                <i class="fas fa-download"></i> Download High-Quality Preview
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <?php if ($product): ?>
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-price">₹<?= number_format($product['price'], 2) ?></div>
                            <?php if ($product['description']): ?>
                                <p style="color: var(--color-text-muted); margin: 10px 0 0 0; font-size: 1rem;">
                                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <h3><i class="fas fa-magic"></i> Customizer Features</h3>
                    <ul class="features">
                        <li><i class="fas fa-check-circle"></i> 360° Interactive Rotation</li>
                        <li><i class="fas fa-check-circle"></i> Seamless Custom Label Upload</li>
                        <li><i class="fas fa-check-circle"></i> Real-time Photorealistic Preview</li>
                        <li><i class="fas fa-check-circle"></i> High-Resolution Export</li>
                    </ul>
                    
                    <?php if ($product): ?>
                        <a href="product.php?id=<?= $product['id'] ?>" class="btn">
                            <i class="fas fa-shopping-cart"></i> Finalize & Add to Cart
                        </a>
                        <a href="customize.php?id=<?= $product['id'] ?>" class="btn secondary">
                            <i class="fas fa-paint-brush"></i> Switch to 2D Customizer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
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
    
    <script src="assets/js/bottle3d-photorealistic.js"></script>
    
    <script>
        // Initialize Photorealistic 3D Bottle
        let photorealisticBottle = null;
        
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('bottle3d-container');
            if (container) {
                // Initialize photorealistic bottle
                // Assuming PhotorealisticBottle is defined in bottle3d-photorealistic.js
                photorealisticBottle = new PhotorealisticBottle('bottle3d-container', {
                    bottleColor: 0xffffff
                });

                // Setup label upload with progress indicator
                const labelUpload = document.getElementById('labelUpload');
                const uploadProgress = document.getElementById('uploadProgress');
                if (labelUpload) {
                    labelUpload.addEventListener('change', (e) => {
                        const file = e.target.files[0];
                        if (file && photorealisticBottle) {
                            // Show progress
                            if (uploadProgress) {
                                uploadProgress.style.display = 'block';
                            }
                            
                            // Mocking the autoFitImageToLabel function for context
                            // Replace with your actual implementation that loads and applies the texture
                            photorealisticBottle.autoFitImageToLabel(file, (finalUrl) => {
                                console.log('Label applied successfully');
                                
                                // Hide progress
                                if (uploadProgress) {
                                    uploadProgress.style.display = 'none';
                                }
                                // Show success message
                                showToast('Label uploaded and applied successfully!', 'success');
                            });
                        }
                    });
                }
                
                // Toast notification function
                function showToast(message, type = 'success') {
                    const toast = document.createElement('div');
                    toast.className = 'toast';
                    const bgColor = type === 'success' 
                        ? 'linear-gradient(135deg, #00bcd4, #007bff)' 
                        : 'linear-gradient(135deg, #ff4757, #c44569)';
                    toast.style.background = bgColor;
                    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.style.animation = 'slideOutRight 0.3s ease-out';
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                }

                // Camera view switcher
                const viewButtons = document.querySelectorAll('.camera-view-btn');
                viewButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const view = btn.dataset.view;
                        if (photorealisticBottle) {
                            // Assumes photorealisticBottle has a setCameraView method
                            photorealisticBottle.setCameraView(view);
                        }
                        // Update active state
                        viewButtons.forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    });
                });
                
                // Reset rotation button
                const resetBtn = document.getElementById('resetRotation');
                if (resetBtn && photorealisticBottle) {
                    resetBtn.addEventListener('click', () => {
                        if (photorealisticBottle.bottleGroup) {
                            // Assuming bottleGroup is the Three.js group to rotate
                            photorealisticBottle.bottleGroup.rotation.y = 0;
                            showToast('Rotation reset', 'success');
                        }
                    });
                }
                
                // Download high-quality button
                const downloadBtn = document.getElementById('downloadPreview');
                if (downloadBtn && photorealisticBottle) {
                    downloadBtn.addEventListener('click', () => {
                        // Assumes photorealisticBottle has a downloadHighQuality method
                        photorealisticBottle.downloadHighQuality();
                        showToast('High-quality image downloading...', 'success');
                    });
                }
            }
        });
    </script>
    
    <script src="assets/js/navbar.js" defer></script>
</body>
</html>