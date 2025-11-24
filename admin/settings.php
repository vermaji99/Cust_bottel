<?php
include '../includes/config.php';
include 'auth_check.php';

// create settings table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  site_name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(50),
  address TEXT,
  logo VARCHAR(255),
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// fetch settings row
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // handle logo upload
    $logoPath = $settings['logo'] ?? '';
    if (!empty($_FILES['logo']['name'])) {
        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        $target = "uploads/" . $fileName;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
            $logoPath = $fileName;
        }
    }

    if ($settings) {
        $stmt = $pdo->prepare("UPDATE settings SET site_name=?, email=?, phone=?, address=?, logo=? WHERE id=?");
        $stmt->execute([$site_name, $email, $phone, $address, $logoPath, $settings['id']]);
        $msg = "Settings updated successfully!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (site_name, email, phone, address, logo) VALUES (?,?,?,?,?)");
        $stmt->execute([$site_name, $email, $phone, $address, $logoPath]);
        $msg = "Settings saved successfully!";
    }

    header("Location: settings.php?success=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin | Site Settings</title>
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

    .container {
      padding: 40px;
      max-width: 700px;
      margin: auto;
      background: #141414;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,188,212,0.1);
    }
    h3 {
      color: #00bcd4;
      margin-bottom: 20px;
      text-align: center;
    }
    label {
      display: block;
      margin-bottom: 6px;
      color: #aaa;
    }
    input[type="text"], input[type="email"], textarea {
      width: 100%;
      padding: 10px 14px;
      border: none;
      border-radius: 6px;
      background: #1a1a1a;
      color: #fff;
      margin-bottom: 20px;
      font-size: 0.95rem;
    }
    textarea { resize: vertical; height: 100px; }
    input[type="file"] { margin-bottom: 20px; color: #ccc; }
    .btn {
      display: inline-block;
      background: linear-gradient(45deg, #00bcd4, #007bff);
      border: none;
      padding: 12px 25px;
      color: #fff;
      border-radius: 25px;
      cursor: pointer;
      transition: 0.3s;
      font-size: 1rem;
      width: 100%;
    }
    .btn:hover { transform: scale(1.03); }
    .preview {
      text-align: center;
      margin-bottom: 20px;
    }
    .preview img {
      max-height: 120px;
      border-radius: 10px;
      background: #222;
      padding: 5px;
    }
    .msg {
      background: #003d33;
      color: #4caf50;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<header>
  <h2>⚙️ Site Settings</h2>
  <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
</header>

<div class="container">
  <?php if (isset($_GET['success'])): ?>
    <div class="msg">✅ Settings saved successfully!</div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <h3>Website Information</h3>

    <label>Website Name</label>
    <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>

    <label>Contact Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>" required>

    <label>Contact Phone</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">

    <label>Address</label>
    <textarea name="address"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>

    <label>Website Logo</label>
    <input type="file" name="logo" accept="image/*">

    <?php if (!empty($settings['logo'])): ?>
    <div class="preview">
      <img src="uploads/<?= htmlspecialchars($settings['logo']) ?>" alt="Logo Preview">
    </div>
    <?php endif; ?>

    <button class="btn" type="submit">💾 Save Settings</button>
  </form>
</div>

</body>
</html>
