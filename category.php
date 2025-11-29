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
    }
    a { text-decoration: none; color: inherit; }
    img { max-width: 100%; display: block; }

    /* Breadcrumb Section */
    .breadcrumb-section {
      background: #1a1a1a;
      padding: 48px 8%;
      text-align: center;
    }
    .breadcrumb-section h1 {
      font-size: 2.25rem;
      font-weight: 700;
      color: #fff;
      margin: 0 0 8px;
    }
    .breadcrumb-section p {
      color: #999;
      margin: 0;
    }

    /* Main Container */
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 48px 4%;
      padding-top: 100px;
    }

    /* Grid Layout */
    .shop-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 32px;
    }

    /* Filter Sidebar */
    .filter-sidebar h2 {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 24px;
      color: #fff;
    }
    .filter-group {
      margin-bottom: 32px;
    }
    .filter-group h3 {
      font-weight: 600;
      margin-bottom: 16px;
      color: #ccc;
      font-size: 1rem;
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
    .product-section-header {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-bottom: 24px;
    }
    .product-section-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    .results-count {
      font-size: 0.875rem;
      color: #999;
    }
    .sort-wrapper {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .sort-wrapper span {
      font-size: 0.875rem;
      color: #999;
    }
    .sort-select {
      padding: 6px 12px;
      border-radius: 6px;
      background: #1a1a1a;
      border: 1px solid #333;
      color: #fff;
      font-size: 0.875rem;
      cursor: pointer;
    }
    .sort-select:focus {
      outline: none;
      border-color: #00bcd4;
    }

    /* Active Filters */
    .active-filters {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-bottom: 24px;
    }
    .active-filters-label {
      font-size: 0.875rem;
      font-weight: 500;
      color: #ccc;
    }
    .filter-tag {
      display: flex;
      align-items: center;
      background: #00bcd4;
      color: white;
      font-size: 0.75rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 9999px;
      gap: 8px;
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
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
    }

    /* Product Card */
    .product-card {
      background: #141414;
      border-radius: 8px;
      overflow: hidden;
      transition: 0.3s;
      position: relative;
      cursor: pointer;
    }
    .product-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0, 188, 212, 0.2);
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
      font-weight: 500;
      color: #ccc;
      margin-left: 4px;
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-top: 48px;
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
      padding: 48px 8%;
      margin-top: 48px;
    }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 32px;
      text-align: center;
    }
    .feature-item {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .feature-icon {
      width: 60px;
      height: 60px;
      background: rgba(0, 188, 212, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
    }
    .feature-icon .material-symbols-outlined {
      font-size: 2rem;
      color: #00bcd4;
    }
    .feature-title {
      font-weight: 600;
      font-size: 1.125rem;
      color: #fff;
      margin-bottom: 8px;
    }
    .feature-desc {
      font-size: 0.875rem;
      color: #999;
    }

    @media (max-width: 1024px) {
      .shop-grid {
        grid-template-columns: 1fr;
      }
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      }
    }
    @media (max-width: 768px) {
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
      }
      .product-section-top {
        flex-direction: column;
        align-items: flex-start;
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

<!-- Main Container -->
<div class="container">
  <div class="shop-grid">
    <!-- Filter Sidebar -->
    <aside class="filter-sidebar">
      <h2>Filter Options</h2>

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
            <select class="sort-select" id="sortSelect" onchange="updateSort()">
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
