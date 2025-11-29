<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$message = '';
$error = '';

// Handle Block/Unblock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_block'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired.';
    } else {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        
        if ($action === 'block') {
            try {
                $stmt = db()->prepare('UPDATE users SET is_blocked = 1, blocked_at = NOW() WHERE id = ? AND role = "user"');
                $stmt->execute([$userId]);
                $message = 'User blocked successfully!';
            } catch (Throwable $e) {
                $error = 'Failed to block user.';
            }
        } elseif ($action === 'unblock') {
            try {
                $stmt = db()->prepare('UPDATE users SET is_blocked = 0, blocked_at = NULL, blocked_reason = NULL WHERE id = ? AND role = "user"');
                $stmt->execute([$userId]);
                $message = 'User unblocked successfully!';
            } catch (Throwable $e) {
                $error = 'Failed to unblock user.';
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $userId = (int) $_GET['delete'];
    if (verify_csrf($_GET['csrf_token'] ?? null)) {
        // Check if user has orders
        $orderCheck = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
        $orderCheck->execute([$userId]);
        $orderCount = (int) $orderCheck->fetchColumn();
        
        if ($orderCount > 0) {
            $error = "Cannot delete user. User has {$orderCount} order(s).";
        } else {
            try {
                db()->prepare('DELETE FROM users WHERE id = ? AND role = "user"')->execute([$userId]);
                $message = 'User deleted successfully!';
            } catch (Throwable $e) {
                $error = 'Failed to delete user.';
            }
        }
    } else {
        $error = 'Invalid request.';
    }
}

// Fetch users with filters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['u.role = "user"'];
$params = [];

if ($search) {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($statusFilter === 'blocked') {
    $where[] = 'u.is_blocked = 1';
} elseif ($statusFilter === 'active') {
    $where[] = '(u.is_blocked = 0 OR u.is_blocked IS NULL)';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// Count total
$countStmt = db()->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch users
$usersStmt = db()->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
           (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status IN ('delivered', 'shipped')) as total_spent
    FROM users u
    $whereSql
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$usersStmt->execute($params);
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('users') ?>
        
        <div class="admin-main">
            <?= admin_header('Manage Users', 'View, block, or delete user accounts') ?>
            
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

                <!-- Filters -->
                <div class="card">
                    <form method="GET" class="filter-bar" style="padding: 20px;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search by name, email, or phone" 
                                   value="<?= esc($search) ?>" class="form-control">
                        </div>
                        <select name="status" class="form-control" style="max-width: 200px;">
                            <option value="">All Users</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="blocked" <?= $statusFilter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if ($search || $statusFilter): ?>
                            <a href="users.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users"></i> Users (<?= number_format($totalRows) ?>)</h3>
                    </div>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 40px; color: #777;">
                                            <i class="fas fa-user-slash" style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                            No users found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <?php $isBlocked = !empty($user['is_blocked']); ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <strong><?= esc($user['name']) ?></strong>
                                                <?php if (!$user['email_verified_at']): ?>
                                                    <br><small style="color: var(--warning);"><i class="fas fa-exclamation-triangle"></i> Unverified</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc($user['email']) ?></td>
                                            <td><?= esc($user['phone'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge badge-info"><?= (int)$user['order_count'] ?></span>
                                            </td>
                                            <td>₹<?= number_format($user['total_spent'] ?? 0, 2) ?></td>
                                            <td>
                                                <?php if ($isBlocked): ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-ban"></i> Blocked
                                                    </span>
                                                    <?php if ($user['blocked_at']): ?>
                                                        <br><small style="color: #777;"><?= date('d M Y', strtotime($user['blocked_at'])) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="actions">
                                                    <?php if ($isBlocked): ?>
                                                        <form method="POST" style="display: inline-block;">
                                                            <?= csrf_field(); ?>
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="action" value="unblock">
                                                            <input type="hidden" name="toggle_block" value="1">
                                                            <button type="submit" class="action-btn action-btn-edit" title="Unblock" onclick="return confirm('Unblock this user?')">
                                                                <i class="fas fa-unlock"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline-block;">
                                                            <?= csrf_field(); ?>
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="action" value="block">
                                                            <input type="hidden" name="toggle_block" value="1">
                                                            <button type="submit" class="action-btn action-btn-delete" title="Block" onclick="return confirm('Block this user? They will not be able to login.')">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($user['order_count'] == 0): ?>
                                                        <a href="?delete=<?= $user['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                                           class="action-btn action-btn-delete" 
                                                           title="Delete"
                                                           onclick="return confirm('Are you sure? This cannot be undone.')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span title="Cannot delete user with orders" style="color: #777; cursor: not-allowed;">
                                                            <i class="fas fa-trash" style="opacity: 0.3;"></i>
                                                        </span>
                                                    <?php endif; ?>
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
</body>
</html>
