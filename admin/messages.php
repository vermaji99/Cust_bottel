<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $messageId = (int) $_GET['delete'];
    if (verify_csrf($_GET['csrf_token'] ?? null)) {
        try {
            $stmt = db()->prepare('DELETE FROM messages WHERE id = ?');
            $stmt->execute([$messageId]);
            $message = 'Message deleted successfully!';
        } catch (Throwable $e) {
            $error = 'Failed to delete message.';
        }
    } else {
        $error = 'Invalid request.';
    }
}

// Handle Mark as Read/Unread (if we add a read status column later)
// For now, we'll just display all messages

// Fetch messages with filters
$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = db()->prepare("SELECT COUNT(*) FROM messages $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch messages
$messagesStmt = db()->prepare("
    SELECT *
    FROM messages
    $whereSql
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
");
$messagesStmt->execute($params);
$messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count (for future use if we add read status)
$unreadCount = db()->query("SELECT COUNT(*) FROM messages")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <style>
        .message-card {
            background: #1a1a1a;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #00bcd4;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 188, 212, 0.2);
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .message-sender {
            flex: 1;
        }
        .message-sender h3 {
            color: #00bcd4;
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }
        .message-sender p {
            color: #aaa;
            margin: 0;
            font-size: 0.9rem;
        }
        .message-date {
            color: #777;
            font-size: 0.85rem;
        }
        .message-body {
            color: #ddd;
            line-height: 1.6;
            margin-bottom: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #2a2a2a;
        }
        .btn-icon-small {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-email {
            background: #00bcd4;
            color: white;
        }
        .btn-email:hover {
            background: #0097a7;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #777;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
        }
        .search-bar input:focus {
            outline: none;
            border-color: #00bcd4;
        }
        .search-bar button {
            padding: 12px 20px;
            background: #00bcd4;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        .search-bar button:hover {
            background: #0097a7;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('messages') ?>
        
        <div class="admin-main">
            <?= admin_header('Contact Messages', 'View and manage customer inquiries') ?>
            
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

                <!-- Summary Card -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <i class="fas fa-envelope"></i>
                        <h3><?= number_format($totalRows) ?></h3>
                        <p>Total Messages</p>
                    </div>
                </div>

                <!-- Search Bar -->
                <form method="GET" action="" class="search-bar">
                    <input type="text" name="search" placeholder="Search by name, email, or message..." value="<?= esc($search) ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="messages.php" class="btn-icon-small" style="background: #666; color: white;"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Messages List -->
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No messages found</h3>
                        <p><?= $search ? 'Try a different search term.' : 'No contact messages have been received yet.' ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-card">
                            <div class="message-header">
                                <div class="message-sender">
                                    <h3><?= esc($msg['name']) ?></h3>
                                    <p><i class="fas fa-envelope"></i> <?= esc($msg['email']) ?></p>
                                </div>
                                <div class="message-date">
                                    <i class="fas fa-clock"></i> <?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                            <div class="message-body">
                                <?= esc($msg['message']) ?>
                            </div>
                            <div class="message-actions">
                                <a href="mailto:<?= esc($msg['email']) ?>?subject=Re: Your Contact Form Inquiry" class="btn-icon-small btn-email">
                                    <i class="fas fa-reply"></i> Reply
                                </a>
                                <a href="?delete=<?= $msg['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                   class="btn-icon-small btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this message?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div style="display: flex; justify-content: center; gap: 10px; margin-top: 30px; flex-wrap: wrap;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="btn-icon-small" style="background: #2a2a2a; color: white;">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <span style="display: flex; align-items: center; color: #aaa; padding: 0 15px;">
                                Page <?= $page ?> of <?= $totalPages ?>
                            </span>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="btn-icon-small" style="background: #2a2a2a; color: white;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

