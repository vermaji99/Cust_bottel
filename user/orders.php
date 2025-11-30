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

// Check which schema the orders table uses
try {
    $checkColumns = db()->query("SHOW COLUMNS FROM orders");
    $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
    $hasOrderNumber = in_array('order_number', $columns);
    $hasTotalAmount = in_array('total_amount', $columns);
    $hasSubtotal = in_array('subtotal', $columns);
    $hasDiscountTotal = in_array('discount_total', $columns);
} catch (Exception $e) {
    $hasOrderNumber = false;
    $hasTotalAmount = false;
    $hasSubtotal = false;
    $hasDiscountTotal = false;
}

// Get orders without joining designs table to avoid column errors
$orderStmt = db()->prepare('
    SELECT o.*
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
');
$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize missing fields for all orders based on schema
foreach ($orders as &$order) {
    // Order number
    if (!$hasOrderNumber && !isset($order['order_number'])) {
        $order['order_number'] = 'ORD-' . $order['id'];
    }
    
    // Total amount - use total_amount if column exists, otherwise fallback to total
    if ($hasTotalAmount) {
        // Column exists - use value from database (even if 0, it's a valid value)
        $order['total_amount'] = $order['total_amount'] ?? 0;
    } else {
        // Column doesn't exist - use old 'total' column
        $order['total_amount'] = $order['total'] ?? 0;
    }
    
    // Subtotal - only initialize if column doesn't exist
    if ($hasSubtotal) {
        // Column exists - use value from database (even if 0)
        $order['subtotal'] = $order['subtotal'] ?? 0;
    } else {
        // Column doesn't exist - initialize to 0
        $order['subtotal'] = 0;
    }
    
    // Discount total - only initialize if column doesn't exist
    if ($hasDiscountTotal) {
        // Column exists - use value from database (even if 0, it's a valid value)
        $order['discount_total'] = $order['discount_total'] ?? 0;
    } else {
        // Column doesn't exist - initialize to 0
        $order['discount_total'] = 0;
    }
    
    // Design fields (will be empty if not available)
    $order['thumbnail_path'] = null;
    $order['design_key'] = null;
}
unset($order);

$timelines = [];
$orderItems = [];

if ($orders) {
    $ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Fetch timelines
    $timelineStmt = db()->prepare("SELECT * FROM order_status_history WHERE order_id IN ($placeholders) ORDER BY created_at ASC");
    $timelineStmt->execute($ids);
    foreach ($timelineStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $timelines[$row['order_id']][] = $row;
    }
    
    // Fetch order items with product details
    try {
        // Check which price column exists
        $checkItemColumns = db()->query("SHOW COLUMNS FROM order_items");
        $itemColumns = $checkItemColumns->fetchAll(PDO::FETCH_COLUMN);
        $hasUnitPrice = in_array('unit_price', $itemColumns);
        $priceColumn = $hasUnitPrice ? 'unit_price' : 'price';
        
        $itemsStmt = db()->prepare("
            SELECT oi.order_id, oi.product_id, oi.quantity, oi.{$priceColumn} as price,
                   p.name as product_name, p.image as product_image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id IN ($placeholders)
        ");
        $itemsStmt->execute($ids);
        foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $orderItems[$item['order_id']][] = $item;
        }
    } catch (Exception $e) {
        error_log('Error fetching order items: ' . $e->getMessage());
    }
}

function statusClass($status) {
    return match (strtolower($status)) {
        'pending' => 'status-pending',
        'processing' => 'status-processing',
        'printed' => 'status-printed',
        'shipped' => 'status-shipped',
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
<title>Bottle | My Orders</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/navbar.css">
<link rel="stylesheet" href="../assets/css/responsive.css">
<meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
<style>
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
    padding-top: 0;
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
.icon {
    font-family: 'Material Symbols Outlined';
    font-weight: normal;
    font-style: normal;
    font-size: 24px;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-block;
    white-space: nowrap;
    word-wrap: normal;
    direction: ltr;
    -webkit-font-feature-settings: 'liga';
    -webkit-font-smoothing: antialiased;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 100px 20px 60px;
    width: 100%;
}

h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 40px;
    text-align: center;
}

h2 .text-highlight {
    background: linear-gradient(90deg, #00bcd4, #007bff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

.order-card {
    background: #161616;
    padding: 30px;
    border-radius: 20px;
    margin-bottom: 25px;
    border: 1px solid rgba(0, 188, 212, 0.2);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 188, 212, 0.1);
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6), 0 0 25px rgba(0, 188, 212, 0.2);
    border-color: rgba(0, 188, 212, 0.4);
}
.order-header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    border-bottom: 1px solid rgba(0, 188, 212, 0.15);
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.order-header strong {
    color: #fff;
    font-size: 1.3rem;
    font-weight: 600;
}

.order-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    color: #a0a0a0;
    align-items: center;
}
/* Order Items Preview */
.order-items-preview {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    margin: 15px 0;
    padding: 10px;
    background: #1a1a1a;
    border-radius: 8px;
}
.order-items-preview-left {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    flex: 1;
}
.order-item-preview {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(0, 188, 212, 0.1);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    border: 1px solid rgba(0, 188, 212, 0.2);
}
.order-item-preview img {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #333;
}
.order-item-preview span {
    color: #ccc;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Flipkart-style Timeline */
.timeline-container {
    display: flex;
    margin-top: 20px;
    min-height: 200px;
}
.timeline-left {
    width: 200px;
    flex-shrink: 0;
    position: relative;
    padding-right: 30px;
}
.timeline-line {
    position: absolute;
    left: 15px;
    top: 10px;
    bottom: 10px;
    width: 3px;
    background: #2a2a2a;
}
.timeline-checkpoints {
    position: relative;
    z-index: 2;
}
.timeline-checkpoint {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 35px;
    position: relative;
}
.timeline-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #2a2a2a;
    border: 3px solid #1a1a1a;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: #666;
    position: relative;
    z-index: 3;
}
.timeline-dot.active {
    background: #ffc107;
    border-color: #ffc107;
    color: #fff;
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
}
.timeline-dot.completed {
    background: #4caf50;
    border-color: #4caf50;
    color: #fff;
}
.timeline-dot.completed::after {
    content: '✓';
    font-size: 1rem;
    font-weight: bold;
}
.timeline-label {
    flex: 1;
    padding-top: 4px;
}
.timeline-label strong {
    color: #fff;
    font-size: 0.9rem;
    display: block;
    margin-bottom: 3px;
}
.timeline-label small {
    color: #888;
    font-size: 0.75rem;
}
.timeline-checkpoint.active .timeline-label strong {
    color: #ffc107;
}
.timeline-checkpoint.completed .timeline-label strong {
    color: #4caf50;
}
.timeline-content {
    flex: 1;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 12px;
    padding: 15px;
    margin-left: 20px;
    border: 1px solid rgba(0, 188, 212, 0.1);
}

/* Order Details Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: #161616;
    margin: 3% auto;
    padding: 40px;
    border-radius: 20px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid rgba(0, 188, 212, 0.2);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7), 0 0 20px rgba(0, 188, 212, 0.15);
    animation: slideUp 0.3s ease;
    /* Hide scrollbar but keep scrolling functionality */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.modal-content::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(0, 188, 212, 0.15);
}

