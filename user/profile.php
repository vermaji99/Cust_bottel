<?php
session_start();
include '../includes/config.php';

// ---------------------------
// 1. SECURITY & DATA FETCH
// ---------------------------

// Agar user login nahi hai to login page pe bhej do
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// User details fetch
$userStmt = $pdo->prepare("SELECT id, name, email, phone, created_at, address, city, pincode FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// User orders fetch (Limited to 5 for profile overview)
$orderStmt = $pdo->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$orderStmt->execute([$user_id]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status messages from form submissions (e.g., redirect from update_profile.php)
$message = $_GET['message'] ?? '';
$status_type = $_GET['status'] ?? ''; // 'success' or 'error'

// Function to get status class (for consistency with other pages)
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'status-pending';
        case 'processing': return 'status-processing';
        case 'completed': return 'status-completed';
        case 'cancelled': return 'status-cancelled';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bottel | My Profile</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Base Styles */
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
.container {
    padding: 60px 8%;
    min-height: 80vh;
}
.btn {
    background: linear-gradient(45deg, #00bcd4, #007bff);
    padding: 8px 20px;
    border-radius: 25px;
    color: #fff;
    text-decoration: none;
    transition: 0.3s;
    border: none;
    cursor: pointer;
    font-weight: 500;
    display: inline-block;
}
.btn:hover { transform: scale(1.02); }

/* Message Box */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
}
.alert-success {
    background: #4caf5020;
    color: #4caf50;
    border: 1px solid #4caf50;
}
.alert-error {
    background: #f4433620;
    color: #f44336;
    border: 1px solid #f44336;
}

/* --- Profile Layout (Modern) --- */
.profile-content {
    display: flex;
    gap: 30px;
}
.profile-nav {
    flex: 0 0 250px; 
    background: #141414;
    padding: 20px 0;
    border-radius: 12px;
    height: fit-content;
}
.profile-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.profile-nav a {
    display: block;
    padding: 15px 25px;
    color: #eee;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: 0.3s;
}
.profile-nav a:hover {
    background: #1c1c1c;
    color: #00bcd4;
}
.profile-nav a.active {
    background: #00bcd41a; 
    border-left: 3px solid #00bcd4;
    color: #00bcd4;
    font-weight: 600;
}

/* Tab Content */
.profile-tabs {
    flex-grow: 1;
    background: #141414;
    padding: 30px;
    border-radius: 12px;
}
.tab-pane {
    display: none;
}
.tab-pane.active {
    display: block;
}
.tab-pane h3 {
    color: #00bcd4;
    margin-top: 0;
    border-bottom: 1px solid #222;
    padding-bottom: 15px;
    margin-bottom: 30px;
}

/* --- Form Styles --- */
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #ccc;
}
.form-group input {
    width: calc(100% - 20px);
    padding: 10px;
    background: #222;
    border: 1px solid #333;
    border-radius: 6px;
    color: white;
}

/* --- Orders Table --- */
.orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
.orders-table th, .orders-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #222;
    text-align: left;
}
.orders-table th { background: #1a1a1a; color: #00bcd4; }
.orders-table tr:hover { background: #1c1c1c; }

/* Status Classes */
.status-pending { color: #ff9800; font-weight: 600; }
.status-processing { color: #03a9f4; font-weight: 600; }
.status-completed { color: #4caf50; font-weight: 600; }
.status-cancelled { color: #f44336; font-weight: 600; }

/* Responsive Adjustments */
@media (max-width: 992px) {
    .profile-content {
        flex-direction: column;
    }
    .profile-nav {
        flex: auto;
        padding: 0;
    }
    .profile-nav ul {
        display: flex; 
        overflow-x: auto;
        padding: 10px 0;
        border-radius: 12px;
        background: #141414;
    }
    .profile-nav li {
        white-space: nowrap;
    }
    .profile-nav a {
        border-left: none;
        border-bottom: 3px solid transparent;
        padding: 10px 15px;
    }
    .profile-nav a.active {
        border-left: none;
        border-bottom: 3px solid #00bcd4;
    }
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
            <li><a href="cart.php">Cart</a></li>
        </ul>
    </nav>
    <div>
        <a href="../logout.php" class="btn" style="padding:6px 15px;font-size:0.9rem;">Logout</a>
    </div>
</header>

<div class="container">
    <h2 style="color:white; margin-bottom: 30px;"><i class="fas fa-user-circle"></i> Account Settings</h2>

    <?php if ($message && $status_type): ?>
        <div class="alert alert-<?= htmlspecialchars($status_type) ?>">
            <i class="fas fa-<?= ($status_type === 'success' ? 'check-circle' : 'exclamation-triangle') ?>"></i>
            <?= htmlspecialchars(urldecode($message)) ?>
        </div>
    <?php endif; ?>

    <div class="profile-content">
        <div class="profile-nav">
            <ul>
                <li><a href="#details" class="tab-link active" data-tab="details"><i class="fas fa-info-circle"></i> My Details</a></li>
                <li><a href="#orders" class="tab-link" data-tab="orders"><i class="fas fa-history"></i> Recent Orders</a></li>
                <li><a href="#password" class="tab-link" data-tab="password"><i class="fas fa-lock"></i> Change Password</a></li>
                <li><a href="#address" class="tab-link" data-tab="address"><i class="fas fa-map-marker-alt"></i> Shipping Address</a></li>
            </ul>
        </div>

        <div class="profile-tabs">
            
            <div id="details" class="tab-pane active">
                <h3>My Personal Details</h3>
                <form action="update_profile.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn">Save Changes</button>
                    <p style="color:#ff9800; margin-top:20px; font-size:0.9rem;">* Email cannot be changed here.</p>
                </form>
            </div>

            <div id="orders" class="tab-pane">
                <h3>Last 5 Orders</h3>
                <?php if (count($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                    <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                    <td class="<?= getStatusClass($order['status']) ?>"><?= ucfirst($order['status']) ?></td>
                                    <td><a href="order_details.php?id=<?= $order['id'] ?>" class="btn" style="padding: 4px 10px;">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="text-align: right; margin-top: 20px;"><a href="orders.php" style="color:#00bcd4; text-decoration:none; font-weight: 500;">View All Orders &rarr;</a></p>
                <?php else: ?>
                    <p style="color:#999;">No recent orders found. Time to shop!</p>
                <?php endif; ?>
            </div>

            <div id="password" class="tab-pane">
                <h3>Change Password</h3>
                <form action="change_password.php" method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn">Update Password</button>
                </form>
            </div>

            <div id="address" class="tab-pane">
                <h3>Shipping Address</h3>
                <form action="update_address.php" method="POST">
                    <div class="form-group">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" value="<?= htmlspecialchars($user['pincode'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn">Update Address</button>
                </form>
            </div>

        </div> </div> </div>

<footer>
    © <?= date("Y") ?> Bottel. All rights reserved.
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-link');
    const panes = document.querySelectorAll('.tab-pane');

    function setActiveTab(targetId) {
        tabs.forEach(link => link.classList.remove('active'));
        panes.forEach(pane => pane.classList.remove('active'));

        const targetLink = document.querySelector(`.tab-link[data-tab="${targetId}"]`);
        const targetPane = document.getElementById(targetId);

        if (targetLink && targetPane) {
            targetLink.classList.add('active');
            targetPane.classList.add('active');
        } else {
            // Fallback to default if hash is invalid
            document.querySelector('.tab-link[data-tab="details"]').classList.add('active');
            document.getElementById('details').classList.add('active');
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-tab');
            setActiveTab(targetId);
            history.pushState(null, null, `#${targetId}`);
        });
    });

    // Handle initial load based on URL hash (e.g., #orders)
    const initialHash = window.location.hash.substring(1) || 'details';
    setActiveTab(initialHash);
});
</script>
</body>
</html>