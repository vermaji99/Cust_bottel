<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Bottel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      background: #0c0c0c;
      color: #eee;
    }
    .admin-container {
      display: flex;
      min-height: 100vh;
    }
    /* Sidebar */
    .sidebar {
      width: 250px;
      background: #141414;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 15px;
      box-shadow: 2px 0 10px rgba(0,0,0,0.5);
    }
    .sidebar h2 {
      color: #00bcd4;
      text-align: center;
      margin-bottom: 20px;
    }
    .sidebar a {
      color: #ccc;
      text-decoration: none;
      padding: 10px 15px;
      border-radius: 8px;
      transition: 0.3s;
    }
    .sidebar a:hover {
      background: #00bcd4;
      color: #111;
    }

    /* Main content */
    .main-content {
      flex: 1;
      padding: 40px;
    }
    .title {
      font-size: 1.8rem;
      color: #00bcd4;
    }
    .subtitle {
      color: #999;
      margin-bottom: 25px;
    }

    /* Stats cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 25px;
      margin-bottom: 40px;
    }
    .card {
      background: #141414;
      text-align: center;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,188,212,0.1);
      transition: 0.3s;
    }
    .card:hover {
      transform: translateY(-5px);
    }
    .card i {
      font-size: 2rem;
      color: #00bcd4;
      margin-bottom: 10px;
    }
    .card h3 {
      margin: 10px 0 5px;
      color: #fff;
    }
    .card p {
      color: #aaa;
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
      background: #141414;
      border-radius: 10px;
      overflow: hidden;
    }
    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #222;
      text-align: left;
    }
    th {
      background: #1b1b1b;
      color: #00bcd4;
    }
    tr:hover {
      background: #1f1f1f;
    }
    .status {
      padding: 4px 10px;
      border-radius: 6px;
      color: #fff;
      font-size: 0.85rem;
    }
    .status.green { background: #28a745; }
    .status.orange { background: #ff9800; }
    .status.red { background: #f44336; }

    .recent-orders h2 {
      margin-bottom: 15px;
      color: #00bcd4;
    }

    @media(max-width: 768px) {
      .admin-container { flex-direction: column; }
      .sidebar { width: 100%; flex-direction: row; overflow-x: auto; }
      .main-content { padding: 20px; }
    }
  </style>
</head>

<body>

<div class="admin-container">

  <!-- Sidebar -->
  <div class="sidebar">
    <h2>🧴 Bottel Admin</h2>
    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="products.php"><i class="fas fa-box"></i> Products</a>
    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="users.php"><i class="fas fa-users"></i> Users</a>
    <a href="settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="logout.php" style="color:#f44336;"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h1 class="title">Welcome, Admin 👑</h1>
    <p class="subtitle">Here’s a quick overview of your business performance.</p>

    <div class="stats-grid">
      <?php
      $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
      $order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
      $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
      ?>

      <div class="card">
        <i class="fas fa-bottle-water"></i>
        <h3><?= $product_count ?></h3>
        <p>Products</p>
      </div>

      <div class="card">
        <i class="fas fa-shopping-bag"></i>
        <h3><?= $order_count ?></h3>
        <p>Orders</p>
      </div>

      <div class="card">
        <i class="fas fa-users"></i>
        <h3><?= $user_count ?></h3>
        <p>Users</p>
      </div>

      <div class="card">
        <i class="fas fa-cogs"></i>
        <h3>Admin</h3>
        <p>Settings</p>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="recent-orders">
      <h2>Recent Orders</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Total (₹)</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("
            SELECT o.*, 
                   COALESCE(o.name, u.name) AS customer_name,
                   o.total_amount, o.status, o.created_at
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.id DESC
            LIMIT 10
          ");
          $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if ($orders && count($orders) > 0) {
            foreach ($orders as $o) {
              $status_color = match($o['status']) {
                'Delivered' => 'green',
                'Pending' => 'orange',
                default => 'red'
              };
              echo "
              <tr>
                <td>#{$o['id']}</td>
                <td>" . htmlspecialchars($o['customer_name'] ?? 'Unknown') . "</td>
                <td>₹" . number_format($o['total_amount'] ?? 0, 2) . "</td>
                <td><span class='status {$status_color}'>" . htmlspecialchars($o['status'] ?? 'Pending') . "</span></td>
                <td>" . date('d M Y', strtotime($o['created_at'])) . "</td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='5' style='text-align:center;color:#888;'>No recent orders found</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

  </div> <!-- main-content -->
</div> <!-- admin-container -->

</body>
</html>
