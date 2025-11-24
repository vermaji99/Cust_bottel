<?php
require __DIR__ . '/includes/bootstrap.php';
$currentUser = current_user();

// Product fetch logic
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: category.php");
  exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
  echo "<h2 style='color:white;text-align:center;margin-top:100px;'>❌ Product not found.</h2>";
  exit;
}
$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title><?= htmlspecialchars($product['name']) ?> | Bottel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      background: #0c0c0c;
      color: #f0f0f0;
    }
    header {
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      background: rgba(0, 0, 0, 0.9);
      padding: 15px 8%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 100;
      backdrop-filter: blur(10px);
    }
    .logo { font-size: 1.5rem; font-weight: bold; color: #00bcd4; }
    nav ul { list-style: none; display: flex; gap: 25px; }
    nav a { text-decoration: none; color: #fff; transition: 0.3s; }
    nav a:hover { color: #00bcd4; }

    main {
      padding: 120px 8% 60px;
      display: flex;
      flex-wrap: wrap;
      gap: 50px;
      justify-content: center;
    }
    .product-img {
      flex: 1 1 400px;
      max-width: 500px;
      background: #141414;
      border-radius: 12px;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .product-img img {
      max-width: 100%;
      border-radius: 12px;
      object-fit: cover;
    }
    .product-details {
      flex: 1 1 400px;
      max-width: 500px;
    }
    h1 {
      font-size: 2rem;
      margin-bottom: 15px;
      color: #00bcd4;
    }
    .price {
      font-size: 1.4rem;
      font-weight: 600;
      color: #00bcd4;
      margin-bottom: 20px;
    }
    .desc {
      line-height: 1.7;
      color: #ccc;
      margin-bottom: 25px;
    }
    .btn {
      background: linear-gradient(45deg, #00bcd4, #007bff);
      padding: 12px 30px;
      border-radius: 30px;
      color: white;
      text-decoration: none;
      font-weight: 600;
      transition: 0.3s;
      display: inline-block;
    }
    .btn:hover {
      transform: scale(1.05);
    }

    footer {
      background: #080808;
      padding: 50px 10% 20px;
      text-align: center;
      color: #777;
      margin-top: 50px;
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
</header>

<main>
  <div class="product-img">
    <img src="admin/uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
  </div>

  <div class="product-details">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <p class="price">₹<?= number_format($product['price'], 2) ?></p>
    <p class="desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
    <form method="POST" action="user/cart_action.php" style="display:inline;">
      <input type="hidden" name="csrf_token" value="<?= esc($csrf); ?>">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
      <input type="hidden" name="redirect" value="<?= esc($_SERVER['REQUEST_URI']); ?>">
      <button type="submit" class="btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>
    </form>
    <a href="#" class="btn" data-wishlist-add="<?= $product['id']; ?>"><i class="fas fa-heart"></i></a>
  </div>
</main>

<footer>
  <p>© 2025 Bottel. All Rights Reserved.</p>
</footer>

</body>
<script src="assets/js/app.js" defer></script>
</html>
