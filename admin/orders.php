<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$statusOptions = ['pending','processing','printed','shipped','delivered','cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        header('Location: orders.php?error=csrf');
        exit;
    }
    $orderId = (int) $_POST['order_id'];
    $status = strtolower($_POST['status'] ?? 'pending');
    if (!in_array($status, $statusOptions, true)) {
        header('Location: orders.php?error=status');
        exit;
    }
    $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$status, $orderId]);
    $timeline = db()->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
    $timeline->execute([$orderId, $status, 'Status updated by admin']);
    header('Location: orders.php?updated=1');
    exit;
}

$statusFilter = strtolower($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($statusFilter && in_array($statusFilter, $statusOptions, true)) {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($search) {
    $where[] = '(o.order_number LIKE ? OR o.shipping_email LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM orders o $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$orderSql = "
    SELECT o.*, d.thumbnail_path, u.name AS user_name
    FROM orders o
    LEFT JOIN designs d ON d.id = o.design_id
    LEFT JOIN users u ON u.id = o.user_id
    $whereSql
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$orderStmt = db()->prepare($orderSql);
$orderStmt->execute($params);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

$summary = db()->query("
    SELECT 
      COUNT(*) AS total_orders,
      SUM(total_amount) AS revenue,
      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders
    FROM orders
")->fetch(PDO::FETCH_ASSOC);
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
  <title>Admin | Manage Orders</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      margin: 0;
      background: #0b0b0b;
      color: #eee;
    }
    header {
      background: #111;
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }
    header h2 { color: #00bcd4; }
    header a {
      color: #00bcd4;
      background: #111;
      padding: 8px 18px;
      border-radius: 8px;
      text-decoration: none;
      border: 1px solid #00bcd4;
      transition: 0.3s;
    }
    header a:hover { background: #00bcd4; color: #111; }

    .summary-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
      gap:20px;
      margin:40px 0;
    }
    .summary-card {
      background:#141414;
      padding:20px;
      border-radius:12px;
      border:1px solid #1f1f1f;
    }
    .container {
      padding: 40px;
      max-width: 1200px;
      margin: 0 auto 40px;
      background: #141414;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,188,212,0.1);
    }
    .filters {
      display:flex;
      gap:15px;
      margin-bottom:20px;
      flex-wrap:wrap;
    }
    .filters input, .filters select {
      padding:8px 12px;
      border-radius:8px;
      border:1px solid #333;
      background:#1b1b1b;
      color:#fff;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      color: #eee;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #333;
      text-align: left;
    }
    th {
      background: #1a1a1a;
      color: #00bcd4;
      text-transform: uppercase;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
    }
    tr:hover { background: #1b1b1b; }

    select, button {
      padding: 6px 10px;
      border: none;
      border-radius: 5px;
    }
    select { background: #1a1a1a; color: #fff; }
    button {
      background: #00bcd4;
      color: #111;
      cursor: pointer;
      transition: 0.3s;
    }
    button:hover { transform: scale(1.05); }

    .msg {
      background: #003d33;
      color: #4caf50;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 20px;
    }

    .btn {
      background: linear-gradient(45deg, #00bcd4, #007bff);
      color: #fff;
      padding: 6px 14px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 0.9rem;
      transition: 0.3s;
    }
    .btn:hover {
      transform: scale(1.05);
      background: #00bcd4;
    }

    @media (max-width: 768px) {
      table { font-size: 0.85rem; }
      th, td { padding: 8px; }
    }
  </style>
</head>
<body>

<header>
  <h2>📦 Manage Orders</h2>
  <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
</header>

<div class="summary-grid">
  <div class="summary-card">
    <div>Total Orders</div>
    <h2><?= (int) ($summary['total_orders'] ?? 0); ?></h2>
  </div>
  <div class="summary-card">
    <div>Pending</div>
    <h2><?= (int) ($summary['pending_orders'] ?? 0); ?></h2>
  </div>
  <div class="summary-card">
    <div>Revenue</div>
    <h2>₹<?= number_format($summary['revenue'] ?? 0, 2); ?></h2>
  </div>
</div>

<div class="container">
  <?php if (isset($_GET['updated'])): ?>
    <div class="msg">✅ Order status updated!</div>
  <?php endif; ?>
  <form class="filters" method="GET">
    <select name="status">
      <option value="">All Statuses</option>
      <?php foreach ($statusOptions as $status): ?>
        <option value="<?= $status; ?>" <?= $status === $statusFilter ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="q" value="<?= esc($search); ?>" placeholder="Search order no. or email">
    <button type="submit">Filter</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Order</th>
        <th>Customer</th>
        <th>Total</th>
        <th>Status</th>
        <th>Design</th>
        <th>Placed On</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($orders): ?>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td>#<?= esc($order['order_number']); ?></td>
            <td><?= esc($order['shipping_name'] ?? $order['user_name'] ?? 'Guest'); ?><br><small><?= esc($order['shipping_email']); ?></small></td>
            <td>₹<?= number_format($order['total_amount'], 2); ?></td>
            <td>
              <form method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach ($statusOptions as $status): ?>
                    <option value="<?= $status; ?>" <?= $order['status'] === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="update_status" value="1">
              </form>
            </td>
            <td>
              <?php if (!empty($order['thumbnail_path'])): ?>
                <img src="../<?= esc($order['thumbnail_path']); ?>" alt="Design" style="width:60px;border-radius:6px;">
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td><?= date('d M Y H:i', strtotime($order['created_at'])); ?></td>
            <td>
              <a href="order_view.php?id=<?= $order['id']; ?>" class="btn"><i class="fas fa-eye"></i> View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;color:#777;">No orders found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($totalPages > 1): ?>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?= $p; ?>&status=<?= esc($statusFilter); ?>&q=<?= esc($search); ?>" style="padding:6px 10px;border-radius:6px;background:<?= $p === $page ? '#00bcd4' : '#1b1b1b'; ?>;color:#fff;text-decoration:none;">
          <?= $p; ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
