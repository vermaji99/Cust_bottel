<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentUser = current_user();

if (!$currentUser) {
    header("Location: ../login.php");
    exit;
}

// ---------------------------
// 1. SECURITY & DATA FETCH
// ---------------------------

$user_id = $currentUser['id'];

// User details fetch - Get all fields from database
$userStmt = db()->prepare("SELECT id, name, email, phone, mobile, address, city, pincode, created_at FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// If user not found, redirect
if (!$user) {
    header("Location: ../login.php");
    exit;
}

// User orders fetch (Limited to 5 for profile overview)
$orderStmt = db()->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$orderStmt->execute([$user_id]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart and wishlist counts
$cartCount = cart_count($user_id);
$wishlistCount = wishlist_count($user_id);

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
<title>Bottle | My Profile</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/navbar.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<style>
/* --- RESET & BASICS --- */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: "Poppins", sans-serif;
    background: radial-gradient(circle at 50% 0%, #1a1f25 0%, #0b0b0b 60%);
    color: #e0e0e0;
    line-height: 1.6;
    overflow-x: hidden;
    min-height: 100vh;
}

/* Apply home page styles for laptop/desktop only */
@media (min-width: 1024px) {
    html {
        font-size: 16px;
    }
    
    body {
        font-family: 'Space Grotesk', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: #f5f5f5;
        background: #0B0C10;
        line-height: 1.6;
    }
    
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Space Grotesk', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    
    h1, h2 {
        font-size: 2.5rem;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
}

a { text-decoration: none; color: inherit; transition: 0.3s; }
ul { list-style: none; }
img, video { display: block; max-width: 100%; height: auto; }
/* --- LAYOUT UTILITIES --- */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 100px 20px 60px;
    width: 100%;
}

.section-padding {
    padding: 100px 0;
}

/* --- TYPOGRAPHY UTILITIES --- */
.text-highlight {
    background: linear-gradient(90deg, #00bcd4, #007bff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

.section-label {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #00bcd4;
    margin-bottom: 12px;
    display: block;
    opacity: 0.9;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 25px;
    line-height: 1.2;
}

.section-desc {
    color: #a0a0a0;
    font-size: 1.05rem;
    margin-bottom: 20px;
    font-weight: 300;
}

/* --- BUTTONS --- */
.btn {
    display: inline-block;
    padding: 14px 32px;
    background: linear-gradient(135deg, #00bcd4, #007bff);
    color: #fff;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.95rem;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
}

/* --- MESSAGE BOX --- */
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
}

.alert-success {
    background: rgba(0, 188, 212, 0.1);
    border: 1px solid #00bcd4;
    color: #00bcd4;
}

.alert-error {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid #dc3545;
    color: #dc3545;
}

/* --- PROFILE LAYOUT --- */
.profile-content {
    display: flex;
    gap: 30px;
}

.profile-nav {
    flex: 0 0 250px; 
    background: #161616;
    padding: 20px 0;
    border-radius: 20px;
    border: 1px solid #252525;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
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
    color: #a0a0a0;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: 0.3s;
    font-size: 0.95rem;
}

.profile-nav a:hover {
    background: #1a1a1a;
    color: #00bcd4;
}

.profile-nav a.active {
    background: rgba(0, 188, 212, 0.1); 
    border-left: 3px solid #00bcd4;
    color: #00bcd4;
    font-weight: 600;
}

.profile-nav a[data-tab="logout"] {
    color: #dc3545;
}

.profile-nav a[data-tab="logout"]:hover {
    color: #ff4757;
    background: rgba(220, 53, 69, 0.1);
}

.profile-nav a[data-tab="logout"].active {
    background: rgba(220, 53, 69, 0.1);
    border-left: 3px solid #dc3545;
    color: #ff4757;
}

/* --- TAB CONTENT --- */
.profile-tabs {
    flex-grow: 1;
    background: #161616;
    padding: 40px;
    border-radius: 20px;
    border: 1px solid #252525;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.tab-pane h3 {
    color: #fff;
    font-size: 1.75rem;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #252525;
}

/* --- FORM STYLES --- */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #fff;
    margin-bottom: 8px;
    font-size: 0.9rem;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 14px 18px;
    border-radius: 12px;
    border: 1px solid #252525;
    background: #1a1a1a;
    color: #fff;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #00bcd4;
    box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1);
}

.form-group input[readonly] {
    background: #141414;
    color: #888;
    cursor: not-allowed;
}

/* --- ORDERS TABLE --- */
.orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: 12px;
    overflow: hidden;
}

.orders-table th, .orders-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #252525;
    text-align: left;
}

