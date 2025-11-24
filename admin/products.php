<?php
include '../includes/config.php';
include 'auth_check.php';

// 🔧 Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
  $name = trim($_POST['name']);
  $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
  $desc = trim($_POST['description']);
  $price = $_POST['price'];
  $category = trim($_POST['category']);

  // File upload
  $image = '';
  if (!empty($_FILES['image']['name'])) {
    $image = time() . '_' . basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);
  }

  $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, price, category, image) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->execute([$name, $slug, $desc, $price, $category, $image]);
  $message = "✅ Product added successfully!";
}

// 🗑 Delete Product
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
  $message = "🗑 Product deleted successfully!";
}

// 🧾 Fetch all products
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin | Manage Products</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: #0c0c0c;
      color: #f5f5f5;
      margin: 0;
      padding: 0;
    }
    header {
      background: #111;
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #222;
    }
    header h1 { color: #00bcd4; margin: 0; font-size: 1.5rem; }
    .logout a { color: #ff5252; text-decoration: none; }

    .container {
      padding: 40px;
      max-width: 1200px;
      margin: auto;
    }

    .message {
      background: #1b1b1b;
      color: #00bcd4;
      padding: 10px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: inline-block;
    }

    .form-box {
      background: #121212;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(255,255,255,0.05);
      margin-bottom: 40px;
    }

    .form-box h2 { margin-top: 0; color: #00bcd4; }
    form input, form textarea {
      width: 100%;
      padding: 10px;
      margin: 8px 0 15px;
      border-radius: 8px;
      border: none;
      background: #1f1f1f;
      color: #fff;
    }
    form button {
      background: linear-gradient(45deg, #00bcd4, #007bff);
      border: none;
      padding: 10px 25px;
      color: #fff;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }
    form button:hover { transform: scale(1.05); }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #111;
      border-radius: 12px;
      overflow: hidden;
    }
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #222;
    }
    th {
      background: #1b1b1b;
      color: #00bcd4;
    }
    tr:hover { background: #1a1a1a; }

    img.thumb {
      width: 60px; height: 60px;
      border-radius: 6px;
      object-fit: cover;
    }

    .actions a {
      margin-right: 10px;
      color: #00bcd4;
      text-decoration: none;
    }
    .actions a.delete { color: #ff5252; }
    .actions a:hover { text-decoration: underline; }

    @media(max-width:768px){
      table, thead, tbody, th, td, tr { display: block; }
      th { display: none; }
      td { border: none; padding: 10px 0; }
      tr { margin-bottom: 20px; border-bottom: 1px solid #333; }
    }
  </style>
</head>

<body>
<header>
  <h1>🧴 Bottel Admin Panel</h1>
  <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</header>

<div class="container">
  <h2>Manage Products</h2>
  <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

  <!-- Add Product Form -->
  <div class="form-box">
    <h2>Add New Product</h2>
    <form method="POST" enctype="multipart/form-data">
      <label>Product Name</label>
      <input type="text" name="name" required>

      <label>Description</label>
      <textarea name="description" rows="3" required></textarea>

      <label>Price (₹)</label>
      <input type="number" step="0.01" name="price" required>

      <label>Category</label>
      <input type="text" name="category" placeholder="e.g., Restaurant, Event, Corporate" required>

      <label>Image</label>
      <input type="file" name="image" accept="image/*" required>

      <button type="submit" name="add_product">Add Product</button>
    </form>
  </div>

  <!-- Products Table -->
  <h2>All Products</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Image</th>
        <th>Name</th>
        <th>Category</th>
        <th>Price (₹)</th>
        <th>Slug</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($products) > 0): ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= $p['id'] ?></td>
            <td><img src="uploads/<?= htmlspecialchars($p['image']) ?>" class="thumb"></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td><?= $p['price'] ?></td>
            <td><?= htmlspecialchars($p['slug']) ?></td>
            <td class="actions">
              <a href="edit_product.php?id=<?= $p['id'] ?>"><i class="fas fa-edit"></i></a>
              <a href="?delete=<?= $p['id'] ?>" class="delete" onclick="return confirm('Delete this product?');"><i class="fas fa-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">No products found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
