<?php
require __DIR__ . '/includes/bootstrap.php';

// Require admin authentication
$admin = require_admin_auth();

// Dashboard Analytics
$stats = [
    'total_users' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn(),
    'total_orders' => (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'total_products' => (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'total_revenue' => (float) db()->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE LOWER(status) IN ("delivered", "shipped")')->fetchColumn(),
    'pending_orders' => (int) db()->query('SELECT COUNT(*) FROM orders WHERE LOWER(status) = "pending"')->fetchColumn(),
    'active_coupons' => (int) db()->query('SELECT COUNT(*) FROM coupons WHERE is_active = 1')->fetchColumn(),
];

// Recent orders for dashboard
$recentOrders = db()->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Revenue chart data (last 30 days)
// Note: Status values might be in different case (Delivered, Shipped vs delivered, shipped)
$revenueData = db()->query("
    SELECT DATE(created_at) as date, SUM(total_amount) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND LOWER(status) IN ('delivered', 'shipped')
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Top selling products
// Note: order_items table uses 'price' column, not 'unit_price'
$topProducts = db()->query("
    SELECT p.name, p.image, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE LOWER(o.status) IN ('delivered', 'shipped')
    GROUP BY p.id, p.name, p.image
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Bottle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('dashboard') ?>
        
        <div class="admin-main">
            <?= admin_header('Dashboard', 'Welcome back, ' . esc($admin['name'])) ?>
            
            <div class="admin-content">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?= number_format($stats['total_users']) ?></h3>
                        <p>Total Users</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-shopping-bag"></i>
                        <h3><?= number_format($stats['total_orders']) ?></h3>
                        <p>Total Orders</p>
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <small class="stat-change"><?= $stats['pending_orders'] ?> pending</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-bottle-water"></i>
                        <h3><?= number_format($stats['total_products']) ?></h3>
                        <p>Products</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-rupee-sign"></i>
                        <h3>₹<?= number_format($stats['total_revenue'], 2) ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3><?= number_format($stats['pending_orders']) ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-tag"></i>
                        <h3><?= number_format($stats['active_coupons']) ?></h3>
                        <p>Active Coupons</p>
                    </div>
                </div>

                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <!-- Revenue Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Revenue (Last 30 Days)</h3>
                        </div>
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                    
                    <!-- Top Products -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-trophy"></i> Top Products</h3>
                        </div>
                        <div style="padding: 10px 0;">
                            <?php if (empty($topProducts)): ?>
                                <p style="color: #777; text-align: center; padding: 20px;">No sales data yet</p>
                            <?php else: ?>
                                <?php foreach ($topProducts as $idx => $product): ?>
                                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border);">
                                        <span style="color: var(--accent); font-weight: 700; width: 24px;"><?= $idx + 1 ?></span>
                                        <?php if ($product['image']): ?>
                                            <img src="uploads/<?= esc($product['image']) ?>" alt="" class="img-thumb">
                                        <?php endif; ?>
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600;"><?= esc($product['name']) ?></div>
                                            <small style="color: var(--text-muted);">Sold: <?= (int)$product['total_sold'] ?> | ₹<?= number_format($product['revenue'], 2) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Recent Orders</h3>
                        <a href="orders.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #777; padding: 40px;">
                                            <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                            No orders yet
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <?php
                                        $statusClass = match(strtolower($order['status'] ?? 'pending')) {
                                            'delivered' => 'badge-success',
                                            'shipped' => 'badge-info',
                                            'processing' => 'badge-info',
                                            'printed' => 'badge-warning',
                                            'cancelled' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                        ?>
                                        <tr>
                                            <td><strong>#<?= esc($order['order_number'] ?? $order['id']) ?></strong></td>
                                            <td>
                                                <?= esc($order['shipping_name'] ?? $order['user_name'] ?? 'Guest') ?><br>
                                                <small style="color: #777;"><?= esc($order['shipping_email'] ?? $order['user_email'] ?? '') ?></small>
                                            </td>
                                            <td>₹<?= number_format($order['total_amount'] ?? 0, 2) ?></td>
                                            <td><span class="badge <?= $statusClass ?>"><?= esc(ucfirst($order['status'] ?? 'Pending')) ?></span></td>
                                            <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <a href="orders.php?view=<?= $order['id'] ?>" class="action-btn action-btn-view" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            const revenueData = <?= json_encode($revenueData) ?>;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: revenueData.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: revenueData.map(d => parseFloat(d.revenue || 0)),
                        borderColor: '#00bcd4',
                        backgroundColor: 'rgba(0, 188, 212, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                },
                                color: '#aaa'
                            },
                            grid: { color: '#222' }
                        },
                        x: {
                            ticks: { color: '#aaa' },
                            grid: { color: '#222' }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