.modal-header h3 {
    background: linear-gradient(90deg, #00bcd4, #007bff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
    font-size: 1.6rem;
    font-weight: 600;
}

.close-modal {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(0, 188, 212, 0.2);
    padding: 8px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.close-modal:hover {
    color: #fff;
    background: rgba(0, 188, 212, 0.2);
    border-color: rgba(0, 188, 212, 0.4);
    transform: rotate(90deg);
}

.modal-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 18px;
    margin: 25px 0;
}

.modal-item {
    display: flex;
    gap: 12px;
    background: rgba(0, 0, 0, 0.3);
    padding: 15px;
    border-radius: 12px;
    border: 1px solid rgba(0, 188, 212, 0.1);
    transition: all 0.3s ease;
}

.modal-item:hover {
    background: rgba(0, 188, 212, 0.1);
    border-color: rgba(0, 188, 212, 0.3);
    transform: translateY(-2px);
}

.modal-item img {
    width: 65px;
    height: 65px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid rgba(0, 188, 212, 0.2);
}

.modal-item-info {
    flex: 1;
}

.modal-item-info h4 {
    margin: 0 0 8px 0;
    font-size: 0.95rem;
    color: #fff;
    font-weight: 600;
}

.modal-item-info p {
    margin: 4px 0;
    font-size: 0.85rem;
    color: #a0a0a0;
}

.modal-item-info p:last-child {
    color: #00bcd4;
    font-weight: 600;
    font-size: 0.9rem;
}

.design-preview img { width:100px;border-radius:8px; }
.status-pending { color: #ff9800; font-weight: 600; }
.status-processing { color: #ffc107; font-weight: 600; }
.status-printed { color: #9c27b0; font-weight: 600; }
.status-shipped { color: #00bcd4; font-weight: 600; }
.status-completed { color: #4caf50; font-weight: 600; }
.status-cancelled { color: #f44336; font-weight: 600; }

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
    text-decoration: none;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
}

.btn-view-details {
    background: linear-gradient(135deg, #00bcd4, #007bff);
    padding: 12px 24px;
    border-radius: 50px;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.3);
}

.btn-view-details:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 188, 212, 0.5);
}

.btn-cancel {
    background: linear-gradient(135deg, #f44336, #d32f2f);
    padding: 12px 24px;
    border-radius: 50px;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
}

.btn-cancel:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(244, 67, 54, 0.5);
}

.btn-invoice {
    background: linear-gradient(135deg, #4caf50, #388e3c);
    padding: 12px 24px;
    border-radius: 50px;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.btn-invoice:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
}
/* --- FOOTER --- */
footer {
    background: #080808;
    padding: clamp(3rem, 8vw, 4.5rem) 0 clamp(1.5rem, 4vw, 2rem);
    border-top: 1px solid #1a1a1a;
    margin-top: clamp(3rem, 6vw, 4rem);
}

.footer-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: clamp(2rem, 5vw, 2.5rem);
    margin-bottom: clamp(2.5rem, 6vw, 3rem);
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 clamp(1rem, 4vw, 1.25rem);
}

@media (min-width: 480px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    .footer-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: clamp(2rem, 4vw, 2.5rem);
    }
}

.footer-col h4 {
    color: #fff;
    font-size: clamp(1rem, 2.5vw, 1.1rem);
    margin-bottom: clamp(1rem, 2.5vw, 1.25rem);
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

.footer-col p {
    color: #888;
    font-size: clamp(0.85rem, 2vw, 0.9rem);
    margin-bottom: clamp(0.5rem, 1.5vw, 0.75rem);
    line-height: 1.6;
}

.footer-col a {
    color: #888;
    text-decoration: none;
    transition: all 0.3s ease;
}

.footer-col a:hover {
    color: #00bcd4;
    padding-left: 5px;
}

.social-links {
    display: flex;
    gap: clamp(0.75rem, 2vw, 1rem);
    margin-top: clamp(0.75rem, 2vw, 1rem);
}

.social-links a {
    width: clamp(32px, 6vw, 36px);
    height: clamp(32px, 6vw, 36px);
    background: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    transition: all 0.3s ease;
    font-size: clamp(0.9rem, 2vw, 1rem);
}

.social-links a:hover {
    background: #00bcd4;
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3);
}

.copyright {
    text-align: center;
    padding-top: clamp(1.5rem, 4vw, 2rem);
    border-top: 1px solid #1a1a1a;
    color: #555;
    font-size: clamp(0.8rem, 2vw, 0.85rem);
    max-width: 1200px;
    margin: 0 auto;
    padding-left: clamp(1rem, 4vw, 1.25rem);
    padding-right: clamp(1rem, 4vw, 1.25rem);
}

/* Modal grid responsive - only for very small screens */
@media (max-width: 480px) {
    .modal-order-info {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
        padding: 15px !important;
    }
    
    .container {
        padding: 80px 15px 40px;
    }
    
    h2 {
        font-size: 2rem;
        margin-bottom: 30px;
    }
    
    .order-card {
        padding: 20px;
    }
    
    .order-header strong {
        font-size: 1.1rem;
    }
    
    .modal-content {
        padding: 20px;
        width: 95%;
    }
}
</style>
</head>
<body>
<?php
$currentPage = 'orders';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <h2>My <span class="text-highlight">Orders History</span></h2>

    <?php if ($messages): ?>
        <div style="background:rgba(76, 175, 80, 0.2);padding:15px;border-radius:12px;color:#4caf50;margin-bottom:25px;border:1px solid rgba(76, 175, 80, 0.3);"><?= esc(implode(' ', $messages)); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="background:rgba(244, 67, 54, 0.2);padding:15px;border-radius:12px;color:#f44336;margin-bottom:25px;border:1px solid rgba(244, 67, 54, 0.3);"><?= esc(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <?php if ($orders): ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $orderId = $order['id'];
            $items = $orderItems[$orderId] ?? [];
            $orderTimeline = $timelines[$orderId] ?? [];
            $currentStatus = strtolower($order['status'] ?? 'pending');
            
            // CRITICAL: Use EXACT same logic as admin orders.php to ensure consistency
            // Admin uses: $order['total_amount'] ?? $order['total'] ?? 0
            // This ensures user sees the SAME total as admin sees
            
            // Calculate total amount - same logic as admin (line 194-234 in admin/orders.php)
            $calculatedTotal = 0;
            
            // First check database columns (handle both NULL and 0)
            $dbTotalAmount = isset($order['total_amount']) ? (float)$order['total_amount'] : 0;
            $dbTotal = isset($order['total']) ? (float)$order['total'] : 0;
            
            if ($dbTotalAmount > 0) {
                $calculatedTotal = $dbTotalAmount;
            } elseif ($dbTotal > 0) {
                $calculatedTotal = $dbTotal;
            } else {
                // If not in order record, calculate from order_items
                $calculatedSubtotalFromItems = 0;
                foreach ($items as $item) {
                    $calculatedSubtotalFromItems += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
                }
                $calculatedTotal = round($calculatedSubtotalFromItems, 2);
                
                // If we have discount_total, subtract it
                if (isset($order['discount_total']) && (float)$order['discount_total'] > 0) {
                    $calculatedTotal = max(0, $calculatedTotal - (float)$order['discount_total']);
                }
                
                // If still 0, try to get subtotal and discount from order
                if ($calculatedTotal == 0 && isset($order['subtotal']) && (float)$order['subtotal'] > 0) {
                    $calculatedTotal = (float)$order['subtotal'];
                    if (isset($order['discount_total']) && (float)$order['discount_total'] > 0) {
                        $calculatedTotal = max(0, $calculatedTotal - (float)$order['discount_total']);
                    }
                }
            }
            
            // Use the calculated total (same as admin)
            $orderTotal = round($calculatedTotal, 2);
            
            // Calculate subtotal from items if order subtotal is 0
            $subtotal = (float)($order['subtotal'] ?? 0);
            if ($subtotal <= 0 && !empty($items)) {
                // Calculate from items
                $calculatedSubtotalFromItems = 0;
                foreach ($items as $item) {
                    $itemPrice = (float)($item['price'] ?? 0);
                    $itemQty = (int)($item['quantity'] ?? 1);
                    $calculatedSubtotalFromItems += $itemPrice * $itemQty;
                }
                if ($calculatedSubtotalFromItems > 0) {
                    $subtotal = round($calculatedSubtotalFromItems, 2);
                }
            }
            
            $discount = (float)($order['discount_total'] ?? 0);
            $couponCode = $order['coupon_code'] ?? null;
            
            // Round for display consistency
            $subtotal = round($subtotal, 2);
            $discount = round($discount, 2);
            
            // Define status flow for timeline
            $statusFlow = [
                'pending' => ['icon' => '📦', 'label' => 'Order Placed', 'color' => '#ff9800'],
                'processing' => ['icon' => '⚙️', 'label' => 'Processing', 'color' => '#ffc107'],
                'printed' => ['icon' => '🖨️', 'label' => 'Printed', 'color' => '#9c27b0'],
                'shipped' => ['icon' => '🚚', 'label' => 'Shipped', 'color' => '#00bcd4'],
                'delivered' => ['icon' => '✅', 'label' => 'Delivered', 'color' => '#4caf50'],
                'cancelled' => ['icon' => '❌', 'label' => 'Cancelled', 'color' => '#f44336'],
            ];
            
            // Determine which statuses to show
            $statusesToShow = [];
            if ($currentStatus === 'cancelled') {
                $statusesToShow = ['pending', 'cancelled'];
            } else {
                $statusOrder = ['pending', 'processing', 'printed', 'shipped', 'delivered'];
                foreach ($statusOrder as $status) {
                    $statusesToShow[] = $status;
                    if ($status === $currentStatus) break;
                }
            }
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <strong>Order #<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])); ?></strong>
                        <div style="color:#aaa;font-size:0.9rem;"><?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="order-meta">
                        <?php 
                        // Use the same total as admin sees - $orderTotal is calculated using same logic as admin
                        // Admin displays: $order['total_amount'] ?? $order['total'] ?? 0
                        // We use: $orderTotal (calculated above using same logic)
                        $hasCouponOrDiscount = !empty($couponCode) || $discount > 0;
                        
                        // Final amount - same as admin sees (already includes discount)
                        $finalAmountAfterDiscount = $orderTotal;
                        
                        if ($hasCouponOrDiscount && $subtotal > 0): 
                        ?>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="text-decoration: line-through; color: #888; font-size: 0.9rem;">₹<?= number_format($subtotal, 2); ?></span>
                                    <span style="color: #4caf50; font-weight: 700; font-size: 1.1rem;">₹<?= number_format($finalAmountAfterDiscount, 2); ?></span>
                                </div>
                                <?php if ($couponCode): ?>
                                    <span style="color: #00bcd4; font-size: 0.8rem; font-weight: 500;">Coupon Applied: <?= esc($couponCode); ?> (-₹<?= number_format($discount, 2); ?>)</span>
                                <?php elseif ($discount > 0): ?>
                                    <span style="color: #4caf50; font-size: 0.8rem; font-weight: 500;">Discount: -₹<?= number_format($discount, 2); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span style="font-weight: 600; font-size: 1rem; color: #4caf50;">Total: ₹<?= number_format($finalAmountAfterDiscount, 2); ?></span>
                        <?php endif; ?>
                        <span class="<?= statusClass($order['status']); ?>"><?= ucfirst($order['status']); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($items)): ?>
                    <div class="order-items-preview">
                        <div class="order-items-preview-left">
                        <?php foreach (array_slice($items, 0, 4) as $item): ?>
                            <div class="order-item-preview">
                                <?php if (!empty($item['product_image'])): ?>
                                    <img src="../admin/uploads/<?= esc($item['product_image']); ?>" alt="<?= esc($item['product_name']); ?>">
                                <?php else: ?>
                                    <img src="../assets/images/placeholder.png" alt="No image" style="background:#2a2a2a;">
                                <?php endif; ?>
                                <span><?= esc($item['product_name'] ?? 'Unknown Product'); ?></span>
                                <span style="color:#888;">×<?= (int)($item['quantity'] ?? 1); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($items) > 4): ?>
                                <div class="order-item-preview view-more-items" style="color:#00bcd4;cursor:pointer;" data-order-id="<?= $orderId; ?>">
                                +<?= count($items) - 4; ?> more
                            </div>
                        <?php endif; ?>
                    </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if ($order['status'] === 'pending'): ?>
                                <form method="POST" style="margin:0;">
                                    <?= csrf_field(); ?>
                                    <button type="submit" name="cancel_order" value="<?= $order['id']; ?>" class="btn-cancel">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (strtolower($order['status']) === 'delivered'): ?>
                                <a href="invoice.php?order_id=<?= $order['id']; ?>" class="btn-invoice" target="_blank">
                                    <i class="fas fa-file-invoice"></i> Invoice
                                </a>
                <?php endif; ?>
                            <button onclick="openOrderDetails(<?= $orderId; ?>)" class="btn-view-details">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display:flex;justify-content:flex-end;align-items:center;margin-top:15px;gap:10px;">
                    <?php if ($order['status'] === 'pending'): ?>
                        <form method="POST" style="margin:0;">
                            <?= csrf_field(); ?>
                                <button type="submit" name="cancel_order" value="<?= $order['id']; ?>" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </form>
                    <?php endif; ?>
                        <?php if (strtolower($order['status']) === 'delivered'): ?>
                            <a href="invoice.php?order_id=<?= $order['id']; ?>" class="btn-invoice" target="_blank">
                                <i class="fas fa-file-invoice"></i> Invoice
                            </a>
                        <?php endif; ?>
                        <button onclick="openOrderDetails(<?= $orderId; ?>)" class="btn-view-details">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Details Modal -->
            <div id="modal-<?= $orderId; ?>" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Order #<?= esc($order['order_number'] ?? ('ORD-' . $order['id'])); ?></h3>
                        <button class="close-modal" onclick="closeOrderDetails(<?= $orderId; ?>)">&times;</button>
                    </div>
                    
                    <div class="modal-order-info" style="display:grid;grid-template-columns:1fr 1fr;gap:25px;margin-bottom:30px;padding:20px;background:rgba(0,0,0,0.2);border-radius:12px;border:1px solid rgba(0,188,212,0.1);">
                        <div>
                            <strong style="color:#a0a0a0;font-size:0.9rem;text-transform:uppercase;letter-spacing:1px;">Order Date</strong>
                            <p style="margin:8px 0 0 0;color:#fff;font-size:1rem;font-weight:500;"><?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div>
                            <strong style="color:#a0a0a0;font-size:0.9rem;text-transform:uppercase;letter-spacing:1px;">Total Amount</strong>
                            <?php 
                            $hasCouponOrDiscount = !empty($couponCode) || $discount > 0;
                            // Final amount - same as admin sees (already includes discount)
                            $finalAmountAfterDiscount = $orderTotal;
                            
                            if ($hasCouponOrDiscount && $subtotal > 0): 
                            ?>
                                <p style="margin:5px 0 2px 0;color:#888;font-size:0.95rem;text-decoration:line-through;">₹<?= number_format($subtotal, 2); ?></p>
                                <?php if ($couponCode): ?>
                                    <p style="margin:2px 0 5px 0;color:#4caf50;font-size:0.9rem;font-weight:500;">Coupon (<?= esc($couponCode); ?>): -₹<?= number_format($discount, 2); ?></p>
                                <?php elseif ($discount > 0): ?>
                                    <p style="margin:2px 0 5px 0;color:#4caf50;font-size:0.9rem;font-weight:500;">Discount: -₹<?= number_format($discount, 2); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                            <p style="margin:8px 0 0 0;background:linear-gradient(90deg, #4caf50, #388e3c);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:1.4rem;font-weight:700;">₹<?= number_format($finalAmountAfterDiscount, 2); ?></p>
                        </div>
                    </div>
                    
                    <h4 style="color:#00bcd4;margin:30px 0 20px 0;font-size:1.3rem;font-weight:600;padding-bottom:12px;border-bottom:1px solid rgba(0,188,212,0.15);">Order Items</h4>
                    <div class="modal-items-grid">
                        <?php 
                        $calculatedSubtotal = 0;
                        foreach ($items as $item): 
                            // Price is already aliased as 'price' in the query
                            $unitPrice = (float)($item['price'] ?? 0);
                            $quantity = (int)($item['quantity'] ?? 1);
                            $itemTotal = $unitPrice * $quantity;
                            $calculatedSubtotal += $itemTotal;
                        ?>
                            <div class="modal-item">
                                <?php if (!empty($item['product_image'])): ?>
                                    <img src="../admin/uploads/<?= esc($item['product_image']); ?>" alt="<?= esc($item['product_name']); ?>">
                                <?php else: ?>
                                    <img src="../assets/images/placeholder.png" alt="No image" style="background:#2a2a2a;">
                                <?php endif; ?>
                                <div class="modal-item-info">
                                    <h4><?= esc($item['product_name'] ?? 'Unknown Product'); ?></h4>
                                    <p>Qty: <?= $quantity; ?> × ₹<?= number_format($unitPrice, 2); ?></p>
                                    <p style="color:#00bcd4;font-weight:600;">₹<?= number_format($itemTotal, 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Price Summary -->
                    <?php 
                    // Use calculated subtotal from items if available
                    $displaySubtotal = $subtotal;
                    if ($displaySubtotal <= 0 && !empty($items)) {
                        $calculatedSubtotal = 0;
                        foreach ($items as $item) {
                            $itemPrice = (float)($item['price'] ?? 0);
                            $itemQty = (int)($item['quantity'] ?? 1);
                            $calculatedSubtotal += $itemPrice * $itemQty;
                        }
                        if ($calculatedSubtotal > 0) {
                            $displaySubtotal = round($calculatedSubtotal, 2);
                        }
                    }
                    
                    // Final amount - same as admin sees (already includes discount)
                    $hasCouponOrDiscountSummary = !empty($couponCode) || $discount > 0;
                    $finalAmountAfterDiscountSummary = $orderTotal;
                    ?>
                    <div style="margin-top:30px;padding:25px;background:rgba(0,0,0,0.3);border-radius:12px;border:1px solid rgba(0,188,212,0.15);">
                        <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:#a0a0a0;font-size:1rem;font-weight:500;">
                            <span>Subtotal:</span>
                            <span style="color:#fff;">₹<?= number_format($displaySubtotal, 2); ?></span>
                        </div>
                        <?php if ($hasCouponOrDiscountSummary && $discount > 0): ?>
                            <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:#4caf50;font-size:1rem;font-weight:500;">
                                <span>Discount<?= $couponCode ? ' (' . esc($couponCode) . ')' : ''; ?>:</span>
                                <span>-₹<?= number_format($discount, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex;justify-content:space-between;margin-top:18px;padding-top:18px;border-top:2px solid rgba(0,188,212,0.3);">
                            <span style="background:linear-gradient(90deg, #4caf50, #388e3c);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;font-size:1.3rem;">Total Amount (After Discount):</span>
                            <span style="background:linear-gradient(90deg, #4caf50, #388e3c);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;font-size:1.3rem;">₹<?= number_format($finalAmountAfterDiscountSummary, 2); ?></span>
                        </div>
                    </div>
                    
                    <h4 style="color:#00bcd4;margin:35px 0 20px 0;font-size:1.3rem;font-weight:600;padding-bottom:12px;border-bottom:1px solid rgba(0,188,212,0.15);">Order Timeline</h4>
                    <div class="timeline-container">
                        <div class="timeline-left">
                            <div class="timeline-line"></div>
                            <div class="timeline-checkpoints">
                                <?php
                                // Get current status index in flow
                                $currentStatusIndex = array_search($currentStatus, $statusesToShow);
                                if ($currentStatusIndex === false) $currentStatusIndex = 0;
                                
                                foreach ($statusesToShow as $idx => $status) {
                                    $statusInfo = $statusFlow[$status] ?? ['icon' => '●', 'label' => ucfirst($status), 'color' => '#666'];
                                    $isCompleted = $idx < $currentStatusIndex;
                                    $isActive = ($status === $currentStatus) || ($idx === $currentStatusIndex);
                                    
                                    // Find matching timeline step from admin updates
                                    $timelineStep = null;
                                    foreach ($orderTimeline as $step) {
                                        if (strtolower($step['status']) === $status) {
                                            $timelineStep = $step;
                                            break;
                                        }
                                    }
                                    
                                    // Show all statuses up to current status
                                    if ($idx > $currentStatusIndex) {
                                        continue;
                                    }
                                ?>
                                    <div class="timeline-checkpoint <?= ($isCompleted || $isActive) ? 'completed' : ''; ?> <?= $isActive ? 'active' : ''; ?>">
                                        <div class="timeline-dot <?= ($isCompleted || $isActive) ? 'completed' : ''; ?> <?= $isActive ? 'active' : ''; ?>">
                                            <?= ($isCompleted || $isActive) ? '✓' : $statusInfo['icon']; ?>
                                        </div>
                                        <div class="timeline-label">
                                            <strong><?= $statusInfo['label']; ?></strong>
                                            <?php if ($timelineStep): ?>
                                                <small><?= date('d M Y, h:i A', strtotime($timelineStep['created_at'])); ?></small>
                                                <?php if (!empty($timelineStep['note'])): ?>
                                                    <small style="display:block;color:#666;margin-top:3px;"><?= esc($timelineStep['note']); ?></small>
                                                <?php endif; ?>
                                            <?php elseif ($status === 'pending'): ?>
                                                <small><?= date('d M Y, h:i A', strtotime($order['created_at'])); ?></small>
                                            <?php else: ?>
                                                <small style="color:#666;">Updated</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="timeline-content">
                            <?php if (!empty($orderTimeline)): ?>
                                <p style="color:#888;margin:0;">Track your order status here. Timeline updates as your order progresses.</p>
                            <?php else: ?>
                                <p style="color:#888;margin:0;">Your order has been placed. Timeline will update as processing begins.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 60px 40px; background:#161616; border-radius:20px; border: 1px solid rgba(0, 188, 212, 0.2);">
            <i class="fas fa-box-open" style="font-size: 3rem; color: #00bcd4; margin-bottom: 20px;"></i>
            <p style="color:#a0a0a0; margin-top:15px; font-size: 1.1rem;">You haven't placed any orders yet.</p>
            <a href="../category.php" class="btn" style="margin-top: 25px;">Start Shopping Now</a>
        </div>
    <?php endif; ?>
</div>

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
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; <?= date('Y'); ?> Bottle. All rights reserved.</p>
    </div>
</footer>

</body>
<script src="../assets/js/navbar.js" defer></script>
<script src="../assets/js/app.js" defer></script>
<script>
function openOrderDetails(orderId) {
    document.getElementById('modal-' + orderId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeOrderDetails(orderId) {
    document.getElementById('modal-' + orderId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
});

// Handle "view more" click - open modal without triggering button click
document.querySelectorAll('.view-more-items').forEach(function(element) {
    element.addEventListener('click', function(e) {
        e.stopPropagation();
        const orderId = this.getAttribute('data-order-id');
        if (orderId) {
            openOrderDetails(parseInt(orderId));
        }
    });
});
</script>
</html>