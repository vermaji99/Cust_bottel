<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$message = '';
$error = '';

// Handle Add/Update Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired.';
    } else {
        $id = (int) ($_POST['coupon_id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = $_POST['type'] ?? 'percent';
        $value = (float) ($_POST['value'] ?? 0);
        $maxDiscount = !empty($_POST['max_discount']) ? (float) $_POST['max_discount'] : null;
        $minAmount = !empty($_POST['min_amount']) ? (float) $_POST['min_amount'] : null;
        $startsAt = !empty($_POST['starts_at']) ? $_POST['starts_at'] : null;
        $endsAt = !empty($_POST['ends_at']) ? $_POST['ends_at'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!$code || !$value) {
            $error = 'Code and value are required.';
        } elseif (!in_array($type, ['percent', 'flat'], true)) {
            $error = 'Invalid discount type.';
        } elseif ($type === 'percent' && ($value < 0 || $value > 100)) {
            $error = 'Percentage must be between 0 and 100.';
        } else {
            try {
                // Check for duplicate code
                $checkStmt = db()->prepare('SELECT id FROM coupons WHERE code = ? AND id != ?');
                $checkStmt->execute([$code, $id]);
                if ($checkStmt->fetch()) {
                    $error = 'A coupon with this code already exists.';
                } else {
                    if ($id > 0) {
                        $stmt = db()->prepare('UPDATE coupons SET code = ?, type = ?, value = ?, max_discount = ?, min_amount = ?, starts_at = ?, ends_at = ?, is_active = ? WHERE id = ?');
                        $stmt->execute([$code, $type, $value, $maxDiscount, $minAmount, $startsAt, $endsAt, $isActive, $id]);
                        $message = 'Coupon updated successfully!';
                    } else {
                        $stmt = db()->prepare('INSERT INTO coupons (code, type, value, max_discount, min_amount, starts_at, ends_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$code, $type, $value, $maxDiscount, $minAmount, $startsAt, $endsAt, $isActive]);
                        $message = 'Coupon created successfully!';
                    }
                }
            } catch (Throwable $e) {
                error_log('Coupon save error: ' . $e->getMessage());
                $error = 'Failed to save coupon. Please try again.';
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if (verify_csrf($_GET['csrf_token'] ?? null)) {
        try {
            db()->prepare('DELETE FROM coupons WHERE id = ?')->execute([$id]);
            $message = 'Coupon deleted successfully!';
        } catch (Throwable $e) {
            $error = 'Failed to delete coupon.';
        }
    } else {
        $error = 'Invalid request.';
    }
}

// Get coupon for editing
$editCoupon = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = db()->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([$id]);
    $editCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all coupons
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];

if ($search) {
    $where[] = 'code LIKE ?';
    $params[] = "%{$search}%";
}

if ($statusFilter === 'active') {
    $where[] = 'is_active = 1 AND (ends_at IS NULL OR ends_at > NOW())';
} elseif ($statusFilter === 'expired') {
    $where[] = '(ends_at IS NOT NULL AND ends_at < NOW())';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'is_active = 0';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$couponsStmt = db()->prepare("SELECT * FROM coupons $whereSql ORDER BY created_at DESC");
$couponsStmt->execute($params);
$coupons = $couponsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editCoupon ? 'Edit' : 'Manage' ?> Coupons | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <script src="assets/js/admin.js" defer></script>
    <style>
        /* Coupons Page Specific Responsive Styles */
        .coupon-form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .coupon-form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-bar .search-box {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-bar select {
            max-width: 150px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .coupon-form-grid-2,
            .coupon-form-grid-3 {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-bar .search-box,
            .filter-bar select,
            .filter-bar .btn {
                width: 100%;
                max-width: 100%;
            }
            
            .card form {
                padding: clamp(1rem, 3vw, 1.25rem) !important;
            }
            
            .form-group label {
                font-size: clamp(0.85rem, 2vw, 0.9rem);
            }
            
            .form-control {
                font-size: clamp(0.9rem, 2vw, 1rem);
                padding: clamp(10px, 2.5vw, 12px) clamp(12px, 3vw, 15px);
            }
            
            small {
                font-size: clamp(0.75rem, 1.8vw, 0.85rem) !important;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -1rem;
                padding: 0 1rem;
            }
            
            table {
                min-width: 900px;
                font-size: clamp(0.8rem, 2vw, 0.9rem);
            }
            
            th, td {
                padding: clamp(0.75rem, 2vw, 1rem) clamp(0.5rem, 1.5vw, 0.75rem);
                white-space: nowrap;
            }
            
            .actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .coupon-form-grid-2,
            .coupon-form-grid-3 {
                gap: 12px;
            }
            
            .card form {
                padding: 1rem !important;
            }
            
            table {
                min-width: 800px;
                font-size: 0.75rem;
            }
            
            th, td {
                padding: 0.625rem 0.5rem;
            }
            
            .badge {
                font-size: clamp(0.7rem, 1.8vw, 0.85rem);
                padding: clamp(3px, 1vw, 4px) clamp(6px, 1.5vw, 10px);
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .coupon-form-grid-3 {
                grid-template-columns: 1fr 1fr;
            }
            
            .coupon-form-grid-3 .form-group:last-child {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('coupons') ?>
        
        <div class="admin-main">
            <?= admin_header($editCoupon ? 'Edit Coupon' : 'Manage Coupons', 'Create discount coupons for customers') ?>
            
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

                <!-- Coupon Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?= $editCoupon ? 'edit' : 'plus' ?>"></i> 
                            <?= $editCoupon ? 'Edit Coupon' : 'Create New Coupon' ?>
                        </h3>
                        <?php if ($editCoupon): ?>
                            <a href="coupons.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                    <form method="POST" style="padding: 20px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="coupon_id" value="<?= $editCoupon['id'] ?? 0 ?>">
                        
                        <div class="coupon-form-grid-2">
                            <div class="form-group">
                                <label for="code">Coupon Code *</label>
                                <input type="text" id="code" name="code" class="form-control" 
                                       value="<?= esc($editCoupon['code'] ?? '') ?>" required
                                       style="text-transform: uppercase;" maxlength="40">
                                <small style="color: #777; font-size: 0.85rem;">Code will be converted to uppercase</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="type">Discount Type *</label>
                                <select id="type" name="type" class="form-control" required onchange="toggleDiscountFields()">
                                    <option value="percent" <?= ($editCoupon['type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
                                    <option value="flat" <?= ($editCoupon['type'] ?? '') === 'flat' ? 'selected' : '' ?>>Flat Amount (₹)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="coupon-form-grid-3">
                            <div class="form-group">
                                <label for="value">Discount Value *</label>
                                <input type="number" id="value" name="value" class="form-control" 
                                       step="0.01" min="0" value="<?= esc($editCoupon['value'] ?? '') ?>" required>
                                <small style="color: #777; font-size: 0.85rem;" id="value-hint">Enter percentage (0-100)</small>
                            </div>
                            
                            <div class="form-group" id="max-discount-group" style="<?= ($editCoupon['type'] ?? 'percent') === 'flat' ? 'display: none;' : '' ?>">
                                <label for="max_discount">Max Discount (₹)</label>
                                <input type="number" id="max_discount" name="max_discount" class="form-control" 
                                       step="0.01" min="0" value="<?= esc($editCoupon['max_discount'] ?? '') ?>">
                                <small style="color: #777; font-size: 0.85rem;">Optional: Maximum discount limit</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="min_amount">Minimum Order (₹)</label>
                                <input type="number" id="min_amount" name="min_amount" class="form-control" 
                                       step="0.01" min="0" value="<?= esc($editCoupon['min_amount'] ?? '') ?>">
                                <small style="color: #777; font-size: 0.85rem;">Optional: Minimum order amount</small>
                            </div>
                        </div>
                        
                        <div class="coupon-form-grid-2">
                            <div class="form-group">
                                <label for="starts_at">Start Date</label>
                                <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" 
                                       value="<?= $editCoupon && $editCoupon['starts_at'] ? date('Y-m-d\TH:i', strtotime($editCoupon['starts_at'])) : '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="ends_at">End Date</label>
                                <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" 
                                       value="<?= $editCoupon && $editCoupon['ends_at'] ? date('Y-m-d\TH:i', strtotime($editCoupon['ends_at'])) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" value="1" 
                                       <?= ($editCoupon['is_active'] ?? 1) ? 'checked' : '' ?>>
                                Coupon is Active
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $editCoupon ? 'Update Coupon' : 'Create Coupon' ?>
                        </button>
                    </form>
                </div>

                <!-- Coupons List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> All Coupons</h3>
                        <form method="GET" class="filter-bar" style="margin: 0; padding: 0;">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search by code" 
                                       value="<?= esc($search) ?>" class="form-control">
                            </div>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                            <?php if ($search || $statusFilter): ?>
                                <a href="coupons.php" class="btn btn-secondary btn-sm">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Min Order</th>
                                    <th>Max Discount</th>
                                    <th>Validity</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($coupons)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px; color: #777;">
                                            <i class="fas fa-tag" style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                            No coupons found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($coupons as $coupon): ?>
                                        <?php
                                        $isExpired = $coupon['ends_at'] && strtotime($coupon['ends_at']) < time();
                                        $isActive = $coupon['is_active'] && !$isExpired;
                                        ?>
                                        <tr>
                                            <td><strong style="color: var(--accent);"><?= esc($coupon['code']) ?></strong></td>
                                            <td>
                                                <span class="badge badge-info"><?= esc(ucfirst($coupon['type'])) ?></span>
                                            </td>
                                            <td>
                                                <?= $coupon['type'] === 'percent' ? $coupon['value'] . '%' : '₹' . number_format($coupon['value'], 2) ?>
                                            </td>
                                            <td><?= $coupon['min_amount'] ? '₹' . number_format($coupon['min_amount'], 2) : '-' ?></td>
                                            <td><?= $coupon['max_discount'] ? '₹' . number_format($coupon['max_discount'], 2) : '-' ?></td>
                                            <td>
                                                <?php if ($coupon['starts_at'] || $coupon['ends_at']): ?>
                                                    <small>
                                                        <?= $coupon['starts_at'] ? date('d M Y', strtotime($coupon['starts_at'])) : 'No start' ?><br>
                                                        <?= $coupon['ends_at'] ? date('d M Y', strtotime($coupon['ends_at'])) : 'No expiry' ?>
                                                    </small>
                                                <?php else: ?>
                                                    No limit
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isExpired): ?>
                                                    <span class="badge badge-danger">Expired</span>
                                                <?php elseif ($coupon['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d M Y', strtotime($coupon['created_at'])) ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a href="?edit=<?= $coupon['id'] ?>" class="action-btn action-btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?= $coupon['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                                       class="action-btn action-btn-delete" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this coupon?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
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
        function toggleDiscountFields() {
            const type = document.getElementById('type').value;
            const maxDiscountGroup = document.getElementById('max-discount-group');
            const valueHint = document.getElementById('value-hint');
            
            if (type === 'flat') {
                maxDiscountGroup.style.display = 'none';
                valueHint.textContent = 'Enter flat amount in ₹';
            } else {
                maxDiscountGroup.style.display = 'block';
                valueHint.textContent = 'Enter percentage (0-100)';
            }
        }
        
        // Convert code to uppercase on input
        document.getElementById('code')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>

