<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;
$currentPage = 'shop';

// Get all categories
$catStmt = db()->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$allCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Get min and max prices
$priceStmt = db()->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
$priceRange = $priceStmt->fetch(PDO::FETCH_ASSOC);
$minPrice = $priceRange['min_price'] ?? 0;
$maxPrice = $priceRange['max_price'] ?? 1000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>Bottle | Shop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      background: #0c0c0c;
      color: #f0f0f0;
      padding-top: 0;
      font-size: 14px;
    }
    
    /* Apply home page styles for laptop/desktop only */
    @media (min-width: 1024px) {
      html {
        font-size: 16px;
      }
      
      body {
        font-family: 'Space Grotesk', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: #f5f5f5;
        background: #0B0C10;
        line-height: 1.6;
        font-size: 16px;
      }
      
      h1, h2, h3, h4, h5, h6 {
        font-family: 'Space Grotesk', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      }
      
      h2 {
        font-size: 2.5rem;
        font-weight: 700;
        letter-spacing: -0.5px;
        margin-bottom: 3rem;
      }
    }
    a { text-decoration: none; color: inherit; }
    img { max-width: 100%; display: block; }

    /* Breadcrumb Section */
    .breadcrumb-section {
      background: #1a1a1a;
      padding: clamp(2rem, 6vw, 3rem) clamp(4%, 8vw, 8%);
      text-align: center;
    }
    .breadcrumb-section h1 {
      font-size: clamp(1.5rem, 5vw, 2.25rem);
      font-weight: 700;
      color: #fff;
      margin: 0 0 clamp(0.5rem, 1vw, 0.75rem);
    }
    .breadcrumb-section p {
      color: #999;
      margin: 0;
    }

    /* Main Container */
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: clamp(1rem, 3vw, 2rem) clamp(2%, 3vw, 4%);
      padding-top: clamp(80px, 12vw, 120px);
      width: 100%;
      box-sizing: border-box;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: clamp(1rem, 3vw, 1.5rem) clamp(3%, 4vw, 5%);
        padding-top: clamp(70px, 10vw, 90px);
        max-width: 100%;
      }
    }
    
    @media (max-width: 480px) {
      .container {
        padding: clamp(0.75rem, 2vw, 1rem) clamp(4%, 5vw, 6%);
        padding-top: clamp(65px, 9vw, 80px);
      }
    }
    
    @media (max-width: 360px) {
      .container {
        padding: clamp(0.75rem, 2vw, 1rem) clamp(3%, 4vw, 5%);
      }
    }
    
    /* Filter Button Container */
    .filter-button-container {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 clamp(2%, 3vw, 4%);
      padding-top: clamp(100px, 15vw, 120px);
      padding-bottom: clamp(0.5rem, 1vw, 0.75rem);
      position: relative;
      z-index: 1;
      display: none;
      box-sizing: border-box;
    }
    
    @media (max-width: 1023px) {
      .filter-button-container {
        display: block;
        padding-top: clamp(90px, 14vw, 110px);
      }
    }
    
    @media (max-width: 768px) {
      .filter-button-container {
        padding: 0 clamp(3%, 4vw, 5%);
        padding-top: clamp(85px, 13vw, 100px);
        padding-bottom: clamp(0.75rem, 1.5vw, 1rem);
        max-width: 100%;
      }
    }
    
    @media (max-width: 480px) {
      .filter-button-container {
        padding: 0 clamp(4%, 5vw, 6%);
        padding-top: clamp(80px, 12vw, 95px);
        padding-bottom: clamp(0.5rem, 1vw, 0.75rem);
      }
    }
    
    @media (max-width: 360px) {
      .filter-button-container {
        padding: 0 clamp(3%, 4vw, 5%);
        padding-top: clamp(75px, 11vw, 90px);
      }
    }
    
    @media (min-width: 1024px) {
      .filter-button-container {
        display: none;
      }
    }

    /* Grid Layout */
    .shop-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: clamp(1.5rem, 4vw, 2rem);
      width: 100%;
      margin: 0 auto;
      box-sizing: border-box;
    }
    
    @media (min-width: 1024px) {
      .shop-grid {
      grid-template-columns: 280px 1fr;
        gap: clamp(2rem, 4vw, 2.5rem);
      }
    }

    /* Filter and Sort Row (Mobile Only) */
    .filter-sort-row {
      display: none;
      width: 100%;
      align-items: center;
      gap: 12px;
      justify-content: space-between;
    }
    
    @media (max-width: 1023px) {
      .filter-sort-row {
        display: flex;
      }
    }
    
    /* Filter Toggle Button for Mobile */
    .filter-toggle-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      padding: 0;
      background: linear-gradient(135deg, #00bcd4, #007bff);
      color: #fff;
      border: none;
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      z-index: 1;
      box-shadow: 0 2px 8px rgba(0, 188, 212, 0.3);
      flex-shrink: 0;
    }
    
    .filter-toggle-btn:hover {
      transform: translateY(-2px) scale(1.05);
      box-shadow: 0 4px 12px rgba(0, 188, 212, 0.4);
    }
    
    .filter-toggle-btn:active {
      transform: translateY(0) scale(0.95);
    }
    
    .filter-toggle-btn i {
      font-size: 1rem;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .filter-toggle-btn.active i {
      transform: rotate(180deg);
    }
    
    /* Sort Wrapper Mobile */
    .sort-wrapper-mobile {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
      min-width: 0;
    }
    
    .sort-wrapper-mobile span {
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      color: #999;
      white-space: nowrap;
      flex-shrink: 0;
    }
    
    .sort-wrapper-mobile .sort-select {
      flex: 0 0 auto;
      min-width: 0;
      max-width: 150px;
      width: 150px;
      padding: clamp(8px, 2vw, 10px) clamp(12px, 3vw, 16px);
      border-radius: clamp(6px, 1.5vw, 8px);
      background: #1a1a1a;
      border: 1px solid #333;
      color: #fff;
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 12px;
      padding-right: 36px;
    }
    
    .sort-wrapper-mobile .sort-select:hover {
      border-color: #00bcd4;
      background-color: #1f1f1f;
    }
    
    .sort-wrapper-mobile .sort-select:focus {
      outline: none;
      border-color: #00bcd4;
      box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
      background-color: #1f1f1f;
    }
    
    .sort-wrapper-mobile .sort-select option {
      background: #1a1a1a;
      color: #fff;
      padding: 8px 12px;
      max-width: 200px;
      width: auto;
    }

    /* Filter Sidebar */
    .filter-sidebar {
      transition: all 0.3s ease;
    }
    
    .filter-sidebar h2 {
      font-size: clamp(1.1rem, 3vw, 1.25rem);
      font-weight: 600;
      margin-bottom: clamp(1.25rem, 3vw, 1.5rem);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    @media (min-width: 1024px) {
      .filter-sidebar h2 {
        font-size: 1.05rem;
      }
      
      .filter-group h3 {
        font-size: 0.85rem;
      }
      
      .filter-label span {
        font-size: 0.8rem;
      }
      
      .price-display {
        font-size: 0.8rem;
      }
    }
    
    @media (max-width: 1023px) {
      .filter-sidebar {
        display: none;
        position: fixed;
        top: 80px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(11, 12, 16, 0.98);
        backdrop-filter: blur(20px);
        z-index: 999;
        padding: 20px;
        overflow-y: auto;
        max-height: calc(100vh - 80px);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        animation: slideDown 0.3s ease-out;
      }
      
      .filter-sidebar.active {
        display: block;
      }
      
      .filter-sidebar h2 {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }
      
      .filter-sidebar h2 .filter-close-btn {
        display: flex !important;
      }
      
      .filter-close-btn:hover {
        background: rgba(255, 255, 255, 0.1) !important;
      }
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .filter-group {
      margin-bottom: clamp(1.5rem, 4vw, 2rem);
    }
    .filter-group h3 {
      font-weight: 600;
      margin-bottom: clamp(0.875rem, 2vw, 1rem);
      color: #ccc;
      font-size: clamp(0.9rem, 2vw, 1rem);
    }
    .filter-options {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .filter-label {
      display: flex;
      align-items: center;
      cursor: pointer;
    }
    .filter-checkbox {
      width: 16px;
      height: 16px;
      border-radius: 2px;
      border: 2px solid #444;
      background: #1a1a1a;
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      position: relative;
      flex-shrink: 0;
    }
    .filter-checkbox:checked {
      background: #00bcd4;
      border-color: #00bcd4;
    }
    .filter-checkbox:checked::after {
      content: '✓';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-size: 12px;
      font-weight: bold;
    }
    .filter-label span {
      margin-left: 12px;
      color: #999;
      font-size: 0.9rem;
    }

    /* Price Range Slider */
    .price-slider-wrapper {
      position: relative;
      height: 8px;
      background: #333;
      border-radius: 4px;
      margin-bottom: 12px;
    }
    .price-slider-track {
      position: absolute;
      height: 8px;
      background: #00bcd4;
      border-radius: 4px;
      left: 0%;
      width: 100%;
    }
    .price-slider-input {
      position: absolute;
      width: 100%;
      height: 8px;
      opacity: 0;
      cursor: pointer;
      z-index: 2;
    }
    .price-slider-handle {
      position: absolute;
      width: 16px;
      height: 16px;
      background: #00bcd4;
      border: 2px solid #0c0c0c;
      border-radius: 50%;
      top: -4px;
      cursor: pointer;
    }
    .price-display {
      font-size: 0.875rem;
      color: #999;
      margin-top: 12px;
    }

    /* Star Rating Filter */
    .star-filter {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #999;
      cursor: pointer;
      transition: 0.2s;
    }
    .star-filter:hover {
      color: #00bcd4;
    }
    .star-rating {
      display: flex;
      color: #ffc107;
    }
    .star-rating .star-empty {
      color: #444;
    }

    /* Product Section */
    .product-section {
      width: 100%;
      margin: 0 auto;
    }
    
    .product-section-header {
      display: flex;
      flex-direction: column;
      gap: clamp(12px, 3vw, 16px);
      margin-bottom: clamp(16px, 4vw, 24px);
      width: 100%;
    }
    .product-section-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: clamp(10px, 2.5vw, 16px);
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .results-count {
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      color: #999;
    }
    
    @media (min-width: 1024px) {
      .results-count {
        font-size: 0.8rem;
      }
    }
    .sort-wrapper {
      display: flex;
      align-items: center;
      gap: clamp(6px, 1.5vw, 8px);
      flex-wrap: wrap;
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
    }
    .sort-wrapper span {
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      color: #999;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .sort-select {
      padding: clamp(5px, 1.2vw, 6px) clamp(10px, 2.5vw, 12px);
      border-radius: clamp(4px, 1vw, 6px);
      background: #1a1a1a;
      border: 1px solid #333;
      color: #fff;
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      cursor: pointer;
      max-width: 180px;
      width: 180px;
      box-sizing: border-box;
      min-width: 0;
      flex: 0 0 auto;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      position: relative;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 12px;
      padding-right: 36px;
    }
    
    @media (min-width: 1024px) {
      .sort-select {
        font-size: 0.8rem;
      }
      
      .sort-wrapper span {
        font-size: 0.8rem;
      }
    }
    
    .sort-select:hover {
      border-color: #00bcd4;
      background-color: #1f1f1f;
    }
    
    @media (max-width: 768px) {
      .sort-select {
        max-width: 160px;
        width: 160px;
        flex: 0 0 auto;
      }
    }
    
    @media (max-width: 480px) {
      .sort-select {
        max-width: 140px;
        width: 140px;
        flex: 0 0 auto;
      }
    }
    
    /* Ensure dropdown options don't overflow */
    .sort-select option {
      max-width: 200px;
      width: auto;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      padding: 8px 12px;
      background: #1a1a1a;
      color: #fff;
    }
    
    /* Hide desktop sort wrapper on mobile */
    @media (max-width: 1023px) {
      .sort-wrapper {
        display: none;
      }
    }
    
    /* For mobile devices - reduce dropdown width */
    @media (max-width: 768px) {
      .sort-wrapper {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        justify-content: flex-start;
      }
      
      .sort-select {
        max-width: 160px;
        width: 160px;
        min-width: 0;
        flex: 0 0 auto;
        box-sizing: border-box;
      }
    }
    
    @media (max-width: 480px) {
      .sort-wrapper {
        flex-direction: row;
        align-items: center;
        gap: clamp(6px, 1.5vw, 8px);
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        flex-wrap: wrap;
      }
      
      .sort-wrapper span {
        width: auto;
        flex-shrink: 0;
        font-size: clamp(0.7rem, 1.8vw, 0.75rem);
      }
      
      .sort-select {
        max-width: 140px;
        width: 140px;
        min-width: 0;
        flex: 0 0 auto;
        box-sizing: border-box;
      }
    }
    
    @media (max-width: 360px) {
      .sort-wrapper {
        gap: clamp(4px, 1vw, 6px);
      }
      
      .sort-wrapper span {
        font-size: clamp(0.65rem, 1.6vw, 0.7rem);
      }
      
      .sort-select {
        max-width: 120px;
        width: 120px;
        font-size: clamp(0.65rem, 1.6vw, 0.75rem);
        padding: clamp(4px, 1vw, 5px) clamp(6px, 1.5vw, 8px);
      }
    }

    /* Active Filters */
    .active-filters {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: clamp(6px, 1.5vw, 8px);
      margin-bottom: clamp(16px, 4vw, 24px);
    }
    .active-filters-label {
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      font-weight: 500;
      color: #ccc;
    }
    .filter-tag {
      display: flex;
      align-items: center;
      background: #00bcd4;
      color: white;
      font-size: clamp(0.7rem, 1.8vw, 0.75rem);
      font-weight: 600;
      padding: clamp(3px, 0.8vw, 4px) clamp(10px, 2.5vw, 12px);
      border-radius: 9999px;
      gap: clamp(6px, 1.5vw, 8px);
    }
    .filter-tag button {
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      cursor: pointer;
      font-size: 16px;
      line-height: 1;
      padding: 0;
      margin-left: 4px;
    }
    .filter-tag button:hover {
      color: white;
    }
    .clear-all {
      font-size: 0.875rem;
      color: #999;
      text-decoration: underline;
      cursor: pointer;
    }
    .clear-all:hover {
      color: #00bcd4;
    }

    /* Products Grid */
    .products-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: clamp(0.75rem, 2vw, 1rem);
      width: 100%;
      margin: 0 auto;
    }
    
    @media (min-width: 360px) {
      .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: clamp(0.875rem, 2.5vw, 1.25rem);
      }
    }
    
    @media (min-width: 480px) {
      .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: clamp(1rem, 2.5vw, 1.25rem);
      }
    }
    
    @media (min-width: 768px) {
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: clamp(1rem, 2.5vw, 1.5rem);
      }
    }
    
    @media (min-width: 1024px) {
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: clamp(1.25rem, 3vw, 1.5rem);
      }
    }

    /* Product Card */
    .product-card {
      background: #141414;
      border-radius: clamp(6px, 1.5vw, 8px);
      overflow: hidden;
      transition: 0.3s;
      position: relative;
      cursor: pointer;
      width: 100%;
      max-width: 100%;
      margin: 0 auto;
    }
    .product-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0, 188, 212, 0.2);
    }
    .product-image-wrapper {
      position: relative;
      width: 100%;
      height: clamp(180px, 45vw, 256px);
      overflow: hidden;
      background: #0f0f0f;
    }
    
    @media (max-width: 480px) {
      .product-image-wrapper {
        height: clamp(200px, 50vw, 240px);
      }
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
    
    /* Always visible on small devices */
    @media (max-width: 768px) {
      .product-actions {
        opacity: 1 !important;
        position: absolute;
        top: 8px;
        right: 8px;
        gap: 6px;
      }
    }
    
    @media (max-width: 480px) {
      .product-actions {
        opacity: 1 !important;
        top: 6px;
        right: 6px;
        gap: 5px;
      }
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
    
    /* Enhanced visibility on small devices */
    @media (max-width: 768px) {
      .product-action-btn {
        width: clamp(36px, 8vw, 38px);
        height: clamp(36px, 8vw, 38px);
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
        font-size: clamp(1rem, 2.5vw, 1.1rem);
      }
      
      .product-action-btn:active {
        transform: scale(0.95);
        background: #00bcd4;
        color: white;
      }
    }
    
    @media (max-width: 480px) {
      .product-action-btn {
        width: clamp(34px, 7vw, 36px);
        height: clamp(34px, 7vw, 36px);
        font-size: clamp(0.9rem, 2vw, 1rem);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
      }
    }
    .product-info {
      padding: clamp(12px, 3vw, 16px);
    }
    .product-category {
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      color: #999;
      margin-bottom: clamp(3px, 0.8vw, 4px);
    }
    
    @media (min-width: 1024px) {
      .product-category {
        font-size: 0.75rem;
      }
    }
    .product-name {
      font-weight: 600;
      color: #fff;
      margin: clamp(3px, 0.8vw, 4px) 0;
      font-size: clamp(0.9rem, 2.5vw, 1rem);
      line-height: 1.4;
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
      font-size: clamp(1rem, 2.5vw, 1.125rem);
      color: #00bcd4;
    }
    
    @media (min-width: 1024px) {
      .price-current {
        font-size: 0.95rem;
      }
    }
    
    .price-original {
      font-size: clamp(0.75rem, 2vw, 0.875rem);
      color: #666;
      text-decoration: line-through;
    }
    
    @media (min-width: 1024px) {
      .price-original {
        font-size: 0.7rem;
      }
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
      font-weight: 500;
      color: #ccc;
      margin-left: 4px;
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: clamp(6px, 1.5vw, 8px);
      margin-top: clamp(2rem, 5vw, 3rem);
      width: 100%;
      flex-wrap: wrap;
    }
    
    @media (max-width: 480px) {
      .pagination {
        gap: clamp(4px, 1vw, 6px);
        margin-top: clamp(1.5rem, 4vw, 2rem);
      }
      
      .pagination a,
      .pagination span {
        padding: clamp(6px, 1.5vw, 8px) clamp(10px, 2.5vw, 12px);
        min-width: clamp(36px, 9vw, 40px);
        height: clamp(36px, 9vw, 40px);
        font-size: clamp(0.8rem, 2vw, 0.875rem);
      }
    }
    .pagination a,
    .pagination span {
      padding: 8px 12px;
      border-radius: 9999px;
      color: #999;
      transition: 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 40px;
      height: 40px;
    }
    .pagination a:hover {
      background: #1a1a1a;
      color: #00bcd4;
    }
    .pagination .active {
      background: #00bcd4;
      color: white;
      font-weight: 600;
    }

    /* Footer Section */
    .features-section {
      background: #1a1a1a;
      padding: clamp(2rem, 5vw, 3rem) clamp(4%, 6vw, 8%);
      margin-top: clamp(2rem, 5vw, 3rem);
      width: 100%;
      box-sizing: border-box;
    }
    
    @media (max-width: 480px) {
      .features-section {
        padding: clamp(1.5rem, 4vw, 2rem) clamp(4%, 5vw, 6%);
    }
    }
    
    .features-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: clamp(1.5rem, 4vw, 2rem);
      text-align: center;
      max-width: 100%;
      margin: 0 auto;
    }
    
    @media (min-width: 480px) {
      .features-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: clamp(1.75rem, 4vw, 2rem);
      }
    }
    
    @media (min-width: 768px) {
      .features-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: clamp(2rem, 4vw, 2.5rem);
      }
    }
    .feature-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      max-width: 100%;
      margin: 0 auto;
    }
    
    @media (max-width: 480px) {
      .feature-item {
        padding: 0 clamp(1rem, 3vw, 1.5rem);
      }
    }
    .feature-icon {
      width: clamp(50px, 12vw, 60px);
      height: clamp(50px, 12vw, 60px);
      background: rgba(0, 188, 212, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: clamp(12px, 3vw, 16px);
    }
    .feature-icon .material-symbols-outlined {
      font-size: clamp(1.5rem, 4vw, 2rem);
      color: #00bcd4;
    }
    .feature-title {
      font-weight: 600;
      font-size: clamp(1rem, 2.5vw, 1.125rem);
      color: #fff;
      margin-bottom: clamp(6px, 1.5vw, 8px);
    }
    .feature-desc {
      font-size: clamp(0.8rem, 2vw, 0.875rem);
      color: #999;
      line-height: 1.5;
    }

    @media (max-width: 1024px) {
      .shop-grid {
        grid-template-columns: 1fr;
      }
      
      .filter-sidebar {
        order: 2;
      }
      
      .product-section {
        order: 1;
    }
    }
    
    @media (max-width: 768px) {
      .product-section-top {
        flex-direction: column;
        align-items: flex-start;
        gap: clamp(0.75rem, 2vw, 1rem);
      }
      
      .product-section-header {
        margin-bottom: clamp(1rem, 3vw, 1.5rem);
      }
    }
    
    @media (max-width: 480px) {
      .products-grid {
        grid-template-columns: 1fr;
        gap: clamp(1rem, 2.5vw, 1.25rem);
        max-width: 100%;
      }
      
      .product-section {
        width: 100%;
      }
      
      .product-section-top {
        gap: clamp(0.75rem, 2vw, 1rem);
        flex-direction: column;
        align-items: stretch;
      }
      
      .results-count {
        font-size: clamp(0.75rem, 2vw, 0.85rem);
        text-align: center;
      }
      
      .sort-wrapper {
        width: 100%;
        max-width: 100%;
        flex-wrap: wrap;
        justify-content: flex-start;
        box-sizing: border-box;
      }
      
      .sort-select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
      }
      
      .active-filters {
        justify-content: center;
        text-align: center;
      }
    }
    
    @media (max-width: 360px) {
      .products-grid {
        gap: clamp(0.875rem, 2vw, 1rem);
      }
      
      .product-info {
        padding: clamp(12px, 3vw, 14px);
      }
      
      .product-card {
        max-width: 100%;
      }
    }
  </style>
