<?php
require __DIR__ . '/init.php';
$userId = $authUser['id'];
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired.';
    } else {
        $orderId = (int) $_POST['cancel_order'];
        $orderCheck = db()->prepare('SELECT status FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $orderCheck->execute([$orderId, $userId]);
        $orderStatus = $orderCheck->fetchColumn();
        if ($orderStatus === 'pending') {
            $update = db()->prepare('UPDATE orders SET status = "cancelled" WHERE id = ?');
            $update->execute([$orderId]);
            $timeline = db()->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
            $timeline->execute([$orderId, 'cancelled', 'Cancelled by customer']);
            $messages[] = 'Order cancelled.';
        } else {
            $errors[] = 'You can only cancel pending orders.';
        }
    }
}

$orderStmt = db()->prepare('
    SELECT o.*, d.thumbnail_path, d.design_key
    FROM orders o
    LEFT JOIN designs d ON d.id = o.design_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
');
$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

$timelines = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $timelineStmt = db()->prepare("SELECT * FROM order_status_history WHERE order_id IN ($placeholders) ORDER BY created_at ASC");
    $timelineStmt->execute($ids);
    foreach ($timelineStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $timelines[$row['order_id']][] = $row;
    }
}

function statusClass($status) {
    return match (strtolower($status)) {
        'pending' => 'status-pending',
        'processing', 'printed', 'shipped' => 'status-processing',
        'delivered' => 'status-completed',
        'cancelled' => 'status-cancelled',
        default => '',
    };
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bottel | My Orders</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
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
.container { padding: 60px 8%; min-height: 80vh; }
h2 { color: #00bcd4; margin-bottom: 20px; }
.order-card {
    background:#141414;
    padding:20px;
    border-radius:12px;
    margin-bottom:25px;
    box-shadow:0 10px 20px rgba(0,0,0,0.4);
}
.order-header {
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:10px;
    border-bottom:1px solid #1f1f1f;
    padding-bottom:12px;
    margin-bottom:15px;
}
.order-meta {
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    color:#bbb;
}
.timeline {
    display:flex;
    gap:20px;
    overflow-x:auto;
    padding-bottom:10px;
}
.timeline-step {
    min-width:140px;
    padding:10px;
    border-radius:10px;
    background:#1d1d1d;
    text-align:center;
    border:1px solid transparent;
}
.timeline-step.active { border-color:#00bcd4; }
.design-preview img { width:100px;border-radius:8px; }
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
    font-size: 0.9rem;
    display: inline-block;
}
.btn:hover { transform: scale(1.05); }
footer {
    background: #080808;
    text-align: center;
    padding: 30px;
    color: #666;
    margin-top: 60px;
}
</style>
</head>
<body>

<header>
    <div class="logo">Bottel</div>
    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="cart.php">Cart</a></li>
        </ul>
    </nav>
    <div>
        <a href="../logout.php" class="btn">Logout</a>
    </div>
</header>

<div class="container">
    <h2><i class="fas fa-history"></i> My Orders History</h2>

    <?php if ($messages): ?>
        <div style="background:#103a2d;padding:10px;border-radius:8px;color:#6ef1c2;margin-bottom:20px;"><?= esc(implode(' ', $messages)); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="background:#3a1010;padding:10px;border-radius:8px;color:#ffb3b3;margin-bottom:20px;"><?= esc(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <?php if ($orders): ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <strong>Order #<?= esc($order['order_number']); ?></strong>
                        <div style="color:#aaa;font-size:0.9rem;"><?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="order-meta">
                        <span>Total: ₹<?= number_format($order['total_amount'], 2); ?></span>
                        <span class="<?= statusClass($order['status']); ?>"><?= ucfirst($order['status']); ?></span>
                    </div>
                </div>
                <?php if (!empty($order['thumbnail_path'])): ?>
                    <div class="design-preview" style="margin-bottom:15px;">
                        <strong>Design:</strong>
                        <img src="../<?= esc($order['thumbnail_path']); ?>" alt="Design thumbnail">
                        <span><?= esc($order['design_key']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="timeline">
                    <?php foreach ($timelines[$order['id']] ?? [] as $step): ?>
                        <div class="timeline-step <?= statusClass($step['status']); ?>">
                            <div style="font-weight:600;"><?= ucfirst($step['status']); ?></div>
                            <small><?= date('d M H:i', strtotime($step['created_at'])); ?></small>
                            <div style="font-size:0.8rem;color:#aaa;"><?= esc($step['note'] ?? ''); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($order['status'] === 'pending'): ?>
                    <form method="POST" style="margin-top:15px;">
                        <?= csrf_field(); ?>
                        <button type="submit" name="cancel_order" value="<?= $order['id']; ?>" class="btn" style="background:#f44336;">Cancel Order</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 40px; background:#141414; border-radius:12px;">
            <i class="fas fa-box-open" style="font-size: 2rem; color: #ff9800; margin-bottom: 15px;"></i>
            <p style="color:#eee; margin-top:10px;">You haven't placed any orders yet.</p>
            <a href="../category.php" class="btn" style="margin-top: 15px;">Start Shopping Now</a>
        </div>
    <?php endif; ?>
</div>

<footer>
    © <?= date("Y") ?> Bottel. All rights reserved.
</footer>

</body>
<script src="../assets/js/app.js" defer></script>
</html>