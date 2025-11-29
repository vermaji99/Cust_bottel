<?php
require __DIR__ . '/bootstrap.php';

// Check if user is admin
$currentUser = current_user();
if (!$currentUser || $currentUser['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 403);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method'], 405);
    exit;
}

// Validate CSRF token
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Invalid security token'], 403);
    exit;
}

// Get and validate input
$reviewId = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
$adminReply = isset($_POST['admin_reply']) ? trim($_POST['admin_reply']) : '';

// Validate review ID
if ($reviewId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid review ID'], 400);
    exit;
}

// Validate reply (optional, but if provided, max length)
if (strlen($adminReply) > 1000) {
    json_response(['success' => false, 'message' => 'Reply is too long (max 1000 characters)'], 400);
    exit;
}

try {
    // Check if review exists
    $reviewStmt = db()->prepare("SELECT id, product_id FROM reviews WHERE id = ?");
    $reviewStmt->execute([$reviewId]);
    $review = $reviewStmt->fetch();

    if (!$review) {
        json_response(['success' => false, 'message' => 'Review not found'], 404);
        exit;
    }

    // Update review with admin reply
    $updateStmt = db()->prepare("
        UPDATE reviews 
        SET admin_reply = ?, admin_replied_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$adminReply ?: null, $reviewId]);

    json_response([
        'success' => true,
        'message' => 'Reply added successfully'
    ]);

} catch (PDOException $e) {
    error_log("Admin reply error: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Database error occurred'], 500);
}

