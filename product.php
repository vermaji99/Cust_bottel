<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
$isLoggedIn = (bool) $currentUser;
$currentPage = 'shop';

// Product fetch logic
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: category.php");
  exit;
}

$id = intval($_GET['id']);
$stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
  echo "<h2 style='color:white;text-align:center;margin-top:100px;'>❌ Product not found.</h2>";
  exit;
}

// Fetch product images from product_images table (different angles)
try {
    $imageStmt = db()->prepare("
        SELECT image_path, image_order, is_primary 
        FROM product_images 
        WHERE product_id = ? 
        ORDER BY is_primary DESC, image_order ASC, id ASC
    ");
    $imageStmt->execute([$id]);
    $productImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If product_images table doesn't exist yet, use fallback
    $productImages = [];
}

// If no images in product_images table, fallback to main image
if (empty($productImages) && !empty($product['image'])) {
    $productImages = [
        ['image_path' => $product['image'], 'is_primary' => 1, 'image_order' => 0]
    ];
}

// If still no images, use placeholder
if (empty($productImages)) {
    $productImages = [
        ['image_path' => 'placeholder.png', 'is_primary' => 1, 'image_order' => 0]
    ];
}

// Get related products (same category, exclude current)
$relatedStmt = db()->prepare("SELECT * FROM products WHERE category = ? AND id != ? AND is_active = 1 ORDER BY RAND() LIMIT 4");
$relatedStmt->execute([$product['category'], $id]);
$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reviews for this product
$reviews = [];
$averageRating = 0;
$totalReviews = 0;
$userReview = null;

try {
    $reviewsStmt = db()->prepare("
        SELECT r.*, u.name as user_name, u.email as user_email
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $reviewsStmt->execute([$id]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's review if logged in
    if ($currentUser) {
        foreach ($reviews as $review) {
            if ($review['user_id'] == $currentUser['id']) {
                $userReview = $review;
                break;
            }
        }
    }
    
    // Calculate average rating from fetched reviews (accurate manual calculation)
    if (count($reviews) > 0) {
        // Extract valid ratings (1-5) from reviews
        $validRatings = [];
        foreach ($reviews as $review) {
            $rating = intval($review['rating'] ?? 0);
            if ($rating >= 1 && $rating <= 5) {
                $validRatings[] = $rating;
            }
        }
        
        if (count($validRatings) > 0) {
            // Calculate exact average: sum / count
            $ratingSum = array_sum($validRatings);
            $ratingCount = count($validRatings);
            $averageRating = $ratingSum / $ratingCount;
            // Round to 1 decimal place (4.666... becomes 4.7)
            $averageRating = round($averageRating, 1);
            // Ensure within valid range
            $averageRating = max(1.0, min(5.0, $averageRating));
            $totalReviews = $ratingCount;
        } else {
            $averageRating = 0;
            $totalReviews = 0;
        }
    } else {
        $averageRating = 0;
        $totalReviews = 0;
    }
} catch (PDOException $e) {
    // If reviews table doesn't exist yet, reviews will be empty
    $reviews = [];
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title><?= htmlspecialchars($product['name']) ?> | Bottle</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
    .material-icons {
      font-size: 24px;
      vertical-align: middle;
    }

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
    .breadcrumb-section a {
      color: #00bcd4;
    }
    .breadcrumb-section a:hover {
      color: #00acc1;
    }

    /* Main Container */
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 64px 4%;
    }

    /* Product Grid */
    .product-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 48px;
      margin-bottom: 64px;
    }

    /* Image Gallery */
    .image-gallery {
      position: relative;
    }
    .main-image-wrapper {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 16px;
      background: #141414;
    }
    .main-image-wrapper img {
      width: 100%;
      height: auto;
      object-fit: cover;
    }
    .gallery-nav-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: #00bcd4;
      color: white;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: 0.3s;
      z-index: 2;
    }
    .gallery-nav-btn:hover {
      background: #00acc1;
    }
    .gallery-nav-btn.prev {
      left: 16px;
    }
    .gallery-nav-btn.next {
      right: 16px;
    }
    .thumbnail-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
    }
    .thumbnail {
      border-radius: 12px;
      border: 2px solid #333;
      cursor: pointer;
      overflow: hidden;
      transition: 0.3s;
      background: #141414;
    }
    .thumbnail.active {
      border-color: #00bcd4;
    }
    .thumbnail img {
      width: 100%;
      height: 100px;
      object-fit: cover;
    }

    /* Product Details */
    .product-details {
      padding-left: 20px;
    }
    .product-category {
      font-size: 0.875rem;
      color: #999;
      margin-bottom: 4px;
    }
    .product-title {
      font-size: 1.875rem;
      font-weight: 700;
      color: #fff;
      margin: 4px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .stock-badge {
      font-size: 0.875rem;
      font-weight: 500;
      color: #4caf50;
      background: rgba(76, 175, 80, 0.2);
      padding: 4px 12px;
      border-radius: 9999px;
    }
    .rating-section {
      display: flex;
      align-items: center;
      margin-top: 8px;
      gap: 8px;
    }
    .rating-stars {
      display: flex;
      color: #ffc107;
    }
    .rating-text {
      margin-left: 8px;
      color: #999;
      font-size: 0.875rem;
    }
    .price-section {
      margin-top: 16px;
    }
    .price-current {
      font-size: 1.875rem;
      font-weight: 700;
      color: #ffc107;
    }
    .price-original {
      margin-left: 8px;
      font-size: 1.25rem;
      color: #666;
      text-decoration: line-through;
    }
    .product-description {
      margin-top: 16px;
      color: #ccc;
      line-height: 1.6;
    }

    /* Size Options */
    .size-section {
      margin-top: 24px;
    }
    .size-label {
      font-size: 0.875rem;
      font-weight: 500;
      color: #fff;
      margin-bottom: 8px;
    }
    .size-buttons {
      display: flex;
      gap: 8px;
      margin-top: 8px;
    }
    .size-btn {
      padding: 8px 16px;
      border-radius: 12px;
      border: none;
      font-size: 0.875rem;
      cursor: pointer;
      transition: 0.3s;
      background: #1a1a1a;
      color: #ccc;
      border: 1px solid #333;
    }
    .size-btn.active,
    .size-btn:hover {
      background: #00bcd4;
      color: white;
      border-color: #00bcd4;
    }

    /* Quantity Selector */
    .quantity-section {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-top: 24px;
    }
    .quantity-controls {
      display: flex;
      align-items: center;
      border: 1px solid #333;
      border-radius: 12px;
      background: #1a1a1a;
    }
    .qty-btn {
      padding: 8px 12px;
      background: transparent;
      border: none;
      color: #999;
      cursor: pointer;
      font-size: 1.2rem;
      transition: 0.2s;
    }
    .qty-btn:hover {
      color: #00bcd4;
    }
    .qty-value {
      padding: 8px 16px;
      color: #fff;
      min-width: 60px;
      text-align: center;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-top: 24px;
    }
    .btn-add-cart {
      flex: 1;
      background: #00bcd4;
      color: white;
      padding: 12px 24px;
      border-radius: 12px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
      font-size: 1rem;
    }
    .btn-add-cart:hover {
      background: #00acc1;
    }
    .btn-buy-now {
      flex: 1;
      background: #ffc107;
      color: white;
      padding: 12px 24px;
      border-radius: 12px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
      font-size: 1rem;
    }
    .btn-buy-now:hover {
      background: #ffb300;
    }
    .btn-wishlist {
      padding: 12px;
      border: 1px solid #333;
      border-radius: 12px;
      background: #1a1a1a;
      color: #999;
      cursor: pointer;
      transition: 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .btn-wishlist:hover {
      border-color: #00bcd4;
      color: #00bcd4;
    }

    /* Product Meta */
    .product-meta {
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1px solid #333;
      font-size: 0.875rem;
      color: #999;
    }
    .meta-item {
      margin-bottom: 8px;
    }
    .meta-label {
      font-weight: 600;
      color: #fff;
      margin-right: 8px;
    }
    .share-buttons {
      display: flex;
      gap: 8px;
      margin-top: 8px;
    }
    .share-btn {
      width: 24px;
      height: 24px;
      background: #1a1a1a;
      border-radius: 50%;
      border: 1px solid #333;
    }

    /* Tabs */
    .tabs-section {
      margin-top: 64px;
    }
    .tabs-nav {
      display: flex;
      justify-content: center;
      gap: 32px;
      border-bottom: 1px solid #333;
      margin-bottom: -1px;
    }
    .tab-btn {
      padding: 16px 4px;
      border-bottom: 2px solid transparent;
      color: #999;
      cursor: pointer;
      transition: 0.3s;
      font-weight: 500;
      background: none;
      border: none;
      font-size: 1rem;
    }
    .tab-btn:hover,
    .tab-btn.active {
      color: #00bcd4;
      border-bottom-color: #00bcd4;
    }
    .tab-content {
      margin-top: 32px;
    }
    .tab-pane {
      display: none;
    }
    .tab-pane.active {
      display: block;
    }

    /* Additional Info Table */
    .info-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
      overflow-x: auto;
    }
    .info-table thead {
      background: #00bcd4;
      color: white;
    }
    .info-table th {
      font-weight: 600;
      padding: 16px;
      text-align: left;
    }
    .info-table th:first-child {
      border-radius: 12px 0 0 0;
    }
    .info-table th:last-child {
      border-radius: 0 12px 0 0;
    }
    .info-table td {
      padding: 16px;
      color: #ccc;
    }
    .info-table tr:nth-child(even) {
      background: #1a1a1a;
    }
    .info-table tr:nth-child(odd) {
      background: #141414;
    }
    .info-table td:first-child {
      font-weight: 500;
      color: #fff;
    }

    /* Reviews Section */
    .reviews-section {
      max-width: 900px;
    }
    
    .review-summary {
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 48px;
      padding: 32px;
      background: #141414;
      border-radius: 12px;
      margin-bottom: 32px;
    }
    
    .review-average {
      text-align: center;
    }
    
    .review-average-number {
      font-size: 3rem;
      font-weight: 700;
      color: #fff;
      display: block;
      margin-bottom: 8px;
    }
    
    .review-stars-large {
      display: flex;
      justify-content: center;
      gap: 4px;
      margin-bottom: 12px;
    }
    
    .review-stars-large .star-filled,
    .review-stars-large .star-empty,
    .review-stars-large .star-half {
      font-size: 1.5rem;
      color: #ffc107;
    }
    
    .review-stars-large .star-empty {
      color: #444;
    }
    
    .review-count-text {
      color: #999;
      font-size: 0.875rem;
      margin: 0;
    }
    
    .review-summary-right {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .rating-bar-row {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .rating-label {
      font-size: 0.875rem;
      color: #ccc;
      min-width: 60px;
    }
    
    .rating-bar {
      flex: 1;
      height: 8px;
      background: #333;
      border-radius: 4px;
      overflow: hidden;
    }
    
    .rating-bar-fill {
      height: 100%;
      background: #ffc107;
      transition: width 0.3s;
    }
    
    .rating-count {
      font-size: 0.875rem;
      color: #999;
      min-width: 30px;
      text-align: right;
    }
    
    /* Review Form */
    .review-form-wrapper {
      background: #141414;
      border-radius: 12px;
      padding: 32px;
      margin-bottom: 32px;
    }
    
    .review-form-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #fff;
      margin-bottom: 24px;
    }
    
    .review-form-group {
      margin-bottom: 24px;
    }
    
    .review-form-group label {
      display: block;
      font-weight: 500;
      color: #ccc;
      margin-bottom: 8px;
      font-size: 0.875rem;
    }
    
    .star-rating-input {
      display: flex;
      gap: 8px;
      cursor: pointer;
    }
    
    .star-input {
      font-size: 2rem;
      color: #444;
      transition: color 0.2s;
      cursor: pointer;
    }
    
    .star-input:hover,
    .star-input.active {
      color: #ffc107;
    }
    
    .review-textarea {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      background: #1a1a1a;
      border: 1px solid #333;
      color: #fff;
      font-family: inherit;
      font-size: 0.875rem;
      resize: vertical;
    }
    
    .review-textarea:focus {
      outline: none;
      border-color: #00bcd4;
    }
    
    .review-submit-btn {
      background: #00bcd4;
      color: #fff;
      border: none;
      padding: 12px 32px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
      font-size: 0.875rem;
    }
    
    .review-submit-btn:hover {
      background: #00acc1;
      transform: translateY(-2px);
    }
    
    .review-login-prompt {
      background: #141414;
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      margin-bottom: 32px;
    }
    
    .review-login-prompt a {
      color: #00bcd4;
      text-decoration: underline;
    }
    
    /* Reviews List */
    .reviews-list-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #fff;
      margin-bottom: 24px;
    }
    
    .review-item {
      background: #141414;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
    }
    
    .review-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 16px;
    }
    
    .review-user {
      display: flex;
      gap: 12px;
      align-items: center;
    }
    
    .review-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: #00bcd4;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 1.25rem;
    }
    
    .review-user-name {
      font-weight: 600;
      color: #fff;
      margin: 0 0 4px;
      font-size: 0.875rem;
    }
    
    .review-date {
      color: #999;
      font-size: 0.75rem;
      margin: 0;
    }
    
    .review-rating-display {
      display: flex;
      gap: 2px;
    }
    
    .review-rating-display .star {
      font-size: 1rem;
    }
    
    .review-rating-display .star-filled {
      color: #ffc107;
    }
    
    .review-rating-display .star-empty {
      color: #444;
    }
    
    .review-comment {
      color: #ccc;
      line-height: 1.6;
      font-size: 0.875rem;
      margin-top: 12px;
    }
    
    .admin-reply {
      background: #1a1a1a;
      border-left: 3px solid #00bcd4;
      border-radius: 8px;
      padding: 16px;
      margin-top: 16px;
    }
    
    .admin-reply-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 8px;
    }
    
    .admin-badge {
      background: #00bcd4;
      color: #fff;
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .admin-reply-date {
      color: #999;
      font-size: 0.75rem;
    }
    
    .admin-reply-text {
      color: #ccc;
      font-size: 0.875rem;
      line-height: 1.6;
      margin: 0;
    }
    
    .admin-reply-form-wrapper {
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid #333;
    }
    
    .admin-reply-form {
      display: flex;
      gap: 12px;
      align-items: flex-start;
    }
    
    .admin-reply-textarea {
      flex: 1;
      padding: 8px 12px;
      border-radius: 6px;
      background: #1a1a1a;
      border: 1px solid #333;
      color: #fff;
      font-family: inherit;
      font-size: 0.875rem;
      resize: vertical;
    }
    
    .admin-reply-textarea:focus {
      outline: none;
      border-color: #00bcd4;
    }
    
    .admin-reply-btn {
      background: #00bcd4;
      color: #fff;
      border: none;
      padding: 8px 20px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
      font-size: 0.875rem;
      white-space: nowrap;
    }
    
    .admin-reply-btn:hover {
      background: #00acc1;
    }
    
    .no-reviews {
      text-align: center;
      color: #999;
      padding: 48px;
      font-size: 0.875rem;
    }
    
    @media (max-width: 768px) {
      .review-summary {
        grid-template-columns: 1fr;
        gap: 24px;
      }
      
      .admin-reply-form {
        flex-direction: column;
      }
      
      .admin-reply-btn {
        width: 100%;
      }
    }

    /* Related Products */
    .related-section {
      margin-top: 80px;
      text-align: center;
    }
    .related-label {
      color: #999;
      font-size: 0.875rem;
    }
    .related-title {
      font-size: 2.25rem;
      font-weight: 700;
      color: #fff;
      margin-top: 8px;
    }
    .related-title span {
      color: #ffc107;
    }
    .related-grid {
      margin-top: 48px;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 32px;
    }

    /* Related Product Card */
    .related-card {
      background: #141414;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
      text-align: left;
      position: relative;
      transition: 0.3s;
    }
    .related-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 12px rgba(0, 188, 212, 0.2);
    }
    .related-card-image {
      position: relative;
      width: 100%;
      height: 256px;
      overflow: hidden;
    }
    .related-card-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .related-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      background: #00bcd4;
      color: white;
      font-size: 0.75rem;
      padding: 4px 8px;
      border-radius: 9999px;
    }
    .related-actions {
      position: absolute;
      top: 12px;
      right: 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .related-card:hover .related-actions {
      opacity: 1;
    }
    .related-action-btn {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.95);
      border: none;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .related-action-btn .material-icons {
      font-size: 18px;
      color: #333;
    }
    .related-card-info {
      padding: 16px;
    }
    .related-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.875rem;
      margin-bottom: 4px;
    }
    .related-category {
      color: #999;
    }
    .related-rating {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .related-rating .material-icons {
      font-size: 18px;
      color: #ffc107;
    }
    .related-rating-value {
      font-weight: 600;
      color: #ccc;
      margin-left: 4px;
    }
    .related-card-name {
      font-weight: 600;
      font-size: 1.125rem;
      color: #fff;
      margin: 4px 0 8px;
    }
    .related-price {
      margin-top: 8px;
    }
    .related-price-current {
      font-weight: 700;
      color: #00bcd4;
      font-size: 1.125rem;
    }
    .related-price-original {
      margin-left: 8px;
      color: #666;
      text-decoration: line-through;
      font-size: 0.875rem;
    }

    /* Features Section */
    .features-section {
      padding: 64px 4%;
      background: #1a1a1a;
    }
    .features-container {
      max-width: 1400px;
      margin: 0 auto;
    }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 32px;
    }
    .feature-item {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .feature-icon {
      background: rgba(255, 193, 7, 0.1);
      padding: 12px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .feature-icon .material-icons {
      color: #ffc107;
      font-size: 2rem;
    }
    .feature-content h4 {
      font-weight: 700;
      color: #fff;
      margin-bottom: 4px;
    }
    .feature-content p {
      font-size: 0.875rem;
      color: #999;
      margin: 0;
    }

    @media (max-width: 1024px) {
      .product-grid {
        grid-template-columns: 1fr;
        gap: 32px;
      }
      .related-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 24px;
      }
    }
    @media (max-width: 768px) {
      .thumbnail-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
      }
      .action-buttons {
        flex-direction: column;
      }
      .btn-add-cart,
      .btn-buy-now {
        width: 100%;
      }
    }
  </style>
