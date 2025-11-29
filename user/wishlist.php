<?php
require __DIR__ . '/init.php';
$wishlist = wishlist_items($authUser['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
<title>Bottle | Wishlist</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    font-family: "Poppins", sans-serif;
    margin: 0;
    background: #0b0b0b;
    color: #eee;
}
header {
    background: rgba(0,0,0,0.95);
    display: flex; justify-content: space-between; align-items: center;
    padding: 15px 8%;
    position: sticky; top: 0;
    z-index: 10;
}
.logo { font-size: 1.5rem; color: #00bcd4; font-weight: bold; }
nav ul { display: flex; list-style: none; gap: 25px; }
nav a { color: #eee; text-decoration: none; transition: 0.3s; }
nav a:hover { color: #00bcd4; }
.container { padding: 60px 8%; }
h2 { color: #00bcd4; margin-bottom: 25px; }
.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
}
.product-card {
    background: #141414;
    border-radius: 12px;
    padding-bottom: 15px;
    transition: transform 0.3s;
}
.product-card:hover { transform: translateY(-5px); }
.product-card img { border-radius: 12px 12px 0 0; height: 220px; object-fit: cover; width: 100%; }
.product-card h4 { text-align: center; margin: 10px 0; font-weight: 600; }
.price { color: #00bcd4; text-align: center; margin-bottom: 10px; }
.btn {
    display: inline-block;
    background: linear-gradient(45deg, #00bcd4, #007bff);
    padding: 6px 18px;
    border-radius: 25px;
    color: #fff;
    text-decoration: none;
    margin: 0 5px;
    transition: 0.3s;
}
.btn:hover { transform: scale(1.05); }
footer {
    background: #080808;
    text-align: center;
    padding: 30px;
    color: #666;
    margin-top: 60px;
}
body {
    padding-top: 0;
}
</style>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>

<?php
$currentPage = 'wishlist';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="padding-top: 100px;">
    <h2>My Wishlist</h2>

    <?php if(count($wishlist) > 0): ?>
    <div class="wishlist-grid">
        <?php foreach($wishlist as $item): ?>
        <div class="product-card">
            <img src="../admin/uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
            <h4><?= htmlspecialchars($item['name']) ?></h4>
            <p class="price">₹<?= number_format($item['price'], 2) ?></p>
            <div style="text-align:center;">
                <a href="../product.php?id=<?= $item['product_id'] ?>" class="btn">View</a>
                <a href="#" class="btn" data-move-to-cart="<?= $item['product_id'] ?>">Move to Cart</a>
                <a href="#" data-wishlist-remove="<?= $item['product_id'] ?>" class="btn" style="background:#f44336;">Remove</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="color:#999;">Your wishlist is empty.</p>
    <?php endif; ?>
</div>

<footer>
    © <?= date("Y") ?> Bottle. All rights reserved.
</footer>

</body>
<script src="../assets/js/navbar.js" defer></script>
<script src="../assets/js/app.js" defer></script>
</html>
