<?php
include '../includes/config.php';
include 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin | Manage Users</title>
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
      transition: 0.3s;
      border: 1px solid #00bcd4;
    }
    header a:hover { background: #00bcd4; color: #111; }

    .container {
      padding: 40px;
    }
    h3 { color: #00bcd4; margin-bottom: 20px; }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #141414;
      border-radius: 10px;
      overflow: hidden;
    }
    th, td {
      padding: 12px 15px;
      text-align: left;
    }
    th {
      background: #00bcd4;
      color: #111;
      font-weight: 600;
    }
    tr:nth-child(even) { background: #1a1a1a; }
    tr:hover { background: #222; transition: 0.3s; }
    td a.btn-del {
      color: #ff5252;
      text-decoration: none;
      font-weight: 600;
    }
    td a.btn-del:hover { text-decoration: underline; }

    .msg {
      margin-bottom: 20px;
      padding: 10px;
      border-radius: 6px;
      text-align: center;
    }
    .msg.success { background: #003d33; color: #4caf50; }
    .msg.error { background: #3d0000; color: #f44336; }
  </style>
</head>
<body>

<header>
  <h2>👤 Manage Users</h2>
  <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
</header>

<div class="container">
  <?php
  // delete user if requested
  if (isset($_GET['delete'])) {
      $uid = intval($_GET['delete']);
      $del = $pdo->prepare("DELETE FROM users WHERE id=?");
      if ($del->execute([$uid])) {
          echo "<div class='msg success'>User deleted successfully!</div>";
      } else {
          echo "<div class='msg error'>Failed to delete user.</div>";
      }
  }

  $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY id DESC");
  if ($stmt->rowCount() > 0) {
      echo "<table>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Registered On</th>
                <th>Actions</th>
              </tr>";
      while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
          echo "<tr>
                  <td>{$u['id']}</td>
                  <td>{$u['name']}</td>
                  <td>{$u['email']}</td>
                  <td>{$u['created_at']}</td>
                  <td><a href='?delete={$u['id']}' class='btn-del' onclick='return confirm(\"Delete this user?\")'>Delete</a></td>
                </tr>";
      }
      echo "</table>";
  } else {
      echo "<p>No users found yet.</p>";
  }
  ?>
</div>

</body>
</html>