.orders-table th { 
    background: #1a1a1a; 
    color: #00bcd4; 
    font-weight: 600;
    font-size: 0.9rem;
}

.orders-table tr:hover { 
    background: #1a1a1a; 
}

.orders-table td {
    color: #e0e0e0;
}

/* --- STATUS CLASSES --- */
.status-pending { color: #ff9800; font-weight: 600; }
.status-processing { color: #00bcd4; font-weight: 600; }
.status-completed { color: #4caf50; font-weight: 600; }
.status-cancelled { color: #f44336; font-weight: 600; }

/* --- FOOTER --- */
footer {
    background: #080808;
    padding: 70px 0 30px;
    border-top: 1px solid #1a1a1a;
    margin-top: 60px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 40px;
    margin-bottom: 50px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
}

.footer-col h4 {
    color: #fff;
    font-size: 1.1rem;
    margin-bottom: 20px;
    position: relative;
    display: inline-block;
}

.footer-col h4::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -8px;
    width: 30px;
    height: 2px;
    background: #00bcd4;
}

.footer-col p { color: #888; font-size: 0.9rem; margin-bottom: 10px; }
.footer-col a { color: #888; }
.footer-col a:hover { color: #00bcd4; padding-left: 5px; }

.social-links { display: flex; gap: 15px; margin-top: 15px; }
.social-links a { 
    width: 36px; 
    height: 36px; 
    background: #1a1a1a; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    border-radius: 50%;
    color: #fff;
    transition: 0.3s;
}
.social-links a:hover { background: #00bcd4; transform: translateY(-3px); }

.copyright {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid #1a1a1a;
    color: #555;
    font-size: 0.85rem;
    max-width: 1200px;
    margin: 0 auto;
    padding-left: 20px;
    padding-right: 20px;
}

/* --- RESPONSIVE --- */
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
        background: #161616;
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
    .profile-tabs {
        padding: 30px 20px;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 80px 20px 40px;
    }
    .section-title {
        font-size: 2rem;
    }
    .tab-pane h3 {
        font-size: 1.5rem;
    }
}
</style>
</head>
<body>
<?php
// Ensure $currentUser is available for navbar
$currentPage = 'profile';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div style="margin-bottom: 40px;">
        <span class="section-label">My Account</span>
        <h2 class="section-title" style="margin-bottom: 10px;">Account <span class="text-highlight">Settings</span></h2>
    </div>

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
                <li><a href="#logout" class="tab-link" data-tab="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? $user['mobile'] ?? '') ?>" placeholder="Enter your phone number">
                    </div>
                    <?php if (!empty($user['mobile']) && $user['mobile'] !== ($user['phone'] ?? '')): ?>
                    <div class="form-group">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" value="<?= htmlspecialchars($user['mobile'] ?? '') ?>" placeholder="Enter your mobile number">
                    </div>
                    <?php endif; ?>
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

            <div id="logout" class="tab-pane">
                <h3>Logout</h3>
                <div style="max-width: 500px;">
                    <p class="section-desc" style="margin-bottom: 30px;">
                        Are you sure you want to logout? You'll need to sign in again to access your account.
                    </p>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="../logout.php" class="btn" style="background: linear-gradient(135deg, #dc3545, #c82333); box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);">
                            <i class="fas fa-sign-out-alt"></i> Yes, Logout
                        </a>
                        <button type="button" class="btn" onclick="history.back()" style="background: #252525; box-shadow: none;">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

        </div> </div> </div>

<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4>About Bottle</h4>
            <p>We craft personalized premium water bottles for restaurants & events across India. Quality meets elegance.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <p><a href="../category.php">Shop Now</a></p>
            <p><a href="../about.php">About Us</a></p>
            <p><a href="../contact.php">Contact</a></p>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p>support@bottle.com</p>
            <p>+91 98765 43210</p>
            <p>Indore, India</p>
        </div>
        <div class="footer-col">
            <h4>Follow Us</h4>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; <?= date('Y'); ?> Bottle. All rights reserved.</p>
    </div>
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
<script src="../assets/js/navbar.js" defer></script>
<script src="../assets/js/app.js" defer></script>
</body>
</html>