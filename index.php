<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;

// Get cart and wishlist counts for badges
$cartCount = $isLoggedIn ? cart_count($currentUser['id']) : 0;
$wishlistCount = $isLoggedIn ? wishlist_count($currentUser['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <title>Bottle | Custom Branded Water Bottles</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/menu-toggle.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet"> 

    <style>
    /* Critical CSS - Load immediately for responsiveness */
    * {
        box-sizing: border-box;
    }
    
    html {
        font-size: 16px;
        /* Prevent scrollbar width changes */
        overflow-y: scroll;
        scrollbar-gutter: stable;
    }
    
    body {
        font-family: 'Space Grotesk', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        background: #0B0C10;
        color: #f5f5f5;
        line-height: 1.6;
        overflow-x: hidden;
        /* Prevent layout shifts */
        min-height: 100vh;
        /* Force immediate rendering */
        will-change: auto;
        /* Prevent horizontal scroll */
        width: 100%;
        max-width: 100vw;
        position: relative;
    }
    
    /* Ensure responsive styles apply immediately */
    @media (max-width: 768px) {
        body {
            font-size: 16px;
        }
    }
    
    /* Material Symbols Icon Font */
    .icon {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        font-size: 24px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-feature-settings: 'liga';
        -webkit-font-smoothing: antialiased;
        font-display: swap;
    }
    
    a { 
        text-decoration: none; 
        color: inherit; 
    }
    
    img { 
        max-width: 100%; 
        display: block; 
    }
    
    h2 { 
        font-weight: 700; 
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 3rem;
        letter-spacing: -0.5px;
    }

    /* Navbar styles moved to assets/css/navbar.css */
    
    /* Critical Navbar Positioning - Prevent Layout Shift */
    /* NOTE: Navbar styles are in assets/css/navbar.css - Don't override here */
    header#main-header.header-futuristic {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        transform: translateZ(0);
        -webkit-transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        will-change: transform;
        box-sizing: border-box !important;
        z-index: 1000 !important; /* Ensure header is below menu (menu z-index: 99999) */
    }
    
    /* Ensure mobile menu is not blocked by header */
    @media (max-width: 767px) {
        .nav-futuristic.nav-open,
        .nav-futuristic.active {
            z-index: 99999 !important;
        }
    }
    
    /* Body padding to account for fixed navbar - applied immediately */
    body {
        padding-top: 0;
    }
    
    /* Critical responsive styles - load immediately */
    @media (max-width: 1440px) {
        html { font-size: 16px; }
    }
    
    @media (max-width: 1024px) {
        html { font-size: 15px; }
    }
    
    @media (max-width: 768px) {
        html { font-size: 14px; }
    }
    
    @media (max-width: 480px) {
        html { font-size: 14px; }
    }
    
    @media (max-width: 320px) {
        html { font-size: 13px; }
    }

    /* === Hero Slideshow === */
    .hero {
        position: relative;
        height: 80vh;
        min-height: 700px;
        max-height: 850px;
        overflow: hidden;
        margin-top: 0;
        display: flex;
        align-items: center;
    }
    
    .slide {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        background-attachment: scroll;
        opacity: 0;
        transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%;
        height: 100%;
    }
    
    .slide::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6));
        z-index: 1;
    }
    
    .slide.active {
        opacity: 1;
    }
    
    
    .hero-content {
        position: relative;
        z-index: 10;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        text-align: left;
        padding: 80px 4% 60px;
        max-width: 1280px;
        margin: 0 auto;
        width: 100%;
        color: white;
    }
    
    .hero-content-wrapper {
        width: 100%;
        max-width: 650px;
    }
    
    .mt-10 {
        margin-top: 2.5rem;
    }
    
    .hero-content .subtitle {
        font-size: 0.9rem;
        font-weight: 400;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 0.8rem;
        animation: fadeInUp 0.8s ease-out;
    }
    
    .hero-content h1 {
        font-size: clamp(2.5rem, 8vw, 5rem);
        font-weight: 700;
        color: #ffffff;
        margin: 0;
        line-height: 1.1;
        letter-spacing: -0.03em;
        margin-bottom: 1.2rem;
        animation: fadeInUp 0.8s ease-out 0.1s both;
    }
    
    .hero-content p { 
        max-width: 550px; 
        color: rgba(255, 255, 255, 0.75); 
        font-size: 0.95rem;
        margin-bottom: 2rem;
        line-height: 1.7;
        animation: fadeInUp 0.8s ease-out 0.2s both;
    }
    
    @media (max-width: 1024px) {
        .hero {
            height: 75vh;
            min-height: 600px;
            max-height: 800px;
        }
    }
    
    @media (max-width: 768px) {
        .hero {
            height: 70vh;
            min-height: 550px;
            max-height: 700px;
        }
        
        .slide {
            background-position: center center;
            background-size: cover;
        }
        
        .hero-content {
            align-items: flex-start;
            text-align: left;
            padding: clamp(40px, 8vw, 60px) clamp(4%, 5vw, 5%) clamp(30px, 6vw, 40px);
        }
        
        .hero-content-wrapper {
            max-width: 100%;
        }
        
        .hero-content h1 {
            font-size: clamp(2rem, 10vw, 3.5rem);
        }
        
        .hero-content .subtitle {
            font-size: clamp(0.75rem, 2vw, 0.9rem);
        }
        
        .hero-content p {
            text-align: left;
            max-width: 100%;
            font-size: clamp(0.85rem, 2.5vw, 0.95rem);
        }
    }
    
    @media (max-width: 480px) {
        .hero {
            height: 65vh;
            min-height: 450px;
            max-height: 600px;
        }
        
        .hero-content {
            padding: clamp(30px, 6vw, 50px) clamp(3%, 4vw, 4%) clamp(20px, 4vw, 30px);
        }
        
        .hero-content h1 {
            font-size: clamp(1.75rem, 8vw, 2.5rem);
        }
    }
    
    @media (max-width: 320px) {
        .hero {
            height: 60vh;
            min-height: 400px;
        }
        
        .hero-content {
            padding: 30px 3% 20px;
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
    
    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-5px);
        }
    }
    
    .btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 12px 28px;
        border-radius: 30px;
        color: #fff;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        border: 1.5px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
        position: relative;
        animation: fadeInUp 0.8s ease-out 0.4s both;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .btn:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.6);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
    
    .btn:active {
        transform: translateY(0);
    }
    
    .btn-icon-circle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        color: #000;
        transition: all 0.3s ease;
    }
    
    .btn:hover .btn-icon-circle {
        background: #fff;
        transform: translateX(3px);
    }
    
    .btn-icon-circle .icon {
        font-size: 16px;
    }

    /* Dots */
    .dots {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 12px;
        z-index: 10;
    }
    
    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }
    
    .dot:hover {
        background: rgba(255, 255, 255, 0.5);
        transform: scale(1.3);
    }
    
    .dot.active { 
        background: rgba(255, 255, 255, 0.9);
        width: 24px;
        border-radius: 4px;
    }

    /* === Sections === */
    section { 
        padding: clamp(3rem, 8vw, 4.5rem) clamp(3%, 5vw, 5%);
        position: relative;
    }
    
    section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(0, 188, 212, 0.25), transparent);
    }
    
    section h2 {
        font-size: clamp(1.75rem, 5vw, 2.8rem);
        font-weight: 700;
        text-align: center;
        margin-bottom: clamp(2rem, 5vw, 3rem);
        color: #fff;
        letter-spacing: -0.5px;
        padding: 0 clamp(1rem, 3vw, 2rem);
    }
    
    .why {
        display: grid;
        grid-template-columns: 1fr;
        gap: clamp(1.5rem, 4vw, 2rem);
        max-width: 1200px;
        margin: 0 auto;
    }
    
    @media (min-width: 480px) {
        .why {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 768px) {
        .why {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(1.75rem, 4vw, 2rem);
        }
    }
    
    .why .card {
        background: rgba(20, 20, 20, 0.6);
        backdrop-filter: blur(10px);
        padding: clamp(2rem, 5vw, 2.5rem) clamp(1.5rem, 4vw, 2rem);
        border-radius: clamp(15px, 3vw, 20px);
        text-align: center;
        border: 1px solid rgba(0, 188, 212, 0.15);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .why .card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(0, 188, 212, 0.1) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.4s;
    }
    
    .why .card:hover {
        transform: translateY(-8px);
        border-color: rgba(0, 188, 212, 0.4);
        box-shadow: 0 15px 40px rgba(0, 188, 212, 0.2);
    }
    
    .why .card:hover::before {
        opacity: 1;
    }
    
    .why .card i { 
        font-size: clamp(2rem, 5vw, 2.5rem); 
        color: #00bcd4; 
        margin-bottom: clamp(1rem, 3vw, 1.25rem);
        display: block;
    }
    
    .why .card h3 {
        font-size: clamp(1.1rem, 3vw, 1.25rem);
        font-weight: 600;
        margin-bottom: clamp(0.75rem, 2vw, 1rem);
        color: #fff;
    }
    
    .why .card p {
        color: #b0b0b0;
        font-size: clamp(0.875rem, 2vw, 0.95rem);
        line-height: 1.6;
        margin: 0;
    }

    /* === Products Carousel === */
    .products-carousel-container {
        position: relative;
        width: 100%;
        max-width: 1400px;
        margin: 40px auto 0;
        padding: 0 8%;
        overflow: hidden;
    }
    
    .products-carousel-wrapper {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        padding: 20px 0;
    }
    
    /* Gradient overlays on edges for smooth fade effect */
    .products-carousel-wrapper::before,
    .products-carousel-wrapper::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 100px;
        z-index: 5;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    
    .products-carousel-wrapper::before {
        left: 0;
        background: linear-gradient(to right, rgba(11, 12, 16, 0.95), transparent);
    }
    
    .products-carousel-wrapper::after {
        right: 0;
        background: linear-gradient(to left, rgba(11, 12, 16, 0.95), transparent);
    }
    
    @media (max-width: 768px) {
        .products-carousel-wrapper::before,
        .products-carousel-wrapper::after {
            width: 50px;
        }
    }
    
    .products-grid {
        display: flex;
        gap: 24px;
        will-change: transform;
        animation: carousel-scroll 30s linear infinite;
    }
    
    .products-carousel-wrapper:hover .products-grid {
        animation-play-state: paused;
        cursor: default;
    }
    
    @keyframes carousel-scroll {
        0% {
            transform: translateX(0);
        }
        100% {
            transform: translateX(calc(-50% - 12px));
        }
    }
    
    /* Smooth scrollbar hiding */
    .products-carousel-container::-webkit-scrollbar {
        display: none;
    }
    
    .products-carousel-container {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    
    .product-card {
        flex: 0 0 calc(25% - 18px);
        min-width: 280px;
    }
    
    @media (max-width: 1200px) {
        .product-card {
            flex: 0 0 calc(33.333% - 16px);
            min-width: 260px;
        }
    }
    
    @media (max-width: 1024px) {
        .products-carousel-container {
            padding: 0 clamp(3%, 4vw, 4%);
        }
        
        .product-card {
            flex: 0 0 calc(33.333% - 16px);
            min-width: 260px;
        }
    }
    
    @media (max-width: 768px) {
        .products-carousel-container {
            padding: 0 clamp(2%, 3vw, 4%);
        }
        
        .product-card {
            flex: 0 0 calc(50% - 12px);
            min-width: clamp(220px, 45vw, 240px);
        }
        
        .products-carousel-wrapper {
            padding: clamp(12px, 2vw, 15px) 0;
        }
    }
    
    @media (max-width: 480px) {
        .products-carousel-container {
            padding: 0 clamp(1.5%, 2vw, 3%);
        }
        
        .product-card {
            flex: 0 0 85%;
            min-width: clamp(260px, 80vw, 280px);
        }
        
        .products-carousel-wrapper::before,
        .products-carousel-wrapper::after {
            width: clamp(20px, 5vw, 30px);
        }
    }
    
    @media (max-width: 320px) {
        .product-card {
            flex: 0 0 90%;
            min-width: 250px;
        }
    }
    
    /* Carousel Navigation Arrows */
    .carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 188, 212, 0.9);
        backdrop-filter: blur(10px);
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        color: #fff;
        font-size: 1.2rem;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 15px rgba(0, 188, 212, 0.4);
        opacity: 0;
        pointer-events: none;
    }
    
    .products-carousel-wrapper:hover .carousel-nav {
        opacity: 1;
        pointer-events: all;
    }
    
    .carousel-nav:hover {
        background: rgba(0, 188, 212, 1);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 188, 212, 0.6);
    }
    
    .carousel-nav.prev {
        left: 2%;
    }
    
    .carousel-nav.next {
        right: 2%;
    }
    
    @media (max-width: 768px) {
        .carousel-nav {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        .carousel-nav.prev {
            left: 1%;
        }
        
        .carousel-nav.next {
            right: 1%;
        }
    }
    
    .product-card {
        background: #141414;
        border: 2px solid #333;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        cursor: pointer;
    }
    
    .product-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 188, 212, 0.3);
        border-color: rgba(0, 188, 212, 0.5);
    }
    
    .product-image-wrapper {
        position: relative;
        width: 100%;
        height: 256px;
        overflow: hidden;
        background: #0f0f0f;
    }
    
    .product-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }
    
    .product-card:hover .product-image-wrapper img {
        transform: scale(1.05);
    }
    
    .product-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: #00bcd4;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 9999px;
        z-index: 2;
    }
    
    .product-actions {
        position: absolute;
        top: 12px;
        right: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 2;
    }
    
    .product-card:hover .product-actions {
        opacity: 1;
    }
    
    .product-action-btn {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        color: #333;
        font-size: 1.25rem;
    }
    
    .product-action-btn:hover {
        background: #00bcd4;
        color: white;
    }
    
    .product-info {
        padding: 16px;
    }
    
    .product-category {
        font-size: 0.875rem;
        color: #999;
        margin-bottom: 4px;
    }
    
    .product-name {
        font-weight: 600;
        color: #fff;
        margin: 4px 0;
        font-size: 1rem;
    }
    
    .product-price-rating {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 8px;
    }
    
    .product-price {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .price-current {
        font-weight: 700;
        font-size: 1.125rem;
        color: #00bcd4;
    }
    
    .price-original {
        font-size: 0.875rem;
        color: #666;
        text-decoration: line-through;
    }
    
    .product-rating {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .product-rating .star {
        color: #ffc107;
        font-size: 1rem;
    }
    
    .product-rating .rating-value {
        font-size: 0.875rem;
        color: #999;
    }
    
    .view-all-btn-wrapper {
        text-align: center;
        margin-top: 40px;
        padding: 0 8%;
    }
    
    .view-all-btn {
        background: linear-gradient(135deg, #00bcd4, #0097a7);
        color: #fff;
        padding: 14px 32px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 188, 212, 0.4);
        border: 2px solid transparent;
    }
    
    .view-all-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 188, 212, 0.6);
        background: linear-gradient(135deg, #00acc1, #00838f);
        border-color: rgba(0, 188, 212, 0.5);
    }
    
    .product-card .btn {
        padding: 8px 14px;
        font-size: 0.72rem;
        margin: 0;
        border-radius: 20px;
        font-weight: 600;
        background: linear-gradient(135deg, #00bcd4, #0097a7);
        border: none;
        color: #fff;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 3px 10px rgba(0, 188, 212, 0.35);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-width: auto;
        height: auto;
        white-space: nowrap;
    }
    
    .product-card .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 188, 212, 0.5);
        background: linear-gradient(135deg, #00acc1, #00838f);
    }
    
    .product-card .btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(0, 188, 212, 0.3);
    }
    
    .product-card .btn[data-wishlist-add] {
        padding: 8px 12px;
        background: linear-gradient(135deg, #e91e63, #c2185b);
        box-shadow: 0 3px 10px rgba(233, 30, 99, 0.35);
    }
    
    .product-card .btn[data-wishlist-add]:hover {
        background: linear-gradient(135deg, #f06292, #e91e63);
        box-shadow: 0 5px 15px rgba(233, 30, 99, 0.5);
    }
    
    .product-card .btn[style*="background"] {
        box-shadow: 0 3px 10px rgba(106, 27, 154, 0.35);
    }
    
    .product-card .btn[style*="background"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(106, 27, 154, 0.5);
    }
    
    .product-card .btn i {
        font-size: 0.8rem;
    }

    /* === Custom Banner === */
    .custom-banner {
        background: linear-gradient(135deg, rgba(13, 13, 13, 0.9) 0%, rgba(22, 22, 22, 0.9) 100%);
        backdrop-filter: blur(15px);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        border-radius: 30px;
        overflow: hidden;
        padding: 70px 80px;
        gap: 60px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(0, 188, 212, 0.2);
        max-width: 1500px;
        margin: 0 auto;
        position: relative;
    }
    
    .custom-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(0, 188, 212, 0.15) 0%, transparent 70%);
        border-radius: 50%;
    }

    .custom-banner .text {
        flex: 1;
        min-width: 300px;
        padding: 20px 0;
        color: #fff;
        position: relative;
        z-index: 1;
    }

    .custom-banner h2 {
        font-size: clamp(2rem, 4vw, 3.5rem);
        font-weight: 800;
        margin-bottom: 25px;
        line-height: 1.2;
        text-align: left;
        letter-spacing: -1px;
    }

    .custom-banner h2 span {
        color: #00bcd4;
        display: block;
    }

    .custom-banner p {
        font-size: 1.125rem;
        line-height: 1.8;
        color: #d0d0d0;
        max-width: 550px;
        margin-bottom: 30px;
    }

    .custom-banner img {
        width: 450px;
        max-width: 100%;
        height: auto;
        border-radius: 20px;
        object-fit: cover;
        filter: drop-shadow(0 10px 30px rgba(0, 188, 212, 0.3));
        position: relative;
        z-index: 1;
        transition: transform 0.4s;
        display: block;
    }
    
    .custom-banner:hover img {
        transform: scale(1.05);
    }
    
    .custom-banner video {
        width: 550px;
        height: 420px;
        border-radius: 25px;
        object-fit: contain;
        filter: none;
        box-shadow: none;
        background: transparent;
        position: relative;
        z-index: 1;
        transition: transform 0.4s;
        display: block;
    }
    
    .custom-banner:hover video {
        transform: scale(1.02);
    }
    
    /* Video responsive sizing */
    @media (max-width: 992px) {
        .custom-banner video {
            width: 400px;
            height: 320px;
            border-radius: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .custom-banner video {
            width: 320px;
            height: 280px;
            border-radius: 18px;
        }
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
        
        .custom-banner h2 {
            text-align: center;
        }
        
        .custom-banner img {
            width: 85%;
            margin-top: 20px;
        }
        
        .custom-banner video {
            width: 400px;
            height: 320px;
            margin-top: 20px;
        }
    }

    /* === Testimonials === */
    .testimonials { 
        text-align: center; 
        background: rgba(10, 10, 10, 0.5);
        border-radius: 30px;
        padding: 80px 5%;
    }
    
    .testimonial-grid {
        display: grid;
        gap: 30px;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        margin-top: 50px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .testimonial {
        background: rgba(20, 20, 20, 0.7);
        backdrop-filter: blur(10px);
        padding: 35px 30px;
        border-radius: 18px;
        font-style: italic;
        border: 1px solid rgba(0, 188, 212, 0.15);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .testimonial::before {
        content: '"';
        position: absolute;
        top: 10px;
        left: 20px;
        font-size: 4rem;
        color: rgba(0, 188, 212, 0.2);
        font-family: serif;
        line-height: 1;
    }
    
    .testimonial:hover {
        transform: translateY(-5px);
        border-color: rgba(0, 188, 212, 0.3);
        box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
    }
    
    .testimonial h4 { 
        margin-top: 20px; 
        color: #00bcd4; 
        font-style: normal;
        font-weight: 600;
    }

    /* === Newsletter === */
    .newsletter {
        background: linear-gradient(135deg, #007bff 0%, #00bcd4 100%);
        padding: 80px 5%;
        text-align: center;
        border-radius: 30px;
        color: #fff;
        position: relative;
        overflow: hidden;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .newsletter::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .newsletter h2,
    .newsletter p,
    .newsletter form {
        position: relative;
        z-index: 1;
    }
    
    .newsletter h2 {
        margin-bottom: 15px;
    }
    
    .newsletter p {
        font-size: 1.125rem;
        margin-bottom: 30px;
        opacity: 0.95;
    }
    
    .newsletter form {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .newsletter input {
        padding: 14px 20px;
        border: none;
        border-radius: 50px;
        flex: 1;
        min-width: 250px;
        font-size: 1rem;
        background: rgba(255, 255, 255, 0.95);
        color: #333;
        outline: none;
    }
    
    .newsletter input::placeholder {
        color: #999;
    }
    
    .newsletter button {
        padding: 14px 35px;
        border: none;
        border-radius: 50px;
        background: #000;
        color: #fff;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
        font-size: 1rem;
        white-space: nowrap;
    }
    
    .newsletter button:hover {
        background: #1a1a1a;
        transform: scale(1.05);
    }

    /* === Footer === */
    footer {
        background: linear-gradient(180deg, #080808 0%, #000000 100%);
        padding: 80px 5% 30px;
        border-top: 1px solid rgba(0, 188, 212, 0.2);
    }
    
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .footer-grid h4 { 
        color: #00bcd4; 
        margin-bottom: 20px;
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .footer-grid p {
        color: #b0b0b0;
        line-height: 1.8;
        margin: 8px 0;
    }
    
    .footer-grid a {
        color: #b0b0b0;
        transition: color 0.3s;
    }
    
    .footer-grid a:hover {
        color: #00bcd4;
    }
    
    .social { 
        display: flex; 
        gap: 15px; 
        margin-top: 15px; 
    }
    
    .social a { 
        color: #00bcd4; 
        font-size: 1.5rem; 
        transition: all 0.3s;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(0, 188, 212, 0.1);
        border: 1px solid rgba(0, 188, 212, 0.2);
    }
    
    .social a:hover { 
        color: #fff;
        background: rgba(0, 188, 212, 0.3);
        transform: translateY(-3px);
    }

    /* === Back to Top Button === */
    #back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, #00bcd4, #007bff);
        color: #fff;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        opacity: 0.9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 999;
        border: none;
        box-shadow: 0 8px 25px rgba(0, 188, 212, 0.4);
    }
    
    #back-to-top:hover {
        opacity: 1;
        transform: translateY(-5px) scale(1.1);
        box-shadow: 0 12px 35px rgba(0, 188, 212, 0.6);
    }

    /* === Responsive === */
    @media (max-width: 768px) {
        section {
            padding: 60px 4%;
        }
        
        h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
        }
        
        .hero {
            height: 80vh;
            min-height: 700px;
            max-height: 850px;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .why {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .custom-banner {
            padding: 40px 20px;
        }
        
        .newsletter form {
            flex-direction: column;
        }
        
        .newsletter input {
            width: 100%;
            min-width: auto;
        }
        
        #back-to-top {
            width: 45px;
            height: 45px;
            bottom: 20px;
            right: 20px;
        }
    }
</style>

</head>

<body>
<?php
$currentPage = 'home';
include __DIR__ . '/includes/navbar.php';
?>
<script>
// Critical: Fix navbar positioning immediately to prevent layout shift
// NOTE: This only sets header position, doesn't interfere with mobile menu
(function() {
    'use strict';
    const header = document.getElementById('main-header');
    if (header) {
        // Force immediate positioning - but don't interfere with menu
        header.style.position = 'fixed';
        header.style.top = '0';
        header.style.left = '0';
        header.style.right = '0';
        header.style.width = '100%';
        header.style.maxWidth = '100vw';
        header.style.boxSizing = 'border-box';
        header.style.transform = 'translateZ(0)';
        header.style.zIndex = '1000';
        // CRITICAL: Don't use overflow: hidden as it blocks menu clicks and visibility
        // header.style.overflow = 'hidden';
    }
    
})();
</script>


<section class="hero">
    <?php
    // Load hero slides from database
    try {
        $stmt = db()->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
        $heroSlides = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($heroSlides)) {
            // Fallback to default images if no slides in database
            $heroSlides = [
                ['image' => 'hero1.png', 'title' => 'Hero Slide 1'],
                ['image' => 'hero2.png', 'title' => 'Hero Slide 2'],
                ['image' => 'hero3.png', 'title' => 'Hero Slide 3']
            ];
            $useDefaults = true;
        } else {
            $useDefaults = false;
        }
        
        foreach ($heroSlides as $index => $slide) {
            $isActive = $index === 0 ? 'active' : '';
            if ($useDefaults) {
                $imageUrl = 'assets/images/' . $slide['image'];
            } else {
                $imageUrl = 'admin/uploads/' . htmlspecialchars($slide['image']);
            }
            echo "<div class='slide {$isActive}' style='background-image:url(\"{$imageUrl}\");'></div>";
        }
    } catch (Exception $e) {
        // Fallback if table doesn't exist
        echo "<div class='slide active' style='background-image:url(\"assets/images/hero1.png\");'></div>";
        echo "<div class='slide' style='background-image:url(\"assets/images/hero2.png\");'></div>";
        echo "<div class='slide' style='background-image:url(\"assets/images/hero3.png\");'></div>";
    }
    ?>

    <div class="hero-content">
        <div class="hero-content-wrapper">
            <p class="subtitle" data-aos="fade-up" data-aos-duration="800">Custom Branded</p>
            <h1 data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">BOTTLES</h1>
            <p data-aos="fade-up" data-aos-delay="200" data-aos-duration="800">Make your brand shine with every bottle. We design & deliver personalized water bottles for restaurants, cafés and premium events.</p>
            <div class="mt-10" data-aos="fade-up" data-aos-delay="400" data-aos-duration="800">
                <a href="category.php" class="btn">
                    Discover Now
                    <span class="btn-icon-circle">
                        <span class="icon">arrow_forward</span>
                    </span>
                </a>
            </div>
        </div>
    </div>
    <div class="dots">
        <?php
        $dotCount = isset($heroSlides) ? count($heroSlides) : 3;
        for ($i = 0; $i < $dotCount; $i++) {
            $active = $i === 0 ? 'active' : '';
            echo "<span class='dot {$active}'></span>";
        }
        ?>
    </div>
</section>

<section>
    <h2 data-aos="fade-up">Why Choose <span style="color:#00bcd4;">Bottle</span></h2>
    <div class="why">
        <div class="card" data-aos="fade-up" data-aos-delay="100"><i class="fas fa-tint"></i><h3>Pure Hydration</h3><p>Eco-friendly & safe water bottles built for elegance.</p></div>
        <div class="card" data-aos="fade-up" data-aos-delay="200"><i class="fas fa-brush"></i><h3>Custom Labeling</h3><p>Add your café or restaurant logo effortlessly.</p></div>
        <div class="card" data-aos="fade-up" data-aos-delay="300"><i class="fas fa-truck"></i><h3>Fast Shipping</h3><p>Nationwide express delivery within days.</p></div>
        <div class="card" data-aos="fade-up" data-aos-delay="400"><i class="fas fa-crown"></i><h3>Royal Quality</h3><p>Designed to match your premium brand identity.</p></div>
    </div>
</section>

<section>
    <h2 data-aos="fade-up">Featured Bottles</h2>
    <div class="products-carousel-container">
        <div class="products-carousel-wrapper" id="carousel-wrapper">
            <button class="carousel-nav prev" id="carousel-prev" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-nav next" id="carousel-next" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="products-grid" id="products-grid">
        <?php
        // Prepare to use db() helper function
        try {
            // Fetch active products for carousel (limit to 12 for performance)
            $stmt = db()->query("SELECT * FROM products WHERE is_active=1 ORDER BY RAND() LIMIT 12");
            $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalProducts = count($allProducts);
            
            // Get base products (at least 4, up to 8 for carousel)
            $baseProducts = array_slice($allProducts, 0, min(8, count($allProducts)));
            
            // If we have less than 4 products, duplicate to reach minimum
            if (count($baseProducts) < 4 && count($baseProducts) > 0) {
                while (count($baseProducts) < 4) {
                    $baseProducts = array_merge($baseProducts, array_slice($baseProducts, 0, 4 - count($baseProducts)));
                }
            }
            
            // Duplicate for seamless infinite loop (carousel needs at least 2 copies)
            $productsToShow = count($baseProducts) > 0 ? array_merge($baseProducts, $baseProducts) : [];
            
            $csrf = csrf_token();
            if (count($productsToShow) > 0) {
                foreach ($productsToShow as $index => $p) {
                // Fetch first image from product_images table (primary or first by order)
                $firstImage = $p['image']; // Fallback to main image
                try {
                  $imgStmt = db()->prepare("
                    SELECT image_path FROM product_images 
                    WHERE product_id = ? 
                    ORDER BY is_primary DESC, image_order ASC, id ASC 
                    LIMIT 1
                  ");
                  $imgStmt->execute([$p['id']]);
                  $imgResult = $imgStmt->fetchColumn();
                  if ($imgResult) {
                    $firstImage = $imgResult;
                  }
                } catch (Exception $e) {
                  // If product_images table doesn't exist, use fallback
                }
                
                $discount = rand(10, 50);
                $originalPrice = $p['price'] * (100 / (100 - $discount));
                // Get average rating from reviews table
                try {
                  $ratingStmt = db()->prepare("
                    SELECT AVG(rating) as avg_rating 
                    FROM reviews 
                    WHERE product_id = ?
                  ");
                  $ratingStmt->execute([$p['id']]);
                  $ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
                  
                  if ($ratingData && $ratingData['avg_rating'] !== null) {
                    $rating = round(floatval($ratingData['avg_rating']), 1);
                    $rating = max(1.0, min(5.0, $rating));
                  } else {
                    // No reviews - rating is 0
                    $rating = 0;
                  }
                } catch (PDOException $e) {
                  // If reviews table doesn't exist, rating is 0
                  $rating = 0;
                }
                // Format rating display - show "No reviews" if 0, else show rating with star
                if ($rating > 0) {
                  $ratingFormatted = number_format($rating, 1);
                  $ratingDisplay = "<div class='product-rating'>
                        <span class='star'>★</span>
                        <span class='rating-value'>{$ratingFormatted}</span>
                      </div>";
                } else {
                  $ratingDisplay = "<div class='product-rating'>
                        <span class='rating-value' style='color:#666;font-size:0.8rem;'>No reviews</span>
                      </div>";
                }
                echo "
                <div class='product-card' onclick=\"window.location.href='product.php?id={$p['id']}'\">
                    <div class='product-image-wrapper'>
                        <img src='admin/uploads/{$firstImage}' alt='{$p['name']}'>
                    <div class='product-badge'>{$discount}% off</div>
                    <div class='product-actions' onclick=\"event.stopPropagation();\">
                      <form method='POST' action='user/cart_action.php' style='display:inline;' onsubmit='event.stopPropagation();'>
                        <input type='hidden' name='csrf_token' value='" . esc($csrf) . "'>
                        <input type='hidden' name='action' value='add'>
                        <input type='hidden' name='product_id' value='{$p['id']}'>
                        <input type='hidden' name='redirect' value='index.php'>
                        <button type='submit' class='product-action-btn' title='Add to cart'>
                          <span class='material-symbols-outlined'>shopping_cart</span>
                        </button>
                      </form>
                      <button class='product-action-btn' title='Buy Now' onclick=\"event.stopPropagation(); buyNow({$p['id']})\">
                        <span class='material-symbols-outlined'>flash_on</span>
                      </button>
                    </div>
                  </div>
                  <div class='product-info'>
                    <p class='product-category'>" . htmlspecialchars($p['category'] ?: 'Bottles') . "</p>
                    <h4 class='product-name'>" . htmlspecialchars($p['name']) . "</h4>
                    <div class='product-price-rating'>
                      <div class='product-price'>
                        <span class='price-current'>₹" . number_format($p['price'], 2) . "</span>
                        <span class='price-original'>₹" . number_format($originalPrice, 2) . "</span>
                      </div>
                      {$ratingDisplay}
                    </div>
                    </div>
                </div>";
                }
            } else {
                echo "<p style='text-align:center; color:#999; padding: 40px;'>No featured products available at the moment.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='text-align:center; color:red;'>Database Error: Failed to load products.</p>";
        }
        ?>
            </div>
        </div>
    </div>
    <?php if (isset($totalProducts) && $totalProducts > 4): ?>
        <div class="view-all-btn-wrapper" id="view-all-wrapper">
            <a href="category.php" class="view-all-btn" id="view-all-btn">
                <i class="fas fa-th"></i> View All Products
            </a>
        </div>
    <?php endif; ?>
</section>

<section class="custom-banner" data-aos="fade-right">
    <div class="text">
        <h2>Design <span>Your Own</span> Bottle</h2>
        <p>Upload your restaurant’s logo and choose your bottle color & packaging.
        We print it for you and ship within 48 hours.</p>
        <a href="customize-3d.php" class="btn">Start Customizing</a>
    </div>
    <video 
        src="media.mp4" 
        data-aos="fade-left"
        autoplay 
        loop 
        muted 
        playsinline
        preload="auto"
        aria-label="Custom Bottle Video"
    ></video>
</section>

<section class="testimonials">
    <h2 data-aos="fade-up">What Our Partners Say</h2>
    <div class="testimonial-grid">
        <?php
        // Sample testimonials - replace with your actual DB query logic
        $testimonials_data = [
            ["quote" => "We love Bottle's quality and quick turnaround. Our customers notice the branding.", "author" => "Café Blue"],
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
            <h4>About Bottle</h4>
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
            <p>Email: support@bottle.com</p>
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
    <p style="text-align:center;margin-top:20px;color:#666;">© 2025 Bottle. All rights reserved.</p>
</footer>

<button id="back-to-top" title="Go to top"><i class="fas fa-arrow-up"></i></button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="assets/js/app.js" defer></script>
<script src="assets/js/navbar.js" defer></script>
<script src="assets/js/menu-toggle.js" defer></script>

<script>
// Critical: Prevent navbar layout shift on page load
(function() {
    'use strict';
    
    function fixNavbarPosition() {
        const header = document.getElementById('main-header');
        if (!header) return;
        
        // Check if mobile menu is open - don't interfere if it is
        const nav = document.querySelector('.nav-futuristic');
        const isMenuOpen = nav && nav.classList.contains('nav-open');
        
        // Only fix header positioning if menu is NOT open
        // This prevents interference with mobile menu functionality
        if (!isMenuOpen) {
            header.style.position = 'fixed';
            header.style.top = '0';
            header.style.left = '0';
            header.style.right = '0';
            header.style.width = '100%';
            header.style.maxWidth = '100vw';
            header.style.boxSizing = 'border-box';
            header.style.transform = 'translateZ(0)';
            header.style.zIndex = '1000';
            // CRITICAL: Don't set overflow: hidden as it blocks menu clicks
            // header.style.overflow = 'hidden';
        }
    }
    
    // Fix immediately
    fixNavbarPosition();
    
    // Fix on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixNavbarPosition);
    } else {
        fixNavbarPosition();
    }
    
    // Fix on window load
    window.addEventListener('load', fixNavbarPosition);
    
    // Fix on resize (to handle orientation changes)
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(fixNavbarPosition, 100);
    });
})();

// Initialize AOS immediately but with optimized settings for faster rendering
(function() {
    'use strict';
    
    // Initialize AOS as soon as possible
    if (typeof AOS !== 'undefined') {
AOS.init({
            duration: 600,
    once: true,
            offset: 50,
    easing: 'ease-out-cubic',
            disable: false,
            startEvent: 'DOMContentLoaded',
            initClassName: false,
            animatedClassName: 'aos-animate',
            useClassNames: false,
            disableMutationObserver: false,
            debounceDelay: 50,
            throttleDelay: 99,
        });
    } else {
        // If AOS not loaded yet, wait for it
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 600,
                    once: true,
                    offset: 50,
                    easing: 'ease-out-cubic',
                });
            }
        });
    }
    
    // Force immediate layout calculation for responsiveness
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger reflow to apply responsive styles immediately
            void document.body.offsetHeight;
        });
    } else {
        // Already loaded, trigger immediately
        void document.body.offsetHeight;
    }
})();

