<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>Bottel | Shop Categories</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      background: #0c0c0c;
      color: #f0f0f0;
    }
    a { text-decoration: none; color: inherit; }
    img { max-width: 100%; display: block; }
    .btn {
      background: linear-gradient(45deg, #00bcd4, #007bff);
      padding: 10px 25px; border-radius: 25px; color: #fff;
      display: inline-block; transition: 0.3s;
    }
    .btn:hover { transform: scale(1.05); }

    header {
      position: fixed; top: 0; left: 0; width: 100%;
      background: rgba(0,0,0,0.9);
      display: flex; justify-content: space-between; align-items: center;
      padding: 15px 8%;
      z-index: 1000;
      backdrop-filter: blur(10px);
    }
    .logo { font-size: 1.5rem; font-weight: 700; color: #00bcd4; }
    nav ul { list-style: none; display: flex; gap: 25px; }
    nav a:hover { color: #00bcd4; }
    .icons { display: flex; gap: 20px; font-size: 1.2rem; }

    main {
      display: flex;
      padding: 100px 8% 60px;
      gap: 40px;
    }
    aside {
      width: 250px;
      background: #141414;
      border-radius: 12px;
      padding: 20px;
      height: fit-content;
    }
    aside h3 { color: #00bcd4; margin-bottom: 15px; }
    aside a {
      display: block; padding: 8px 0;
      border-bottom: 1px solid #222;
      color: #ccc; transition: 0.3s;
    }
    aside a:hover { color: #00bcd4; }

    .product-section { flex: 1; }
    .top-bar {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 25px;
    }
    .search-box input {
      padding: 8px 15px; border: none; border-radius: 25px; width: 220px;
      background: #1a1a1a; color: #fff;
    }
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 25px;
    }
    .product-card {
      background: #141414; border-radius: 12px;
      transition: transform 0.3s;
      padding-bottom: 15px;
    }
    .product-card:hover { transform: translateY(-5px); }
    .product-card img { border-radius: 12px 12px 0 0; height: 220px; object-fit: cover; width: 100%; }
    .product-card h4 { text-align: center; margin: 10px 0; font-weight: 600; }
    .price { color: #00bcd4; text-align: center; margin-bottom: 10px; }

    .pagination {
      text-align: center; margin-top: 40px;
    }
    .pagination a {
      color: #00bcd4; padding: 8px 15px;
      border: 1px solid #00bcd4;
      border-radius: 8px; margin: 0 5px;
      transition: 0.3s;
    }
    .pagination a:hover { background: #00bcd4; color: #fff; }

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
    @media(max-width: 768px){
      main { flex-direction: column; }
      aside { width: 100%; order: 2; }
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
      <li><a href="contact.php">Contact</a></li>
    </ul>
  </nav>
  <div class="icons">
    <a href="user/cart.php"><i class="fas fa-shopping-cart"></i></a>
    <a href="login.php"><i class="fas fa-user"></i></a>
  </div>
</header>

<main>
  <aside>
  <h3>Categories</h3>

  <?php
  // "All" option to show everything
  $isAllActive = !isset($_GET['cat']) ? "style='color:#00bcd4;'" : "";
  echo "<a href='category.php' $isAllActive>All</a>";

  // agar categories table nahi hai, to products table se distinct category names nikal lo
  $catStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

  while ($cat = $catStmt->fetch()) {
    $category = htmlspecialchars($cat['category']);
    $active = (isset($_GET['cat']) && $_GET['cat'] === $category) ? "style='color:#00bcd4;'" : "";
    echo "<a href='category.php?cat=" . urlencode($category) . "' $active>$category</a>";
  }
  ?>
</aside>


  <div class="product-section">
    <div class="top-bar">
      <h2>Shop Bottles</h2>
      <div class="search-box">
        <form method="GET" action="category.php">
          <input type="text" name="search" placeholder="Search bottle..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        </form>
      </div>
    </div>

    <div class="products-grid">
      <?php
      $query = "SELECT * FROM products WHERE 1";
      $params = [];

      if (!empty($_GET['cat'])) {
        $query .= " AND category = ?";
        $params[] = $_GET['cat'];
      }

      if (!empty($_GET['search'])) {
        $query .= " AND name LIKE ?";
        $params[] = "%" . $_GET['search'] . "%";
      }

      $query .= " ORDER BY id DESC";

      $stmt = $pdo->prepare($query);
      $stmt->execute($params);

      if ($stmt->rowCount() > 0) {
        while ($p = $stmt->fetch()) {
          echo "
          <div class='product-card'>
            <img src='admin/uploads/{$p['image']}' alt='{$p['name']}'>
            <h4>{$p['name']}</h4>
            <p class='price'>₹{$p['price']}</p>
            <div style='text-align:center;display:flex;gap:8px;justify-content:center;'>
              <a href='product.php?id={$p['id']}' class='btn'>View</a>
              <a href='#' class='btn' data-wishlist-add='{$p['id']}' title='Add to wishlist'><i class=\"fas fa-heart\"></i></a>
            </div>
          </div>";
        }
      } else {
        echo "<p style='text-align:center;width:100%;color:#777;'>No products found.</p>";
      }
      ?>
    </div>
  </div>
</main>

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
