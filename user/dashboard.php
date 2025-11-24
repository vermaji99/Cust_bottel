<?php
require __DIR__ . '/init.php';
$user = $authUser;
$user_id = $authUser['id'];

// Quick stats
// A. Order Count
$orderCountStmt = $pdo->prepare("SELECT COUNT(id) FROM orders WHERE user_id = ?");
$orderCountStmt->execute([$user_id]);
$orderCount = $orderCountStmt->fetchColumn();

$wishCount = wishlist_count($user_id);
$cartCount = cart_count($user_id);

// Recent orders
$recentOrdersStmt = $pdo->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$recentOrdersStmt->execute([$user_id]);
$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to determine CSS class based on status
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'status-pending';
        case 'processing':
            return 'status-processing';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
<title>Bottel | Dashboard</title>
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

/* Dashboard-specific adjustments */
nav li { /* Ensures the navigation looks clean */
    display: inline-block;
}

.container { padding: 60px 8%; }
h2 { color: #00bcd4; margin-bottom: 25px; }
.stats {
    display: flex; gap: 25px;
    flex-wrap: wrap;
    margin-bottom: 40px;
}
.stat-card {
    flex: 1;
    min-width: 150px; /* Added min-width for better mobile handling */
    background: #141414;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0,188,212,0.1);
    transition: transform 0.3s;
}
.stat-card:hover { transform: translateY(-5px); }
.stat-card h3 { color: #00bcd4; margin-bottom: 10px; font-size: 1.2rem; }
.stat-card p { font-size: 1.5rem; font-weight: 600; }
.stat-card i { margin-right: 5px; color: #00bcd4; } /* Icon style */

.recent-orders table {
    width: 100%;
    border-collapse: collapse;
    background: #141414;
    border-radius: 12px;
    overflow: hidden;
}
.recent-orders th, .recent-orders td {
    padding: 12px 15px;
    border-bottom: 1px solid #222;
    text-align: center;
}
.recent-orders th { background: #1a1a1a; color: #00bcd4; }
.recent-orders tr:hover { background: #1c1c1c; }

/* Status Classes */
.status-pending { color: #ff9800; font-weight: 600; }
.status-processing { color: #03a9f4; font-weight: 600; }
.status-completed { color: #4caf50; font-weight: 600; }
.status-cancelled { color: #f44336; font-weight: 600; }

.btn { 
    background: linear-gradient(45deg, #00bcd4, #007bff);
    padding: 6px 18px;
    border-radius: 25px;
    color: #fff;
    text-decoration: none;
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
@media(max-width:768px){
    .stats { flex-direction: column; }
}
</style>
</head>
<body>

<header>
    <div class="logo">Bottel</div>
    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../category.php">Shop</a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="wishlist.php">Wishlist</a></li>
        </ul>
    </nav>
    <div>
        <a href="../logout.php" class="btn">Logout</a>
    </div>
</header>

<div class="container">
    <h2>Welcome Back, <span style="color:white;"><?= htmlspecialchars($user['name'] ?? 'User') ?></span>!</h2>

    <div class="stats">
        <div class="stat-card">
            <h3><i class="fas fa-box"></i> Total Orders</h3>
            <p><?= $orderCount ?></p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-heart"></i> Wishlist Items</h3>
            <p><?= $wishCount ?></p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-shopping-cart"></i> Items in Cart</h3>
            <p><?= $cartCount ?></p>
        </div>
        <div class="stat-card" style="background:#00bcd41a;">
            <h3><i class="fas fa-envelope"></i> Email</h3>
            <p style="font-size: 1rem;"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
        </div>
    </div>

    <div class="recent-orders">
        <h3 style="color:#00bcd4; margin-bottom:15px;">Recent Orders</h3>
        <?php if($orderCount > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($recentOrders as $o): 
                    $orderStatus = htmlspecialchars($o['status']);
                ?>
                    <tr>
                        <td>#<?= $o['id'] ?></td>
                        <td>₹<?= number_format($o['total_amount'],2) ?></td>
                        <td class="<?= getStatusClass($orderStatus) ?>"><?= ucfirst($orderStatus) ?></td>
                        <td><?= date('j M Y', strtotime($o['created_at'])) ?></td>
                        <td><a href="order_details.php?id=<?= $o['id'] ?>" class="btn" style="padding: 4px 10px; font-size:0.9rem;">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align: right; margin-top: 15px;"><a href="orders.php" style="color:#00bcd4; text-decoration:none;">View all orders &rarr;</a></p>
        <?php else: ?>
            <p style="color:#999; margin-top:20px;">
                You haven't placed any orders yet. <a href="../category.php" style="color:#00bcd4; text-decoration:none;">Start shopping now!</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<footer>
    © <?= date("Y") ?> Bottel. All rights reserved.
</footer>

</body>
<script src="../assets/js/app.js" defer></script>
</html>