</head>

<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Breadcrumb Section -->
<div class="breadcrumb-section">
  <!-- <h1>Shop</h1>
  <p>
    <a href="index.php">Home</a> / 
    <a href="category.php">Shop</a> / 
    <span>Product Details</span>
  </p> -->
</div>

<!-- Main Container -->
<main class="container">
  <div class="product-grid">
    <!-- Image Gallery - Multiple Angle Views -->
    <div class="image-gallery">
      <div class="main-image-wrapper">
        <img id="mainImage" src="admin/uploads/<?= htmlspecialchars($productImages[0]['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php if (count($productImages) > 1): ?>
          <button class="gallery-nav-btn prev" onclick="changeImage(-1)">
            <span class="material-icons">chevron_left</span>
          </button>
          <button class="gallery-nav-btn next" onclick="changeImage(1)">
            <span class="material-icons">chevron_right</span>
          </button>
        <?php endif; ?>
      </div>
      <div class="thumbnail-grid">
        <?php foreach ($productImages as $idx => $img): ?>
          <div class="thumbnail <?= $idx === 0 ? 'active' : '' ?>" onclick="setMainImage(<?= $idx ?>)">
            <img src="admin/uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="View <?= $idx + 1 ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Product Details -->
    <div class="product-details">
      <p class="product-category"><?= htmlspecialchars($product['category'] ?: 'Bottles') ?></p>
      <h2 class="product-title">
        <?= htmlspecialchars($product['name']) ?>
        <span class="stock-badge">In Stock</span>
      </h2>

      <div class="rating-section">
        <div class="rating-stars">
          <?php
          // Display stars based on actual average rating
          if ($totalReviews > 0 && $averageRating > 0) {
            // Calculate filled stars and half star
            $avgInt = floor($averageRating);
            $hasHalf = ($averageRating - $avgInt) >= 0.5;
            
            for ($i = 1; $i <= 5; $i++):
              if ($i <= $avgInt): ?>
                <span class="material-icons" style="font-size: 18px; color: #ffc107;">star</span>
              <?php elseif ($i == $avgInt + 1 && $hasHalf): ?>
                <span class="material-icons" style="font-size: 18px; color: #ffc107;">star_half</span>
              <?php else: ?>
                <span class="material-icons" style="font-size: 18px; color: #666;">star_border</span>
              <?php endif;
            endfor;
          } else {
            // No reviews - show all empty stars
            for ($i = 1; $i <= 5; $i++): ?>
              <span class="material-icons" style="font-size: 18px; color: #666;">star_border</span>
            <?php endfor;
          }
          ?>
        </div>
        <span class="rating-text">
          <?php if ($totalReviews > 0): ?>
            <?= number_format($averageRating, 1) ?> (<?= $totalReviews ?> Review<?= $totalReviews != 1 ? 's' : '' ?>)
          <?php else: ?>
            No reviews yet
          <?php endif; ?>
        </span>
      </div>

      <div class="price-section">
        <span class="price-current">₹<?= number_format($product['price'], 2) ?></span>
        <?php if ($product['price'] > 100): ?>
          <span class="price-original">₹<?= number_format($product['price'] * 1.25, 2) ?></span>
        <?php endif; ?>
      </div>

      <p class="product-description">
        <?= nl2br(htmlspecialchars($product['description'] ?: 'Premium quality custom bottle with excellent design and durability. Perfect for personal use, events, or as a gift.')) ?>
      </p>

      <!-- Size Options -->
      <div class="size-section">
        <p class="size-label">Size/Volume</p>
        <div class="size-buttons">
          <button class="size-btn active" data-size="30ml">30 ml</button>
          <button class="size-btn" data-size="60ml">60ml</button>
          <button class="size-btn" data-size="80ml">80ml</button>
          <button class="size-btn" data-size="100ml">100ml</button>
        </div>
      </div>

      <!-- Quantity Selector -->
      <div class="quantity-section">
        <div class="quantity-controls">
          <button class="qty-btn" onclick="changeQty(-1)">-</button>
          <span class="qty-value" id="quantity">1</span>
          <button class="qty-btn" onclick="changeQty(1)">+</button>
        </div>
        <form method="POST" action="user/cart_action.php" style="flex: 1;">
          <input type="hidden" name="csrf_token" value="<?= esc($csrf); ?>">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
          <input type="hidden" name="quantity" id="cartQuantity" value="1">
          <input type="hidden" name="redirect" value="<?= esc($_SERVER['REQUEST_URI']); ?>">
          <button type="submit" class="btn-add-cart">Add To Cart</button>
        </form>
        <button type="button" class="btn-buy-now" onclick="buyNow(<?= $product['id']; ?>)">Buy Now</button>
        <a href="#" class="btn-wishlist" data-wishlist-add="<?= $product['id']; ?>">
          <span class="material-icons">favorite_border</span>
        </a>
      </div>

      <!-- Product Meta -->
      <div class="product-meta">
        <p class="meta-item">
          <span class="meta-label">SKU :</span> <?= strtoupper(substr(md5($product['id']), 0, 12)) ?>
        </p>
        <p class="meta-item">
          <span class="meta-label">Tags :</span> <?= htmlspecialchars($product['category'] ?: 'Bottles') ?>, Custom, Premium
        </p>
        <div class="meta-item">
          <span class="meta-label">Share :</span>
          <div class="share-buttons">
            <a href="#" class="share-btn"></a>
            <a href="#" class="share-btn"></a>
            <a href="#" class="share-btn"></a>
            <a href="#" class="share-btn"></a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs Section -->
  <div class="tabs-section">
    <div class="tabs-nav">
      <button class="tab-btn" onclick="showTab('description')">Description</button>
      <button class="tab-btn active" onclick="showTab('additional')">Additional Information</button>
      <button class="tab-btn" onclick="showTab('review')">Review</button>
    </div>

    <div class="tab-content">
      <div id="description" class="tab-pane">
        <p class="product-description">
          <?= nl2br(htmlspecialchars($product['description'] ?: 'This premium custom bottle is designed with quality and style in mind. Perfect for personal use, events, or as a gift.')) ?>
        </p>
      </div>

      <div id="additional" class="tab-pane active">
        <div style="overflow-x: auto;">
          <table class="info-table">
            <thead>
              <tr>
                <th>Attribute</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Material</td>
                <td>Premium Plastic / Stainless Steel</td>
              </tr>
              <tr>
                <td>Size/Volume</td>
                <td>30 ml, 60 ml, 80 ml, 100 ml</td>
              </tr>
              <tr>
                <td>Shelf Life</td>
                <td>24 months</td>
              </tr>
              <tr>
                <td>Customization</td>
                <td>2D & 3D Design Available</td>
              </tr>
              <tr>
                <td>Packaging</td>
                <td>Recyclable Material</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div id="review" class="tab-pane">
        <div class="reviews-section">
          <!-- Review Summary -->
          <div class="review-summary">
            <div class="review-summary-left">
              <div class="review-average">
                <span class="review-average-number"><?= $totalReviews > 0 ? number_format($averageRating, 1) : '0.0' ?></span>
                <div class="review-stars-large">
                  <?php
                  $avgInt = floor($averageRating);
                  $hasHalf = ($averageRating - $avgInt) >= 0.5;
                  for ($i = 1; $i <= 5; $i++): 
                    if ($i <= $avgInt): ?>
                      <span class="star-filled">★</span>
                    <?php elseif ($i == $avgInt + 1 && $hasHalf): ?>
                      <span class="star-half">★</span>
                    <?php else: ?>
                      <span class="star-empty">★</span>
                    <?php endif;
                  endfor; ?>
                </div>
                <p class="review-count-text">Based on <?= $totalReviews ?> review<?= $totalReviews != 1 ? 's' : '' ?></p>
              </div>
            </div>
            <div class="review-summary-right">
              <?php
              // Rating distribution
              $ratingDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
              foreach ($reviews as $r) {
                // Safely get rating with null coalescing and convert to integer
                $ratingValue = $r['rating'] ?? 0;
                // Convert rating to integer (handle float/string values like "4.0")
                $rating = intval($ratingValue);
                // Ensure rating is between 1 and 5
                if ($rating >= 1 && $rating <= 5 && isset($ratingDist[$rating])) {
                  $ratingDist[$rating]++;
                }
              }
              for ($star = 5; $star >= 1; $star--):
                $count = isset($ratingDist[$star]) ? $ratingDist[$star] : 0;
                $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
              ?>
                <div class="rating-bar-row">
                  <span class="rating-label"><?= $star ?> Star</span>
                  <div class="rating-bar">
                    <div class="rating-bar-fill" style="width: <?= $percentage ?>%"></div>
                  </div>
                  <span class="rating-count"><?= $count ?></span>
                </div>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Review Form (for authenticated users) -->
          <?php if ($currentUser): ?>
            <div class="review-form-wrapper">
              <h3 class="review-form-title">Write a Review</h3>
              <form id="reviewForm" class="review-form">
                <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                
                <div class="review-form-group">
                  <label>Your Rating *</label>
                  <div class="star-rating-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <span class="star-input" data-rating="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</span>
                    <?php endfor; ?>
                    <input type="hidden" name="rating" id="ratingInput" value="<?= $userReview ? intval($userReview['rating']) : '0' ?>" required>
                  </div>
                </div>

                <div class="review-form-group">
                  <label for="reviewComment">Your Comment (Optional)</label>
                  <textarea 
                    name="comment" 
                    id="reviewComment" 
                    class="review-textarea" 
                    placeholder="Share your experience with this product..."
                    rows="4"
                  ><?= $userReview ? htmlspecialchars($userReview['comment']) : '' ?></textarea>
                </div>

                <button type="submit" class="review-submit-btn">
                  <?= $userReview ? 'Update Review' : 'Submit Review' ?>
                </button>
              </form>
            </div>
          <?php else: ?>
            <div class="review-login-prompt">
              <p>Please <a href="login.php?redirect=product.php?id=<?= $id ?>">login</a> to write a review.</p>
            </div>
          <?php endif; ?>

          <!-- Reviews List -->
          <div class="reviews-list">
            <h3 class="reviews-list-title">Customer Reviews</h3>
            <div id="reviewsContainer">
              <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                  <div class="review-item" data-review-id="<?= $review['id'] ?>">
                    <div class="review-header">
                      <div class="review-user">
                        <div class="review-avatar">
                          <?= strtoupper(substr($review['user_name'], 0, 1)) ?>
                        </div>
                        <div class="review-user-info">
                          <p class="review-user-name"><?= htmlspecialchars($review['user_name']) ?></p>
                          <p class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></p>
                        </div>
                      </div>
                      <div class="review-rating-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <span class="star <?= $i <= $review['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                        <?php endfor; ?>
                      </div>
                    </div>
                    
                    <?php if (!empty($review['comment'])): ?>
                      <div class="review-comment">
                        <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($review['admin_reply'])): ?>
                      <div class="admin-reply">
                        <div class="admin-reply-header">
                          <span class="admin-badge">Admin</span>
                          <span class="admin-reply-date"><?= date('M d, Y', strtotime($review['admin_replied_at'])) ?></span>
                        </div>
                        <p class="admin-reply-text"><?= nl2br(htmlspecialchars($review['admin_reply'])) ?></p>
                      </div>
                    <?php endif; ?>

                    <?php if ($currentUser && $currentUser['role'] === 'admin' && empty($review['admin_reply'])): ?>
                      <div class="admin-reply-form-wrapper">
                        <form class="admin-reply-form" onsubmit="submitAdminReply(event, <?= $review['id'] ?>)">
                          <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
                          <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                          <textarea 
                            name="admin_reply" 
                            class="admin-reply-textarea" 
                            placeholder="Write a reply..."
                            rows="2"
                            required
                          ></textarea>
                          <button type="submit" class="admin-reply-btn">Reply</button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Related Products -->
  <?php if (count($relatedProducts) > 0): ?>
  <div class="related-section">
    <p class="related-label">Related Products</p>
    <h2 class="related-title">Explore <span>Related Products</span></h2>
    <div class="related-grid">
      <?php foreach ($relatedProducts as $related): 
        // Fetch first image from product_images table for related products
        $relatedFirstImage = $related['image']; // Fallback to main image
        try {
          $relatedImgStmt = db()->prepare("
            SELECT image_path FROM product_images 
            WHERE product_id = ? 
            ORDER BY is_primary DESC, image_order ASC, id ASC 
            LIMIT 1
          ");
          $relatedImgStmt->execute([$related['id']]);
          $relatedImgResult = $relatedImgStmt->fetchColumn();
          if ($relatedImgResult) {
            $relatedFirstImage = $relatedImgResult;
          }
        } catch (Exception $e) {
          // If product_images table doesn't exist, use fallback
        }
        
        $discount = rand(20, 50);
        $originalPrice = $related['price'] * (100 / (100 - $discount));
        // Get average rating from reviews table
        try {
          $ratingStmt = db()->prepare("
            SELECT AVG(rating) as avg_rating 
            FROM reviews 
            WHERE product_id = ?
          ");
          $ratingStmt->execute([$related['id']]);
          $ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
          
          if ($ratingData && $ratingData['avg_rating'] !== null) {
            $rating = round(floatval($ratingData['avg_rating']), 1);
            $rating = max(1.0, min(5.0, $rating));
          } else {
            // Fallback: Generate consistent rating based on product ID
            $seed = ($related['id'] * 7) % 41;
            $rating = 1.0 + ($seed / 10);
            $rating = max(1.0, min(5.0, round($rating, 1)));
          }
        } catch (PDOException $e) {
          // If reviews table doesn't exist, use fallback
          $seed = ($related['id'] * 7) % 41;
          $rating = 1.0 + ($seed / 10);
          $rating = max(1.0, min(5.0, round($rating, 1)));
        }
        $rating = number_format($rating, 1);
      ?>
      <div class="related-card">
        <div class="related-card-image">
          <img src="admin/uploads/<?= htmlspecialchars($relatedFirstImage) ?>" alt="<?= htmlspecialchars($related['name']) ?>">
          <div class="related-badge"><?= $discount ?>% off</div>
          <div class="related-actions">
            <button class="related-action-btn" data-wishlist-add="<?= $related['id'] ?>" title="Add to wishlist">
              <span class="material-icons">favorite_border</span>
            </button>
            <button class="related-action-btn" title="Compare">
              <span class="material-icons">sync_alt</span>
            </button>
            <button class="related-action-btn" title="Add to cart" onclick="window.location.href='product.php?id=<?= $related['id'] ?>'">
              <span class="material-icons">shopping_bag</span>
            </button>
          </div>
        </div>
        <div class="related-card-info">
          <div class="related-card-header">
            <p class="related-category"><?= htmlspecialchars($related['category'] ?: 'Bottles') ?></p>
            <div class="related-rating">
              <span class="material-icons">star</span>
              <span class="related-rating-value"><?= $rating ?></span>
            </div>
          </div>
          <h3 class="related-card-name"><?= htmlspecialchars($related['name']) ?></h3>
          <div class="related-price">
            <span class="related-price-current">₹<?= number_format($related['price'], 2) ?></span>
            <span class="related-price-original">₹<?= number_format($originalPrice, 2) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</main>

<!-- Features Section -->
<section class="features-section">
  <div class="features-container">
    <div class="features-grid">
      <div class="feature-item">
        <div class="feature-icon">
          <span class="material-icons">local_shipping</span>
        </div>
        <div class="feature-content">
          <h4>Free Shipping</h4>
          <p>Free shipping for order above ₹500</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">
          <span class="material-icons">payment</span>
        </div>
        <div class="feature-content">
          <h4>Flexible Payment</h4>
          <p>Multiple secure payment options</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">
          <span class="material-icons">support_agent</span>
        </div>
        <div class="feature-content">
          <h4>24x7 Support</h4>
          <p>We support online all days.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
let currentImageIndex = 0;
const productImages = [
  <?php foreach ($productImages as $idx => $img): ?>
    'admin/uploads/<?= htmlspecialchars($img['image_path']) ?>'<?= $idx < count($productImages) - 1 ? ',' : '' ?>
  <?php endforeach; ?>
];

function setMainImage(index) {
  if (index >= 0 && index < productImages.length) {
    currentImageIndex = index;
    document.getElementById('mainImage').src = productImages[index];
    document.querySelectorAll('.thumbnail').forEach((t, i) => {
      t.classList.toggle('active', i === index);
    });
  }
}

function changeImage(direction) {
  currentImageIndex += direction;
  if (currentImageIndex < 0) currentImageIndex = productImages.length - 1;
  if (currentImageIndex >= productImages.length) currentImageIndex = 0;
  setMainImage(currentImageIndex);
}

function changeQty(change) {
  const qtySpan = document.getElementById('quantity');
  const cartQtyInput = document.getElementById('cartQuantity');
  let qty = parseInt(qtySpan.textContent) + change;
  if (qty < 1) qty = 1;
  qtySpan.textContent = qty;
  cartQtyInput.value = qty;
}

function showTab(tabName) {
  document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  document.getElementById(tabName).classList.add('active');
  event.target.classList.add('active');
}

// Size button selection
document.querySelectorAll('.size-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
  });
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
  quantityInput.value = document.getElementById('quantity') ? document.getElementById('quantity').textContent : '1';
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

// === Review Functions ===

// Set rating stars
function setRating(rating) {
  document.getElementById('ratingInput').value = rating;
  document.querySelectorAll('.star-input').forEach((star, index) => {
    if (index < rating) {
      star.classList.add('active');
    } else {
      star.classList.remove('active');
    }
  });
}

// Initialize rating if user already has a review
<?php if ($userReview): ?>
document.addEventListener('DOMContentLoaded', function() {
  setRating(<?= $userReview['rating'] ?>);
});
<?php endif; ?>

// Submit review form
document.getElementById('reviewForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  // Validate rating before submission
  const ratingInput = document.getElementById('ratingInput');
  const rating = parseInt(ratingInput.value) || 0;
  
  if (rating < 1 || rating > 5) {
    alert('Please select a rating (1-5 stars) before submitting your review.');
    return;
  }
  
  const formData = new FormData(this);
  const submitBtn = this.querySelector('.review-submit-btn');
  const originalText = submitBtn.textContent;
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Submitting...';
  
  try {
    const response = await fetch('api/submit_review.php', {
      method: 'POST',
      body: formData
    });
    
    // Check if response is OK
    if (!response.ok) {
      // Try to get error message from response
      let errorData;
      try {
        errorData = await response.json();
      } catch (jsonError) {
        errorData = { message: 'Server returned an error (Status: ' + response.status + ')' };
      }
      throw new Error(errorData.message || 'Network response was not ok: ' + response.status);
    }
    
    const data = await response.json();
    
    if (data.success) {
      // Show success message briefly before reload
      submitBtn.textContent = 'Success!';
      submitBtn.style.background = '#4caf50';
      
      // Refresh page to show updated reviews and rating
      setTimeout(() => {
        window.location.reload();
      }, 500);
    } else {
      console.error('API Error:', data);
      let errorMsg = data.message || 'Failed to submit review. Please try again.';
      
      // Show detailed error if available
      if (data.error_details) {
        console.error('Error Details:', data.error_details);
        errorMsg += '\n\nCheck browser console for details.';
      }
      
      alert(errorMsg);
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  } catch (error) {
    console.error('Error:', error);
    
    // Check if error is JSON parse error
    if (error.message.includes('JSON')) {
      alert('Server returned invalid response. Please check your connection and try again.');
    } else {
      alert('Error: ' + error.message + '\n\nPlease check the browser console for details.');
    }
    
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
  }
});

// Submit admin reply
async function submitAdminReply(e, reviewId) {
  e.preventDefault();
  
  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('.admin-reply-btn');
  const originalText = submitBtn.textContent;
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Replying...';
  
  try {
    const response = await fetch('api/admin_reply_review.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Refresh page to show admin reply
      window.location.reload();
    } else {
      alert(data.message || 'Failed to submit reply. Please try again.');
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
  }
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
            registerErrorDiv.textContent = 'Network error. Please try again';
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
