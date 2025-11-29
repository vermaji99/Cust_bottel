<?php
/**
 * Submit Review API Endpoint
 * 
 * Handles review submission with comprehensive error handling
 * Table: reviews (id, product_id, user_id, rating, comment, admin_reply, admin_replied_at, created_at, updated_at)
 */
require __DIR__ . '/bootstrap.php';

// Check authentication
$currentUser = current_user();
if (!$currentUser) {
    json_response(['success' => false, 'message' => 'Please login to submit a review'], 401);
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Only POST method allowed'], 405);
}

// Validate CSRF token
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(['success' => false, 'message' => 'Invalid security token'], 403);
}

// Get and sanitize input from POST (FormData)
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment'] ?? '') : '';

// Validate inputs
if ($productId <= 0) {
    json_response(['success' => false, 'message' => 'Invalid product ID'], 400);
}

if ($rating < 1 || $rating > 5) {
    json_response(['success' => false, 'message' => 'Rating must be between 1 and 5'], 400);
}

if (strlen($comment) > 1000) {
    json_response(['success' => false, 'message' => 'Comment too long (max 1000 characters)'], 400);
}

// Process review submission
try {
    $db = db();
    
    // Ensure reviews table has 'comment' column (fix for missing column error)
    try {
        $checkColumn = $db->query("SHOW COLUMNS FROM reviews LIKE 'comment'");
        if ($checkColumn->rowCount() == 0) {
            // Column doesn't exist, add it
            $db->exec("ALTER TABLE reviews ADD COLUMN comment TEXT DEFAULT NULL AFTER rating");
            error_log("Added missing 'comment' column to reviews table");
        }
    } catch (PDOException $colError) {
        error_log("Column check error (table might not exist): " . $colError->getMessage());
    }
    
    // Verify product exists
    $productStmt = $db->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $productStmt->execute([$productId]);
    if (!$productStmt->fetch()) {
        json_response(['success' => false, 'message' => 'Product not found or inactive'], 404);
    }

    // Check for existing review
    $existingStmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $existingStmt->execute([$currentUser['id'], $productId]);
    $existing = $existingStmt->fetch();

    // Prepare comment (null if empty string)
    $commentValue = ($comment === '') ? null : $comment;

    if ($existing) {
        // Update existing review
        $updateStmt = $db->prepare("
            UPDATE reviews 
            SET rating = ?, comment = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$rating, $commentValue, $existing['id']]);
        $reviewId = $existing['id'];
    } else {
        // Insert new review
        $insertStmt = $db->prepare("
            INSERT INTO reviews (product_id, user_id, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->execute([$productId, $currentUser['id'], $rating, $commentValue]);
        $reviewId = $db->lastInsertId();
        
        if (!$reviewId) {
            throw new PDOException('Failed to get review ID after insertion');
        }
    }

    // Calculate average rating
    $avgStmt = $db->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
        FROM reviews 
        WHERE product_id = ?
    ");
    $avgStmt->execute([$productId]);
    $avgData = $avgStmt->fetch(PDO::FETCH_ASSOC);
    
    $avgRating = round(floatval($avgData['avg_rating'] ?? 0), 1);
    $totalReviews = intval($avgData['total_reviews'] ?? 0);

    json_response([
        'success' => true,
        'message' => 'Review submitted successfully',
        'review_id' => $reviewId,
        'avg_rating' => $avgRating,
        'total_reviews' => $totalReviews
    ]);

} catch (PDOException $e) {
    // Log detailed error for debugging
    $errorInfo = $e->errorInfo ?? [];
    error_log("Review submission PDO error: " . $e->getMessage());
    error_log("SQL State: " . ($errorInfo[0] ?? 'N/A'));
    error_log("Error Code: " . ($errorInfo[1] ?? 'N/A'));
    error_log("Error Message: " . ($errorInfo[2] ?? $e->getMessage()));
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    
    // Always return detailed error for debugging
    json_response([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_details' => [
            'sql_state' => $errorInfo[0] ?? 'N/A',
            'error_code' => $errorInfo[1] ?? 'N/A',
            'error_message' => $errorInfo[2] ?? $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ], 500);
    
} catch (Exception $e) {
    error_log("Review submission error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    json_response([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'error_details' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ], 500);
}
