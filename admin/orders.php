<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$statusOptions = ['pending', 'processing', 'printed', 'shipped', 'delivered', 'cancelled'];
$message = isset($_GET['status_updated']) ? 'Order status updated successfully!' : '';
$error = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired.';
    } else {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = strtolower($_POST['status'] ?? '');
        $note = trim($_POST['note'] ?? 'Status updated by admin');
        
        if (!in_array($status, $statusOptions, true)) {
            $error = 'Invalid status selected.';
        } else {
            try {
                db()->beginTransaction();
                
                // Check if updated_at column exists
                try {
                    $checkUpdated = db()->query("SHOW COLUMNS FROM orders LIKE 'updated_at'");
                    $hasUpdatedAt = $checkUpdated->rowCount() > 0;
                } catch (Exception $e) {
                    $hasUpdatedAt = false;
                }
                
                // Update order status
                if ($hasUpdatedAt) {
                    $stmt = db()->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$status, $orderId]);
                } else {
                    $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
                    $stmt->execute([$status, $orderId]);
                }
                
                // Add to status history if table exists
                try {
                    $checkHistory = db()->query("SHOW TABLES LIKE 'order_status_history'");
                    if ($checkHistory->rowCount() > 0) {
                        $historyStmt = db()->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
                        $historyStmt->execute([$orderId, $status, $note]);
                    }
                } catch (Exception $e) {
                    // Table doesn't exist, skip
                    error_log('order_status_history table not found: ' . $e->getMessage());
                }
                
                db()->commit();
                $message = 'Order status updated successfully!';
                // Redirect to prevent form resubmission
                header('Location: orders.php?status_updated=1');
                exit;
            } catch (Throwable $e) {
                db()->rollBack();
                error_log('Order update error: ' . $e->getMessage());
                $error = 'Failed to update order status.';
            }
        }
    }
}

// Check which schema columns exist
try {
    $checkOrderColumns = db()->query("SHOW COLUMNS FROM orders");
    $orderColumns = $checkOrderColumns->fetchAll(PDO::FETCH_COLUMN);
    $hasOrderNumber = in_array('order_number', $orderColumns);
    $hasShippingEmail = in_array('shipping_email', $orderColumns);
    $hasShippingName = in_array('shipping_name', $orderColumns);
    $hasTotalAmount = in_array('total_amount', $orderColumns);
    $hasDesignId = in_array('design_id', $orderColumns);
    $hasCustomDesignImage = in_array('custom_design_image', $orderColumns);
    $hasCustomDesignDescription = in_array('custom_design_description', $orderColumns);
} catch (Exception $e) {
    $hasOrderNumber = false;
    $hasShippingEmail = false;
    $hasShippingName = false;
    $hasTotalAmount = false;
    $hasDesignId = false;
    $hasCustomDesignImage = false;
    $hasCustomDesignDescription = false;
}

// Check designs table columns
try {
    $checkDesignColumns = db()->query("SHOW COLUMNS FROM designs");
    $designColumns = $checkDesignColumns->fetchAll(PDO::FETCH_COLUMN);
    $hasThumbnailPath = in_array('thumbnail_path', $designColumns);
    $designsTableExists = true;
} catch (Exception $e) {
    $hasThumbnailPath = false;
    $designsTableExists = false;
}