</head>

<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Breadcrumb Section
<div class="breadcrumb-section">
  <h1>Shop</h1>
  <p>Home / Shop</p>
</div> -->

<!-- Filter Toggle Button Container (Mobile Only) -->
<div class="filter-button-container">
  <div class="filter-sort-row">
    <div class="sort-wrapper-mobile">
      <span>Sort by:</span>
      <select class="sort-select" id="sortSelectMobile" onchange="updateSort()">
        <?php
        $selectedSort = $_GET['sort'] ?? '';
        ?>
        <option value="" <?= $selectedSort === '' ? 'selected' : '' ?>>Default Sorting</option>
        <option value="price_asc" <?= $selectedSort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
        <option value="price_desc" <?= $selectedSort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        <option value="rating_desc" <?= $selectedSort === 'rating_desc' ? 'selected' : '' ?>>Highest Rated</option>
        <option value="rating_asc" <?= $selectedSort === 'rating_asc' ? 'selected' : '' ?>>Lowest Rated</option>
        <option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
      </select>
    </div>
    <button class="filter-toggle-btn" id="filterToggleBtn" onclick="toggleFilterSidebar()">
      <i class="fas fa-filter"></i>
    </button>
  </div>
</div>

<!-- Main Container -->
<div class="container">
  <div class="shop-grid">
    <!-- Filter Sidebar -->
    <aside class="filter-sidebar" id="filterSidebar">
      <h2>
        <span>Filter Options</span>
        <button class="filter-close-btn" onclick="toggleFilterSidebar()" style="display: none; background: transparent; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; padding: 0; width: 32px; height: 32px; align-items: center; justify-content: center; border-radius: 50%; transition: 0.3s;">
          <i class="fas fa-times"></i>
        </button>
      </h2>

      <!-- Categories -->
      <div class="filter-group">
        <h3>By Categories</h3>
        <div class="filter-options">
          <?php
          $selectedCategories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
          foreach ($allCategories as $cat) {
            $checked = in_array($cat, $selectedCategories) ? 'checked' : '';
            echo "<label class='filter-label'>
              <input type='checkbox' class='filter-checkbox' name='category' value='" . htmlspecialchars($cat) . "' $checked onchange='updateFilters()'>
              <span>" . htmlspecialchars($cat) . "</span>
            </label>";
          }
          ?>
        </div>
      </div>

      <!-- Price Range -->
      <div class="filter-group">
        <h3>Price</h3>
        <div class="price-slider-wrapper">
          <div class="price-slider-track" id="priceTrack"></div>
          <?php
          $urlPriceMin = isset($_GET['price_min']) ? floatval($_GET['price_min']) : $minPrice;
          $urlPriceMax = isset($_GET['price_max']) ? floatval($_GET['price_max']) : $maxPrice;
          ?>
          <input type="range" class="price-slider-input" id="priceMin" min="<?= $minPrice ?>" max="<?= $maxPrice ?>" value="<?= $urlPriceMin ?>" oninput="updatePriceSlider()">
          <input type="range" class="price-slider-input" id="priceMax" min="<?= $minPrice ?>" max="<?= $maxPrice ?>" value="<?= $urlPriceMax ?>" oninput="updatePriceSlider()">
          <div class="price-slider-handle" id="handleMin" style="left: 0%;"></div>
          <div class="price-slider-handle" id="handleMax" style="right: 0%;"></div>
        </div>
        <p class="price-display" id="priceDisplay">₹<?= number_format($urlPriceMin, 2) ?> - ₹<?= number_format($urlPriceMax, 2) ?></p>
      </div>

      <!-- Review Rating -->
      <div class="filter-group">
        <h3>Review</h3>
        <div class="filter-options">
          <?php 
          $selectedRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
          for ($i = 5; $i >= 1; $i--): 
            $isSelected = ($selectedRating == $i) ? 'style="color: #00bcd4; font-weight: 600;"' : '';
          ?>
          <a href="#" class="star-filter" onclick="filterByRating(<?= $i ?>); return false;" <?= $isSelected ?>>
            <div class="star-rating">
              <?php for ($j = 1; $j <= 5; $j++): ?>
                <span class="star <?= $j > $i ? 'star-empty' : '' ?>">★</span>
              <?php endfor; ?>
            </div>
            <span><?= $i ?> Star</span>
          </a>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Promotions -->
      <div class="filter-group">
        <h3>By Promotions</h3>
        <div class="filter-options">
          <?php
          $selectedPromos = isset($_GET['promos']) ? explode(',', $_GET['promos']) : ['bestseller'];
          ?>
          <label class="filter-label">
            <input type="checkbox" class="filter-checkbox" name="promo" value="new" <?= in_array('new', $selectedPromos) ? 'checked' : '' ?> onchange="updateFilters()">
            <span>New Arrivals</span>
          </label>
          <label class="filter-label">
            <input type="checkbox" class="filter-checkbox" name="promo" value="bestseller" <?= in_array('bestseller', $selectedPromos) ? 'checked' : '' ?> onchange="updateFilters()">
            <span>Best Sellers</span>
          </label>
          <label class="filter-label">
            <input type="checkbox" class="filter-checkbox" name="promo" value="sale" <?= in_array('sale', $selectedPromos) ? 'checked' : '' ?> onchange="updateFilters()">
            <span>On Sale</span>
          </label>
        </div>
      </div>

      <!-- Availability -->
      <div class="filter-group">
        <h3>Availability</h3>
        <div class="filter-options">
          <?php
          $selectedAvailability = isset($_GET['availability']) ? explode(',', $_GET['availability']) : ['instock'];
          ?>
          <label class="filter-label">
            <input type="checkbox" class="filter-checkbox" name="availability" value="instock" <?= in_array('instock', $selectedAvailability) ? 'checked' : '' ?> onchange="updateFilters()">
            <span>In Stock</span>
          </label>
          <label class="filter-label">
            <input type="checkbox" class="filter-checkbox" name="availability" value="outstock" <?= in_array('outstock', $selectedAvailability) ? 'checked' : '' ?> onchange="updateFilters()">
            <span>Out of Stocks</span>
          </label>
        </div>
      </div>
    </aside>

    <!-- Product Section -->
    <div class="product-section">
      <!-- Header -->
      <div class="product-section-header">
        <div class="product-section-top">
          <?php
          // Calculate total filtered products count
          $countQuery = "SELECT COUNT(*) FROM products WHERE is_active = 1";
          $countParams = [];
          
          // Category filter
          if (!empty($_GET['categories'])) {
            $selectedCategories = explode(',', $_GET['categories']);
            $selectedCategories = array_map('trim', $selectedCategories);
            $selectedCategories = array_filter($selectedCategories);
            if (!empty($selectedCategories)) {
              $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
              $countQuery .= " AND category IN ($placeholders)";
              $countParams = array_merge($countParams, $selectedCategories);
            }
          }
          if (empty($_GET['categories']) && !empty($_GET['cat'])) {
            $countQuery .= " AND category = ?";
            $countParams[] = $_GET['cat'];
          }
          
          // Price filter
          $countPriceMin = isset($_GET['price_min']) ? floatval($_GET['price_min']) : $minPrice;
          $countPriceMax = isset($_GET['price_max']) ? floatval($_GET['price_max']) : $maxPrice;
          $countQuery .= " AND price >= ? AND price <= ?";
          $countParams[] = $countPriceMin;
          $countParams[] = $countPriceMax;
          
          // Availability filter
          if (!empty($_GET['availability'])) {
            $selectedAvailability = explode(',', $_GET['availability']);
            $availabilityConditions = [];
            if (in_array('instock', $selectedAvailability)) {
              $availabilityConditions[] = "stock > 0";
            }
            if (in_array('outstock', $selectedAvailability)) {
              $availabilityConditions[] = "stock <= 0";
            }
            if (!empty($availabilityConditions)) {
              $countQuery .= " AND (" . implode(" OR ", $availabilityConditions) . ")";
            }
          }
          
          // Get all products first for complex filters (rating, promotions)
          $countProductsQuery = str_replace("SELECT COUNT(*)", "SELECT *", $countQuery);
          $countProductsStmt = db()->prepare($countProductsQuery);
          $countProductsStmt->execute($countParams);
          $allProducts = $countProductsStmt->fetchAll(PDO::FETCH_ASSOC);
          
          // Apply rating filter if set
          $countSelectedRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
          if ($countSelectedRating >= 1 && $countSelectedRating <= 5) {
            $allProducts = array_filter($allProducts, function($p) use ($countSelectedRating) {
              // Use getProductRating function
              $rating = getProductRating($p['id']);
              $ratingInt = intval(floor($rating));
              return $ratingInt == $countSelectedRating;
            });
          }
          
          // Apply promotion filters if set
          if (!empty($_GET['promos'])) {
            $selectedPromos = explode(',', $_GET['promos']);
            $allProducts = array_filter($allProducts, function($p) use ($selectedPromos) {
              $matches = false;
              
              if (in_array('new', $selectedPromos)) {
                // New = created in last 30 days
                $createdDate = strtotime($p['created_at']);
                if ($createdDate > (time() - 30 * 24 * 60 * 60)) {
                  $matches = true;
                }
              }
              
              if (in_array('bestseller', $selectedPromos)) {
                // Best seller = has been ordered
                try {
                  $orderStmt = db()->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
                  $orderStmt->execute([$p['id']]);
                  $orderCount = $orderStmt->fetchColumn();
                  if ($orderCount > 0 || $p['id'] <= 10) {
                    $matches = true;
                  }
                } catch (Exception $e) {
                  if ($p['id'] <= 10) {
                    $matches = true;
                  }
                }
              }
              
              if (in_array('sale', $selectedPromos)) {
                // Sale = on discount (price less than average)
                try {
                  $avgPriceStmt = db()->query("SELECT AVG(price) as avg_price FROM products WHERE is_active = 1");
                  $avgPrice = $avgPriceStmt->fetch(PDO::FETCH_ASSOC)['avg_price'] ?? 500;
                  if ($p['price'] < $avgPrice * 0.8) {
                    $matches = true;
                  }
                } catch (Exception $e) {
                  if ($p['price'] < 500) {
                    $matches = true;
                  }
                }
              }
              
              return $matches;
            });
          }
          
          $totalFilteredProducts = count($allProducts);
          ?>
          <p class="results-count" id="resultsCount">Showing 1-<?= $totalFilteredProducts ?> of <?= $totalFilteredProducts ?> results</p>
          <div class="sort-wrapper">
            <span>Sort by:</span>
            <select class="sort-select" id="sortSelect" onchange="updateSort()" style="max-width: 100%; width: 100%;">
              <?php
              $selectedSort = $_GET['sort'] ?? '';
              ?>
              <option value="" <?= $selectedSort === '' ? 'selected' : '' ?>>Default Sorting</option>
              <option value="price_asc" <?= $selectedSort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_desc" <?= $selectedSort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="rating_desc" <?= $selectedSort === 'rating_desc' ? 'selected' : '' ?>>Highest Rated</option>
              <option value="rating_asc" <?= $selectedSort === 'rating_asc' ? 'selected' : '' ?>>Lowest Rated</option>
              <option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
            </select>
          </div>
        </div>

        <!-- Active Filters -->
        <div class="active-filters" id="activeFilters">
          <span class="active-filters-label">Active Filter:</span>
          <!-- Will be populated by JavaScript -->
        </div>
      </div>

      <!-- Products Grid -->
      <div class="products-grid" id="productsGrid">
        <?php
        $query = "SELECT * FROM products WHERE is_active = 1";
        $params = [];

        // Category filter - handle multiple categories
        if (!empty($_GET['categories'])) {
          $selectedCategories = explode(',', $_GET['categories']);
          $selectedCategories = array_map('trim', $selectedCategories);
          $selectedCategories = array_filter($selectedCategories);
          if (!empty($selectedCategories)) {
            $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
            $query .= " AND category IN ($placeholders)";
            $params = array_merge($params, $selectedCategories);
          }
        }

        // Also support single 'cat' parameter for backward compatibility
        if (empty($_GET['categories']) && !empty($_GET['cat'])) {
          $query .= " AND category = ?";
          $params[] = $_GET['cat'];
        }

        if (!empty($_GET['search'])) {
          $query .= " AND name LIKE ?";
          $params[] = "%" . $_GET['search'] . "%";
        }

        // Availability filter
        if (!empty($_GET['availability'])) {
          $selectedAvailability = explode(',', $_GET['availability']);
          $availabilityConditions = [];
          if (in_array('instock', $selectedAvailability)) {
            $availabilityConditions[] = "stock > 0";
          }
          if (in_array('outstock', $selectedAvailability)) {
            $availabilityConditions[] = "stock <= 0";
          }
          if (!empty($availabilityConditions)) {
            $query .= " AND (" . implode(" OR ", $availabilityConditions) . ")";
          }
        }

        // Price filter
        $priceMin = isset($_GET['price_min']) ? floatval($_GET['price_min']) : $minPrice;
        $priceMax = isset($_GET['price_max']) ? floatval($_GET['price_max']) : $maxPrice;
        $query .= " AND price >= ? AND price <= ?";
        $params[] = $priceMin;
        $params[] = $priceMax;

        // Rating filter - use consistent rating based on product ID
        // Rating is calculated as: ((product_id * 7) % 50) / 10 + 4, which gives 4.0 to 4.9 range
        if (!empty($_GET['rating'])) {
          $selectedRating = intval($_GET['rating']);
          if ($selectedRating >= 1 && $selectedRating <= 5) {
            // Filter products where calculated rating matches selected rating
            // We'll filter after fetching, or use HAVING clause with calculated field
            $minRating = floatval($selectedRating);
            $maxRating = floatval($selectedRating + 0.99);
            // We'll filter this in PHP after calculating ratings
          }
        }

        // Sort
        $orderBy = "ORDER BY id DESC";
        if (!empty($_GET['sort'])) {
          switch ($_GET['sort']) {
            case 'price_asc':
              $orderBy = "ORDER BY price ASC";
              break;
            case 'price_desc':
              $orderBy = "ORDER BY price DESC";
              break;
            case 'rating_desc':
              // Will sort by calculated rating after fetching
              $orderBy = "ORDER BY id DESC";
              break;
            case 'rating_asc':
              // Will sort by calculated rating after fetching
              $orderBy = "ORDER BY id DESC";
              break;
            case 'newest':
              $orderBy = "ORDER BY id DESC";
              break;
          }
        }
        $query .= " " . $orderBy;

        $stmt = db()->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $csrf = csrf_token();

        // Helper function to get average rating from reviews table (accurate manual calculation)
        function getProductRating($productId) {
          try {
            // Fetch all reviews for accurate calculation
            $ratingStmt = db()->prepare("
              SELECT rating 
              FROM reviews 
              WHERE product_id = ? AND rating >= 1 AND rating <= 5
            ");
            $ratingStmt->execute([$productId]);
            $ratings = $ratingStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($ratings) > 0) {
              // Convert to integers and calculate exact average
              $intRatings = array_map('intval', $ratings);
              $ratingSum = array_sum($intRatings);
              $ratingCount = count($intRatings);
              $rating = $ratingSum / $ratingCount;
              // Round to 1 decimal place (4.666... becomes 4.7)
              $rating = round($rating, 1);
              return max(1.0, min(5.0, $rating)); // Ensure 1.0-5.0 range
            } else {
              // No reviews - return 0 (no default/fallback rating)
              return 0;
            }
          } catch (PDOException $e) {
            // If reviews table doesn't exist, return 0
            return 0;
          }
        }

        // Apply rating filter if set
        $selectedRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
        if ($selectedRating >= 1 && $selectedRating <= 5) {
          $products = array_filter($products, function($p) use ($selectedRating) {
            $rating = getProductRating($p['id']);
            $ratingInt = intval(floor($rating));
            return $ratingInt == $selectedRating;
          });
          $products = array_values($products); // Re-index array
        }

        // Apply promotion filters if set
        if (!empty($_GET['promos'])) {
          $selectedPromos = explode(',', $_GET['promos']);
          $products = array_filter($products, function($p) use ($selectedPromos) {
            $matches = false;
            
            if (in_array('new', $selectedPromos)) {
              // New = created in last 30 days
              $createdDate = strtotime($p['created_at']);
              if ($createdDate > (time() - 30 * 24 * 60 * 60)) {
                $matches = true;
              }
            }
            
            if (in_array('bestseller', $selectedPromos)) {
              // Best seller = has been ordered (simplified: ID < 10 or check order_items)
              try {
                $orderStmt = db()->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
                $orderStmt->execute([$p['id']]);
                $orderCount = $orderStmt->fetchColumn();
                if ($orderCount > 0 || $p['id'] <= 10) {
                  $matches = true;
                }
              } catch (Exception $e) {
                // Fallback logic
                if ($p['id'] <= 10) {
                  $matches = true;
                }
              }
            }
            
            if (in_array('sale', $selectedPromos)) {
              // Sale = on discount (price less than average or specific threshold)
              $avgPriceStmt = db()->query("SELECT AVG(price) as avg_price FROM products WHERE is_active = 1");
              $avgPrice = $avgPriceStmt->fetch(PDO::FETCH_ASSOC)['avg_price'] ?? 500;
              if ($p['price'] < $avgPrice * 0.8) { // 20% below average
                $matches = true;
              }
            }
            
            return $matches;
          });
          $products = array_values($products); // Re-index array
        }

        // Apply rating sorting if selected
        if (!empty($_GET['sort']) && ($_GET['sort'] == 'rating_desc' || $_GET['sort'] == 'rating_asc')) {
          usort($products, function($a, $b) {
            $ratingA = getProductRating($a['id']);
            $ratingB = getProductRating($b['id']);
            if ($_GET['sort'] == 'rating_desc') {
              return $ratingB <=> $ratingA; // Descending
            } else {
              return $ratingA <=> $ratingB; // Ascending
            }
          });
        }

        $totalProducts = count($products);

        if ($totalProducts > 0) {
          foreach ($products as $p) {
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
            $rating = getProductRating($p['id']); // Get rating (0 if no reviews, actual rating if reviews exist)
            $ratingDisplay = '';
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
                    <input type='hidden' name='redirect' value='category.php'>
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
          echo "<p style='text-align:center;width:100%;color:#777;grid-column:1/-1;'>No products found.</p>";
        }
        ?>
      </div>

      <!-- Pagination -->
      <nav class="pagination">
        <a href="#"><span class="material-symbols-outlined">chevron_left</span></a>
        <a href="#" class="active">1</a>
        <a href="#">2</a>
        <a href="#">3</a>
        <span>...</span>
        <a href="#">10</a>
        <a href="#"><span class="material-symbols-outlined">chevron_right</span></a>
      </nav>
    </div>
  </div>
</div>

<!-- Features Section -->
<section class="features-section">
  <div class="features-grid">
    <div class="feature-item">
      <div class="feature-icon">
        <span class="material-symbols-outlined">local_shipping</span>
      </div>
      <h3 class="feature-title">Free Shipping</h3>
      <p class="feature-desc">Free shipping for order above ₹500</p>
    </div>
    <div class="feature-item">
      <div class="feature-icon">
        <span class="material-symbols-outlined">payment</span>
      </div>
      <h3 class="feature-title">Flexible Payment</h3>
      <p class="feature-desc">Multiple secure payment options</p>
    </div>
    <div class="feature-item">
      <div class="feature-icon">
        <span class="material-symbols-outlined">support_agent</span>
      </div>
      <h3 class="feature-title">24x7 Support</h3>
      <p class="feature-desc">We support online all days</p>
    </div>
  </div>
</section>

<script>
function updatePriceSlider() {
  const minInput = document.getElementById('priceMin');
  const maxInput = document.getElementById('priceMax');
  const minHandle = document.getElementById('handleMin');
  const maxHandle = document.getElementById('handleMax');
  const track = document.getElementById('priceTrack');
  const display = document.getElementById('priceDisplay');

  let min = parseFloat(minInput.value);
  let max = parseFloat(maxInput.value);

  if (min > max) {
    min = max;
    minInput.value = min;
  }

  const minPercent = ((min - parseFloat(minInput.min)) / (parseFloat(minInput.max) - parseFloat(minInput.min))) * 100;
  const maxPercent = ((max - parseFloat(maxInput.min)) / (parseFloat(maxInput.max) - parseFloat(minInput.min))) * 100;

  minHandle.style.left = minPercent + '%';
  maxHandle.style.right = (100 - maxPercent) + '%';
  track.style.left = minPercent + '%';
  track.style.width = (maxPercent - minPercent) + '%';

  display.textContent = '₹' + min.toFixed(2) + ' - ₹' + max.toFixed(2);
}

function updateFilters() {
  const categories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(cb => cb.value);
  const promos = Array.from(document.querySelectorAll('input[name="promo"]:checked')).map(cb => cb.value);
  const availability = Array.from(document.querySelectorAll('input[name="availability"]:checked')).map(cb => cb.value);
  const priceMin = document.getElementById('priceMin').value;
  const priceMax = document.getElementById('priceMax').value;
  const sort = document.getElementById('sortSelect').value;
  const currentRating = new URLSearchParams(window.location.search).get('rating');

  const params = new URLSearchParams();
  if (categories.length > 0) params.append('categories', categories.join(','));
  if (promos.length > 0) params.append('promos', promos.join(','));
  if (availability.length > 0) params.append('availability', availability.join(','));
  if (priceMin) params.append('price_min', priceMin);
  if (priceMax) params.append('price_max', priceMax);
  if (sort) params.append('sort', sort);
  if (currentRating) params.append('rating', currentRating);

  window.location.href = 'category.php?' + params.toString();
}

function updateSort() {
  // Sync both sort selects if they exist
  const sortSelect = document.getElementById('sortSelect');
  const sortSelectMobile = document.getElementById('sortSelectMobile');
  
  if (sortSelect && sortSelectMobile) {
    // Sync the one that was changed to the other
    if (event && event.target) {
      if (event.target.id === 'sortSelectMobile') {
        sortSelect.value = sortSelectMobile.value;
      } else {
        sortSelectMobile.value = sortSelect.value;
      }
    } else {
      // Fallback: sync mobile to desktop
      if (sortSelectMobile) {
        sortSelectMobile.value = sortSelect.value;
      }
    }
  }
  
  updateFilters();
}

function filterByRating(rating) {
  const categories = Array.from(document.querySelectorAll('input[name="category"]:checked')).map(cb => cb.value);
  const promos = Array.from(document.querySelectorAll('input[name="promo"]:checked')).map(cb => cb.value);
  const availability = Array.from(document.querySelectorAll('input[name="availability"]:checked')).map(cb => cb.value);
  const priceMin = document.getElementById('priceMin').value;
  const priceMax = document.getElementById('priceMax').value;
  const sort = document.getElementById('sortSelect').value;
  const currentRating = new URLSearchParams(window.location.search).get('rating');
  
  // Toggle rating - if same rating clicked, remove it
  const newRating = (currentRating && parseInt(currentRating) == rating) ? '' : rating;

  const params = new URLSearchParams();
  if (categories.length > 0) params.append('categories', categories.join(','));
  if (promos.length > 0) params.append('promos', promos.join(','));
  if (availability.length > 0) params.append('availability', availability.join(','));
  if (priceMin) params.append('price_min', priceMin);
  if (priceMax) params.append('price_max', priceMax);
  if (sort) params.append('sort', sort);
  if (newRating) params.append('rating', newRating);

  window.location.href = 'category.php?' + params.toString();
}

// Initialize price slider
updatePriceSlider();

// Sync sort selects on page load
(function() {
  const sortSelect = document.getElementById('sortSelect');
  const sortSelectMobile = document.getElementById('sortSelectMobile');
  
  if (sortSelect && sortSelectMobile) {
    // Sync mobile to desktop on load
    sortSelectMobile.value = sortSelect.value;
    
    // Keep them in sync
    sortSelect.addEventListener('change', function() {
      sortSelectMobile.value = this.value;
    });
    
    sortSelectMobile.addEventListener('change', function() {
      sortSelect.value = this.value;
    });
  }
})();

// Fix select dropdown width on mobile - ensure options don't overflow screen
(function() {
  const sortSelect = document.getElementById('sortSelect');
  if (sortSelect) {
    // Constrain select width to prevent overflow
    function constrainSelectWidth() {
      const wrapper = sortSelect.closest('.sort-wrapper');
      if (wrapper) {
        const container = wrapper.closest('.product-section-top') || wrapper.closest('.product-section-header') || wrapper.closest('.container');
        if (container) {
          const containerRect = container.getBoundingClientRect();
          const containerWidth = containerRect.width;
          const viewportWidth = window.innerWidth;
          
          if (window.innerWidth <= 768) {
            // On mobile, reduce width to percentage of container
            if (window.innerWidth <= 480) {
              if (window.innerWidth <= 360) {
                sortSelect.style.maxWidth = '65%';
              } else {
                sortSelect.style.maxWidth = '70%';
              }
            } else {
              sortSelect.style.maxWidth = '75%';
            }
            sortSelect.style.width = 'auto';
            sortSelect.style.minWidth = '0';
          } else {
            // On desktop, calculate available space
            const span = wrapper.querySelector('span');
            const spanWidth = span ? span.getBoundingClientRect().width : 0;
            const gap = 8;
            const availableWidth = containerWidth - spanWidth - gap;
            sortSelect.style.maxWidth = Math.min(availableWidth, viewportWidth - 40) + 'px'; // 40px for margins
            sortSelect.style.width = 'auto';
          }
        }
      }
    }
    
    // Run on load, resize, and orientation change
    window.addEventListener('resize', constrainSelectWidth);
    window.addEventListener('orientationchange', constrainSelectWidth);
    window.addEventListener('load', constrainSelectWidth);
    
    // Run after a short delay to ensure DOM is ready
    setTimeout(constrainSelectWidth, 100);
    setTimeout(constrainSelectWidth, 500);
    
    // Also fix on focus/click (when dropdown opens)
    sortSelect.addEventListener('focus', constrainSelectWidth);
    sortSelect.addEventListener('click', constrainSelectWidth);
    sortSelect.addEventListener('mousedown', constrainSelectWidth);
  }
})();

// Filter Sidebar Toggle Function
function toggleFilterSidebar() {
  const filterSidebar = document.getElementById('filterSidebar');
  const filterToggleBtn = document.getElementById('filterToggleBtn');
  const closeBtn = filterSidebar.querySelector('.filter-close-btn');
  
  if (filterSidebar && filterToggleBtn) {
    filterSidebar.classList.toggle('active');
    filterToggleBtn.classList.toggle('active');
    
    // Show/hide close button on mobile
    if (window.innerWidth <= 1023) {
      if (closeBtn) {
        closeBtn.style.display = filterSidebar.classList.contains('active') ? 'flex' : 'none';
      }
      
      // Prevent body scroll when filter is open
      if (filterSidebar.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    }
  }
}

// Close filter sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
  const filterSidebar = document.getElementById('filterSidebar');
  const filterToggleBtn = document.getElementById('filterToggleBtn');
  
  if (window.innerWidth <= 1023 && filterSidebar && filterToggleBtn) {
    if (filterSidebar.classList.contains('active') && 
        !filterSidebar.contains(e.target) && 
        !filterToggleBtn.contains(e.target)) {
      toggleFilterSidebar();
    }
  }
});