// Add futuristic cursor trail effect
let particles = [];
const heroSection = document.querySelector('.hero');

if (heroSection) {
    heroSection.addEventListener('mousemove', (e) => {
        if (particles.length > 20) return; // Limit particles
        
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: absolute;
            width: 4px;
            height: 4px;
            background: radial-gradient(circle, rgba(0, 188, 212, 0.8) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            left: ${e.clientX}px;
            top: ${e.clientY - 56}px;
            z-index: 999;
            animation: particleFade 1s ease-out forwards;
        `;
        heroSection.appendChild(particle);
        particles.push(particle);
        
        setTimeout(() => {
            particle.remove();
            particles = particles.filter(p => p !== particle);
        }, 1000);
    });
}

// Add particle fade animation
const style = document.createElement('style');
style.textContent = `
    @keyframes particleFade {
        0% {
            opacity: 1;
            transform: scale(1) translate(0, 0);
        }
        100% {
            opacity: 0;
            transform: scale(0) translate(var(--tx, 0), var(--ty, -50px));
        }
    }
`;
document.head.appendChild(style);

const backToTopButton = document.getElementById('back-to-top');
const header = document.getElementById('main-header');

// 2. Header Scroll Effect & Back to Top Button Logic
window.addEventListener('scroll', () => {
    // Header Scroll Effect
    if (header) {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
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

// Buy Now function - adds to cart and redirects to checkout
function buyNow(productId) {
  <?php if (!$isLoggedIn): ?>
  // Show login popup if not logged in
  if (typeof showLoginPopup === 'function') {
    showLoginPopup();
  } else if (window.showLoginPopup) {
    window.showLoginPopup();
  } else {
    window.location.href = 'login.php';
  }
  return;
  <?php endif; ?>
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'user/cart_action.php';
  
  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = 'csrf_token';
  csrfInput.value = '<?= esc(csrf_token()) ?>';
  form.appendChild(csrfInput);
  
  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = 'add';
  form.appendChild(actionInput);
  
  const productInput = document.createElement('input');
  productInput.type = 'hidden';
  productInput.name = 'product_id';
  productInput.value = productId;
  form.appendChild(productInput);
  
  const quantityInput = document.createElement('input');
  quantityInput.type = 'hidden';
  quantityInput.name = 'quantity';
  quantityInput.value = '1';
  form.appendChild(quantityInput);
  
  const redirectInput = document.createElement('input');
  redirectInput.type = 'hidden';
  redirectInput.name = 'redirect';
  redirectInput.value = 'checkout.php';
  form.appendChild(redirectInput);
  
  document.body.appendChild(form);
  form.submit();
}

// === Featured Bottles Carousel ===
(function() {
    const carouselWrapper = document.getElementById('carousel-wrapper');
    const productsGrid = document.getElementById('products-grid');
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    
    if (!carouselWrapper || !productsGrid) return;
    
    let scrollPosition = 0;
    let isScrolling = false;
    let animationFrame = null;
    
    // Get the first product card width + gap for smooth scrolling
    const getCardWidth = () => {
        const firstCard = productsGrid.querySelector('.product-card');
        if (!firstCard) return 304;
        const gap = parseInt(window.getComputedStyle(productsGrid).gap) || 24;
        return firstCard.offsetWidth + gap;
    };
    
    // Manual navigation with smooth scroll
    const scrollTo = (direction) => {
        if (isScrolling) return;
        isScrolling = true;
        
        const cardWidth = getCardWidth();
        const maxScroll = productsGrid.scrollWidth / 2;
        
        // Temporarily pause CSS animation
        productsGrid.style.animationPlayState = 'paused';
        
        const currentTransform = window.getComputedStyle(productsGrid).transform;
        const currentX = currentTransform === 'none' ? 0 : parseFloat(currentTransform.split(',')[4]) || 0;
        const currentPos = Math.abs(currentX);
        
        const targetPos = direction === 'next' 
            ? currentPos + cardWidth 
            : Math.max(0, currentPos - cardWidth);
        
        const finalPos = targetPos >= maxScroll ? 0 : targetPos;
        scrollPosition = finalPos;
        
        productsGrid.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        productsGrid.style.transform = `translateX(-${scrollPosition}px)`;
        
        setTimeout(() => {
            isScrolling = false;
            // Resume CSS animation if not hovered
            if (!carouselWrapper.matches(':hover')) {
                productsGrid.style.animationPlayState = 'running';
                productsGrid.style.transition = '';
            }
        }, 600);
    };
    
    // Button navigation
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            scrollTo('prev');
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            scrollTo('next');
        });
    }
    
    // Touch/swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    carouselWrapper.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        productsGrid.style.animationPlayState = 'paused';
    }, { passive: true });
    
    carouselWrapper.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });
    
    const handleSwipe = () => {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                scrollTo('next');
            } else {
                scrollTo('prev');
            }
        } else {
            // Resume animation if swipe was too small
            setTimeout(() => {
                if (!carouselWrapper.matches(':hover')) {
                    productsGrid.style.animationPlayState = 'running';
                }
            }, 100);
        }
    };
    
    // Enhanced hover pause (CSS handles most of it, but we add smooth transition)
    carouselWrapper.addEventListener('mouseenter', () => {
        productsGrid.style.animationPlayState = 'paused';
    });
    
    carouselWrapper.addEventListener('mouseleave', () => {
        productsGrid.style.animationPlayState = 'running';
    });
})();
</script>

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
    position: relative;
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

body.blurred {
    overflow: hidden;
}
</style>

<div class="login-popup-overlay" id="loginPopup">
    <div class="login-popup-modal">
        <button class="login-popup-close" onclick="closeLoginPopup()" title="Close">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Toggle Buttons at Top -->
        <div class="toggle-buttons">
            <button type="button" class="toggle-btn active" id="loginToggleBtn" onclick="showLoginForm()">
                Login
            </button>
            <button type="button" class="toggle-btn" id="registerToggleBtn" onclick="showRegisterForm()">
                Register
            </button>
        </div>
        
        <!-- Login Form -->
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
        
        <!-- Register Form -->
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
    let hasShown = false;
    
    const loginFormContainer = document.getElementById('loginFormContainer');
    const registerFormContainer = document.getElementById('registerFormContainer');
    const registerForm = document.getElementById('registerPopupForm');
    const registerErrorDiv = document.getElementById('registerError');
    const loginToggleBtn = document.getElementById('loginToggleBtn');
    const registerToggleBtn = document.getElementById('registerToggleBtn');
    
    window.showLoginPopup = function() {
        popup.classList.add('show');
        document.body.classList.add('blurred');
        // Show login form by default
        showLoginForm();
    };
    
    window.closeLoginPopup = function() {
        popup.classList.remove('show');
        document.body.classList.remove('blurred');
    };
    
    // Show popup after 6-7 seconds
    setTimeout(() => {
        if (!hasShown) {
            window.showLoginPopup();
            hasShown = true;
        }
    }, 6500);
    
    window.showRegisterForm = function() {
        loginFormContainer.style.display = 'none';
        registerFormContainer.style.display = 'block';
        registerErrorDiv.style.display = 'none';
        loginToggleBtn.classList.remove('active');
        registerToggleBtn.classList.add('active');
    };
    
    window.showLoginForm = function() {
        registerFormContainer.style.display = 'none';
        loginFormContainer.style.display = 'block';
        errorDiv.style.display = 'none';
        registerToggleBtn.classList.remove('active');
        loginToggleBtn.classList.add('active');
    };
    
    // Close on overlay click
    popup.addEventListener('click', function(e) {
        if (e.target === popup) {
            closeLoginPopup();
        }
    });
    
    // Handle login form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        errorDiv.style.display = 'none';
        
        const email = document.getElementById('popupEmail').value;
        const password = document.getElementById('popupPassword').value;
        
        try {
            const response = await fetch('api/login_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload page to update UI
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
    
    // Handle register form submission
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        registerErrorDiv.style.display = 'none';
        
        const name = document.getElementById('popupName').value.trim();
        const email = document.getElementById('popupRegEmail').value;
        const password = document.getElementById('popupRegPassword').value;
        const confirm = document.getElementById('popupConfirmPassword').value;
        
        // Client-side validation
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
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ name, email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Redirect to OTP verification page
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
    
    // Prevent popup from showing if user logs in via other means
    window.addEventListener('focus', function() {
        if (document.body.classList.contains('user-logged-in')) {
            hasShown = true;
            closeLoginPopup();
        }
    });
    
    // Handle profile icon click for login popup
    document.addEventListener('DOMContentLoaded', function() {
        const profileLoginBtn = document.getElementById('profileLoginBtn');
        if (profileLoginBtn) {
            profileLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.showLoginPopup();
                return false;
            });
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>