// Fetch orders with filters
$statusFilter = strtolower($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($statusFilter && in_array($statusFilter, $statusOptions, true)) {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}

if ($search) {
    $searchConditions = [];
    if ($hasOrderNumber) {
        $searchConditions[] = 'o.order_number LIKE ?';
        $params[] = "%{$search}%";
    }
    if ($hasShippingEmail) {
        $searchConditions[] = 'o.shipping_email LIKE ?';
        $params[] = "%{$search}%";
    }
    if ($hasShippingName) {
        $searchConditions[] = 'o.shipping_name LIKE ?';
        $params[] = "%{$search}%";
    }
    // Fallback: search in email/name columns (old schema)
    if (!$hasShippingEmail && !$hasShippingName) {
        $searchConditions[] = '(o.email LIKE ? OR o.name LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($searchConditions) {
        $where[] = '(' . implode(' OR ', $searchConditions) . ')';
    }
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = db()->prepare("SELECT COUNT(*) FROM orders o $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Build SELECT with conditional columns
$selectFields = ['o.*', 'u.name AS user_name', 'u.email AS user_email'];
if ($hasDesignId && $designsTableExists) {
    if ($hasThumbnailPath) {
        $selectFields[] = 'd.thumbnail_path';
    } else {
        $selectFields[] = 'NULL AS thumbnail_path';
    }
} else {
    $selectFields[] = 'NULL AS thumbnail_path';
}

$fromJoin = 'FROM orders o LEFT JOIN users u ON o.user_id = u.id';
if ($hasDesignId && $designsTableExists) {
    $fromJoin .= ' LEFT JOIN designs d ON d.id = o.design_id';
}

// Fetch orders
$orderSql = "
    SELECT " . implode(', ', $selectFields) . "
    $fromJoin
    $whereSql
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$orderStmt = db()->prepare($orderSql);
$orderStmt->execute($params);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Check order_items schema once (outside loop for efficiency)
try {
    $checkItemColumnsOnce = db()->query("SHOW COLUMNS FROM order_items");
    $itemColumnsOnce = $checkItemColumnsOnce->fetchAll(PDO::FETCH_COLUMN);
    $hasUnitPriceOnce = in_array('unit_price', $itemColumnsOnce);
} catch (Exception $e) {
    $hasUnitPriceOnce = false;
}

// Prepare statements for total calculation (more efficient)
if ($hasUnitPriceOnce) {
    $totalCalcStmt = db()->prepare("SELECT SUM(quantity * unit_price) as calculated_total FROM order_items WHERE order_id = ?");
} else {
    $totalCalcStmt = db()->prepare("SELECT SUM(quantity * price) as calculated_total FROM order_items WHERE order_id = ?");
}

// Initialize missing fields for display and calculate total if needed
foreach ($orders as &$order) {
    if (!isset($order['order_number'])) {
        $order['order_number'] = 'ORD-' . $order['id'];
    }
    
    // Calculate total amount - check multiple sources
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
        try {
            $totalCalcStmt->execute([$order['id']]);
            $result = $totalCalcStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['calculated_total']) && $result['calculated_total'] !== null) {
                $calculatedTotal = (float)$result['calculated_total'];
            }
            
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
        } catch (Exception $e) {
            error_log('Error calculating order total in admin orders.php: ' . $e->getMessage());
        }
    }
    
    // Store calculated total in both fields for compatibility (always set, even if 0)
    $order['total_amount'] = $calculatedTotal;
    $order['total'] = $calculatedTotal;
    
    if (!isset($order['shipping_name'])) {
        $order['shipping_name'] = $order['name'] ?? '';
    }
    if (!isset($order['shipping_email'])) {
        $order['shipping_email'] = $order['email'] ?? '';
    }
}
unset($order);