// Update close button visibility on resize
window.addEventListener('resize', function() {
  const filterSidebar = document.getElementById('filterSidebar');
  const closeBtn = filterSidebar ? filterSidebar.querySelector('.filter-close-btn') : null;
  
  if (window.innerWidth > 1023) {
    if (filterSidebar) {
      filterSidebar.classList.remove('active');
      filterSidebar.style.display = '';
    }
    if (closeBtn) {
      closeBtn.style.display = 'none';
    }
    document.body.style.overflow = '';
  } else if (closeBtn && filterSidebar && filterSidebar.classList.contains('active')) {
    closeBtn.style.display = 'flex';
  }
});

// Buy Now function - adds to cart and redirects to checkout
function buyNow(productId) {
  <?php if (!$isLoggedIn): ?>
  // Show login popup if not logged in
  if (typeof window.showLoginPopup === 'function') {
    window.showLoginPopup();
  } else {
    // Fallback: show popup by ID if function not available yet
    const popup = document.getElementById('loginPopup');
    if (popup) {
      popup.classList.add('show');
      document.body.classList.add('blurred');
      showLoginForm();
    } else {
      window.location.href = 'login.php';
    }
  }
  return;
  <?php endif; ?>
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'user/cart_action.php';
  
  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = 'csrf_token';
  csrfInput.value = '<?= esc($csrf) ?>';
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
  
  // Use absolute path for redirect - since cart_action.php is in user/, redirect to checkout.php in same directory
  const redirectInput = document.createElement('input');
  redirectInput.type = 'hidden';
  redirectInput.name = 'redirect';
  redirectInput.value = 'checkout.php';
  form.appendChild(redirectInput);
  
  document.body.appendChild(form);
  form.submit();
}
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