// Get order details for modal
$orderDetails = null;
if (isset($_GET['view'])) {
    $orderId = (int) $_GET['view'];
    
    // Build safe query for order details
    $detailFields = ['o.*', 'u.name AS user_name', 'u.email AS user_email'];
    $detailFrom = 'FROM orders o LEFT JOIN users u ON o.user_id = u.id';
    
    if ($hasDesignId && $designsTableExists) {
        if ($hasThumbnailPath) {
            $detailFields[] = 'd.thumbnail_path';
            $detailFields[] = 'd.file_path';
        } else {
            $detailFields[] = 'NULL AS thumbnail_path';
            $detailFields[] = 'd.filename AS file_path';
        }
        $detailFrom .= ' LEFT JOIN designs d ON d.id = o.design_id';
    } else {
        $detailFields[] = 'NULL AS thumbnail_path';
        $detailFields[] = 'NULL AS file_path';
    }
    
    $orderStmt = db()->prepare("
        SELECT " . implode(', ', $detailFields) . "
        $detailFrom
        WHERE o.id = ?
    ");
    $orderStmt->execute([$orderId]);
    $orderDetails = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    // Initialize missing fields
    if ($orderDetails) {
        if (!isset($orderDetails['order_number'])) {
            $orderDetails['order_number'] = 'ORD-' . $orderDetails['id'];
        }
        if (!isset($orderDetails['total_amount'])) {
            $orderDetails['total_amount'] = $orderDetails['total'] ?? 0;
        }
        if (!isset($orderDetails['subtotal'])) {
            $orderDetails['subtotal'] = $orderDetails['total_amount'] ?? $orderDetails['total'] ?? 0;
        }
        if (!isset($orderDetails['discount_total'])) {
            $orderDetails['discount_total'] = 0;
        }
        if (!isset($orderDetails['shipping_name'])) {
            $orderDetails['shipping_name'] = $orderDetails['name'] ?? $orderDetails['user_name'] ?? '';
        }
        if (!isset($orderDetails['shipping_email'])) {
            $orderDetails['shipping_email'] = $orderDetails['email'] ?? $orderDetails['user_email'] ?? '';
        }
        if (!isset($orderDetails['shipping_phone'])) {
            $orderDetails['shipping_phone'] = $orderDetails['phone'] ?? '';
        }
        if (!isset($orderDetails['shipping_address'])) {
            $orderDetails['shipping_address'] = $orderDetails['address'] ?? '';
        }
    }
    
    // Get order items
    if ($orderDetails) {
        // Check order_items schema for price column
        try {
            $checkItemColumns = db()->query("SHOW COLUMNS FROM order_items");
            $itemColumns = $checkItemColumns->fetchAll(PDO::FETCH_COLUMN);
            $hasUnitPrice = in_array('unit_price', $itemColumns);
        } catch (Exception $e) {
            $hasUnitPrice = false;
        }
        
        // Select price or unit_price based on schema
        $priceColumn = $hasUnitPrice ? 'unit_price' : 'price';
        $itemsStmt = db()->prepare("
            SELECT oi.*, oi.$priceColumn as price, p.name as product_name, p.image as product_image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $orderDetails['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure price field exists for all items
        foreach ($orderDetails['items'] as &$item) {
            if (!isset($item['price'])) {
                $item['price'] = $item['unit_price'] ?? $item['price'] ?? 0;
            }
        }
        unset($item);
        
        // Calculate subtotal from items if order subtotal is 0
        $calculatedSubtotal = 0;
        foreach ($orderDetails['items'] as $item) {
            $itemPrice = (float)($item['price'] ?? 0);
            $itemQty = (int)($item['quantity'] ?? 1);
            $calculatedSubtotal += $itemPrice * $itemQty;
        }
        
        // Update subtotal if it's 0 or missing
        if (($orderDetails['subtotal'] ?? 0) <= 0 && $calculatedSubtotal > 0) {
            $orderDetails['subtotal'] = round($calculatedSubtotal, 2);
        }
        
        // Calculate total amount using the SAME logic as orders list (line 194-234)
        // This ensures modal shows the exact same total as the list
        $modalCalculatedTotal = 0;
        
        // First check database columns (handle both NULL and 0) - same as list
        $dbTotalAmount = isset($orderDetails['total_amount']) ? (float)$orderDetails['total_amount'] : 0;
        $dbTotal = isset($orderDetails['total']) ? (float)$orderDetails['total'] : 0;
        
        if ($dbTotalAmount > 0) {
            $modalCalculatedTotal = $dbTotalAmount;
        } elseif ($dbTotal > 0) {
            $modalCalculatedTotal = $dbTotal;
        } else {
            // If not in order record, calculate from order_items - same as list
            $modalCalculatedTotal = round($calculatedSubtotal, 2);
            
            // If we have discount_total, subtract it - same as list
            if (isset($orderDetails['discount_total']) && (float)$orderDetails['discount_total'] > 0) {
                $modalCalculatedTotal = max(0, $modalCalculatedTotal - (float)$orderDetails['discount_total']);
            }
            
            // If still 0, try to get subtotal and discount from order - same as list
            if ($modalCalculatedTotal == 0 && isset($orderDetails['subtotal']) && (float)$orderDetails['subtotal'] > 0) {
                $modalCalculatedTotal = (float)$orderDetails['subtotal'];
                if (isset($orderDetails['discount_total']) && (float)$orderDetails['discount_total'] > 0) {
                    $modalCalculatedTotal = max(0, $modalCalculatedTotal - (float)$orderDetails['discount_total']);
                }
            }
        }
        
        // Store the calculated total (same as list does in line 233)
        $orderDetails['total_amount'] = round($modalCalculatedTotal, 2);
        $orderDetails['total'] = round($modalCalculatedTotal, 2);
        
        // Update subtotal if it was calculated from items
        if (($orderDetails['subtotal'] ?? 0) <= 0 && $calculatedSubtotal > 0) {
            $orderDetails['subtotal'] = round($calculatedSubtotal, 2);
        }
        
        // Get status history
        $historyStmt = db()->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC");
        $historyStmt->execute([$orderId]);
        $orderDetails['history'] = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Summary stats - handle both schemas
$totalAmountColumn = $hasTotalAmount ? 'total_amount' : 'total';
$statusColumn = 'status';
$summarySql = "
    SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(CASE WHEN LOWER($statusColumn) IN ('delivered', 'shipped') THEN $totalAmountColumn ELSE 0 END), 0) AS revenue,
        SUM(CASE WHEN LOWER($statusColumn) = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN LOWER($statusColumn) = 'processing' THEN 1 ELSE 0 END) AS processing_orders
    FROM orders
";
$summary = db()->query($summarySql)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <script src="assets/js/admin.js" defer></script>
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('orders') ?>
        
        <div class="admin-main">
            <?= admin_header('Manage Orders', 'View and update order status') ?>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= esc($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= esc($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-shopping-bag"></i>
                        <h3><?= number_format($summary['total_orders']) ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-rupee-sign"></i>
                        <h3>₹<?= number_format($summary['revenue'], 2) ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <h3><?= number_format($summary['pending_orders']) ?></h3>
                        <p>Pending</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-cog"></i>
                        <h3><?= number_format($summary['processing_orders']) ?></h3>
                        <p>Processing</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card">
                    <form method="GET" class="filter-bar" style="padding: 20px;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search by order number, email, or name" 
                                   value="<?= esc($search) ?>" class="form-control">
                        </div>
                        <select name="status" class="form-control" style="max-width: 200px;">
                            <option value="">All Statuses</option>
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                    <?= ucfirst($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if ($search || $statusFilter): ?>
                            <a href="orders.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Orders (<?= number_format($totalRows) ?>)</h3>
                    </div>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: #777;">
                                            <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                            No orders found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                        $statusClass = match(strtolower($order['status'] ?? 'pending')) {
                                            'delivered' => 'badge-success',
                                            'shipped' => 'badge-info',
                                            'processing' => 'badge-info',
                                            'printed' => 'badge-warning',
                                            'cancelled' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                        
                                        // Get item count
                                        $itemCountStmt = db()->prepare('SELECT SUM(quantity) FROM order_items WHERE order_id = ?');
                                        $itemCountStmt->execute([$order['id']]);
                                        $itemCount = (int) $itemCountStmt->fetchColumn();
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= esc($order['order_number'] ?? $order['id']) ?></strong>
                                            </td>
                                            <td>
                                                <?= esc($order['shipping_name'] ?? $order['user_name'] ?? 'Guest') ?><br>
                                                <small style="color: #777;"><?= esc($order['shipping_email'] ?? $order['user_email'] ?? '') ?></small>
                                            </td>
                                            <td><?= $itemCount ?> item(s)</td>
                                            <td><strong>₹<?= number_format((float)($order['total_amount'] ?? $order['total'] ?? 0), 2) ?></strong></td>
                                            <td>
                                                <span class="badge <?= $statusClass ?>">
                                                    <?= esc(ucfirst($order['status'] ?? 'Pending')) ?>
                                                </span>
                                            </td>
                                            <td><?= $order['created_at'] ? date('d M Y H:i', strtotime($order['created_at'])) : 'N/A' ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a href="?view=<?= $order['id'] ?>" class="action-btn action-btn-view" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('Update order status?')">
                                                        <?= csrf_field(); ?>
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <select name="status" onchange="this.form.submit()" 
                                                                style="padding: 4px 8px; background: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem;">
                                                            <?php foreach ($statusOptions as $status): ?>
                                                                <option value="<?= $status ?>" <?= strtolower($order['status']) === $status ? 'selected' : '' ?>>
                                                                    <?= ucfirst($status) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <a href="?page=<?= $p ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" 
                                   class="<?= $p === $page ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <?php if ($orderDetails): ?>
        <div id="orderModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div style="background: var(--bg-secondary); border-radius: 12px; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto;">
                <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="color: var(--accent); margin: 0;">Order #<?= esc($orderDetails['order_number'] ?? $orderDetails['id']) ?></h2>
                    <a href="orders.php" style="color: var(--text-secondary); font-size: 1.5rem; text-decoration: none;">&times;</a>
                </div>
                <div style="padding: 20px;">
                    <!-- Order Items -->
                    <h3 style="color: var(--accent); margin-bottom: 15px;">Order Items</h3>
                    <?php foreach ($orderDetails['items'] ?? [] as $item): ?>
                        <div style="display: flex; gap: 15px; padding: 15px; background: var(--bg-card); border-radius: 8px; margin-bottom: 10px;">
                            <?php if ($item['product_image']): ?>
                                <img src="uploads/<?= esc($item['product_image']) ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                            <?php endif; ?>
                            <div style="flex: 1;">
                                <strong><?= esc($item['product_name'] ?? 'Product') ?></strong><br>
                                <small style="color: #777;">Qty: <?= (int)($item['quantity'] ?? 0) ?> × ₹<?= number_format((float)($item['price'] ?? 0), 2) ?></small>
                            </div>
                            <div style="font-weight: 600; color: var(--accent);">₹<?= number_format((int)($item['quantity'] ?? 0) * (float)($item['price'] ?? 0), 2) ?></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Order Summary -->
                    <?php
                    // Use the EXACT same value as shown in orders list (line 512)
                    // The total_amount is already calculated using the same logic (line 194-234) and stored in $orderDetails['total_amount']
                    // So we just use it directly to ensure consistency
                    $displayTotal = (float)($orderDetails['total_amount'] ?? $orderDetails['total'] ?? 0);
                    
                    // Calculate subtotal for display
                    $displaySubtotal = (float)($orderDetails['subtotal'] ?? 0);
                    if ($displaySubtotal <= 0 && !empty($orderDetails['items'])) {
                        $calculatedSubtotal = 0;
                        foreach ($orderDetails['items'] as $item) {
                            $itemPrice = (float)($item['price'] ?? 0);
                            $itemQty = (int)($item['quantity'] ?? 1);
                            $calculatedSubtotal += $itemPrice * $itemQty;
                        }
                        if ($calculatedSubtotal > 0) {
                            $displaySubtotal = round($calculatedSubtotal, 2);
                        }
                    }
                    
                    $discount = (float)($orderDetails['discount_total'] ?? 0);
                    ?>
                    <div style="margin-top: 20px; padding: 20px; background: var(--bg-card); border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <strong>₹<?= number_format($displaySubtotal, 2) ?></strong>
                        </div>
                        <?php if ($discount > 0): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--success);">
                                <span>Discount<?= !empty($orderDetails['coupon_code']) ? ' (' . esc($orderDetails['coupon_code']) . ')' : ''; ?>:</span>
                                <strong>-₹<?= number_format($discount, 2) ?></strong>
                            </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 1px solid var(--border); font-size: 1.1rem;">
                            <strong>Total:</strong>
                            <strong style="color: var(--accent);">₹<?= number_format($displayTotal, 2) ?></strong>
                        </div>
                    </div>
                    
                    <!-- Custom Design (if uploaded) -->
                    <?php 
                    // Check if custom design data exists
                    $customDesignImage = isset($orderDetails['custom_design_image']) ? $orderDetails['custom_design_image'] : null;
                    $customDesignDescription = isset($orderDetails['custom_design_description']) ? $orderDetails['custom_design_description'] : null;
                    
                    // Normalize values - handle NULL, empty string, and string "NULL"
                    if ($customDesignImage !== null && $customDesignImage !== '') {
                        $customDesignImage = trim($customDesignImage);
                        if ($customDesignImage === '' || strtolower($customDesignImage) === 'null') {
                            $customDesignImage = null;
                        }
                    } else {
                        $customDesignImage = null;
                    }
                    
                    if ($customDesignDescription !== null && $customDesignDescription !== '') {
                        $customDesignDescription = trim($customDesignDescription);
                        if ($customDesignDescription === '' || strtolower($customDesignDescription) === 'null') {
                            $customDesignDescription = null;
                        }
                    } else {
                        $customDesignDescription = null;
                    }
                    
                    // Show section if columns exist and data is present
                    $hasImage = $hasCustomDesignImage && $customDesignImage !== null && $customDesignImage !== '';
                    $hasDescription = $hasCustomDesignDescription && $customDesignDescription !== null && $customDesignDescription !== '';
                    
                    // Debug output (remove in production)
                    // Uncomment to debug:
                    // echo "<!-- Debug: hasCustomDesignImage=" . ($hasCustomDesignImage ? 'true' : 'false') . ", customDesignImage=" . var_export($customDesignImage, true) . ", hasImage=" . ($hasImage ? 'true' : 'false') . " -->";
                    // echo "<!-- Debug: hasCustomDesignDescription=" . ($hasCustomDesignDescription ? 'true' : 'false') . ", customDesignDescription=" . var_export($customDesignDescription, true) . ", hasDescription=" . ($hasDescription ? 'true' : 'false') . " -->";
                    
                    if ($hasImage || $hasDescription): 
                    ?>
                        <h3 style="color: var(--accent); margin: 20px 0 15px;">
                            <i class="fas fa-palette"></i> Custom Design Requirements
                        </h3>
                        <div style="padding: 20px; background: var(--bg-card); border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(0, 188, 212, 0.3); box-shadow: 0 2px 8px rgba(0, 188, 212, 0.1);">
                            <?php if ($hasImage): ?>
                                <div style="margin-bottom: 20px;">
                                    <strong style="color: var(--accent); display: block; margin-bottom: 12px; font-size: 1.05rem;">
                                        <i class="fas fa-image"></i> Uploaded Design Image:
                                    </strong>
                                    <div style="text-align: center;">
                                        <img src="uploads/<?= esc($customDesignImage) ?>" alt="Custom Design" 
                                             style="max-width: 100%; max-height: 400px; border-radius: 8px; border: 2px solid rgba(0, 188, 212, 0.3); cursor: pointer; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);" 
                                             onclick="window.open('uploads/<?= esc($customDesignImage) ?>', '_blank')"
                                             title="Click to view full size">
                                        <p style="color: #888; font-size: 0.85rem; margin-top: 8px;">Click image to view full size</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($hasDescription): ?>
                                <div>
                                    <strong style="color: var(--accent); display: block; margin-bottom: 12px; font-size: 1.05rem;">
                                        <i class="fas fa-comment-alt"></i> Design Description / Requirements:
                                    </strong>
                                    <div style="color: var(--text-primary); white-space: pre-wrap; line-height: 1.8; padding: 15px; background: rgba(0, 188, 212, 0.08); border-radius: 6px; border-left: 4px solid var(--accent); font-size: 0.95rem;">
                                        <?= nl2br(esc($customDesignDescription)) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Shipping Info -->
                    <h3 style="color: var(--accent); margin: 20px 0 15px;">Shipping Information</h3>
                    <div style="padding: 15px; background: var(--bg-card); border-radius: 8px; margin-bottom: 20px;">
                        <p><strong>Name:</strong> <?= esc($orderDetails['shipping_name'] ?? '') ?></p>
                        <p><strong>Email:</strong> <?= esc($orderDetails['shipping_email'] ?? '') ?></p>
                        <p><strong>Phone:</strong> <?= esc($orderDetails['shipping_phone'] ?? '') ?></p>
                        <p><strong>Address:</strong> <?= esc($orderDetails['shipping_address'] ?? '') ?></p>
                    </div>
                    
                    <!-- Status Update -->
                    <h3 style="color: var(--accent); margin-bottom: 15px;">Update Status</h3>
                    <form method="POST" style="margin-bottom: 20px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="order_id" value="<?= $orderDetails['id'] ?>">
                        <input type="hidden" name="update_status" value="1">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <select name="status" class="form-control" required>
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= $status ?>" <?= strtolower($orderDetails['status']) === $status ? 'selected' : '' ?>>
                                        <?= ucfirst($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                        <textarea name="note" class="form-control" rows="2" placeholder="Add a note (optional)">Status updated by admin</textarea>
                    </form>
                    
                    <!-- Status History -->
                    <?php if (!empty($orderDetails['history'])): ?>
                        <h3 style="color: var(--accent); margin-bottom: 15px;">Status History</h3>
                        <div style="padding: 15px; background: var(--bg-card); border-radius: 8px;">
                            <?php foreach ($orderDetails['history'] as $history): ?>
                                <div style="padding: 10px 0; border-bottom: 1px solid var(--border);">
                                    <div style="display: flex; justify-content: space-between;">
                                        <span><strong><?= esc(ucfirst($history['status'])) ?></strong></span>
                                        <small style="color: #777;"><?= date('d M Y H:i', strtotime($history['created_at'])) ?></small>
                                    </div>
                                    <?php if ($history['note']): ?>
                                        <small style="color: #777;"><?= esc($history['note']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
