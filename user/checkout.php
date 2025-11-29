<?php
require __DIR__ . '/init.php';
$userId = $authUser['id'];
$messages = [];
$errors = [];
$userStmt = db()->prepare('SELECT name, email, phone, address, city, pincode FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

cart_clear_unavailable($userId);
$cartItems = cart_items_detailed($userId);
// Allow checkout page to load even if cart is empty (product might be adding via Buy Now)
// Will show empty cart message instead of redirecting

$appliedCouponCode = $_SESSION['cart_coupon'] ?? null;
$appliedCoupon = $appliedCouponCode ? find_coupon($appliedCouponCode) : null;

$subtotal = 0;
foreach ($cartItems as &$item) {
    // Use price_snapshot if available (price when added to cart), otherwise current price
    $itemPrice = !empty($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$item['price'];
    $quantity = (int)$item['quantity'];
    $subtotal += $quantity * $itemPrice;
    $item['display_price'] = $itemPrice; // Store for display
}
unset($item); // Unset reference

// Calculate discount and total - ensure proper rounding
$subtotal = round($subtotal, 2);
$discount = $appliedCoupon ? calculate_coupon_discount($subtotal, $appliedCoupon) : 0;
$discount = round($discount, 2);
$grandTotal = round(max($subtotal - $discount, 0), 2);

// Check if designs table has the new schema columns (design_key)
try {
    $checkColumns = db()->query("SHOW COLUMNS FROM designs LIKE 'design_key'");
    $hasNewSchema = $checkColumns->rowCount() > 0;
} catch (Exception $e) {
    $hasNewSchema = false;
}

// Fetch designs based on schema
if ($hasNewSchema) {
    // New schema: design_key, thumbnail_path, file_path
    $designStmt = db()->prepare('SELECT id, design_key, thumbnail_path, file_path FROM designs WHERE user_id = ? ORDER BY created_at DESC');
    $designStmt->execute([$userId]);
    $designs = $designStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Old schema: filename (no design_key, no thumbnail_path) - just use id
    $designStmt = db()->prepare('SELECT id, filename FROM designs WHERE user_id = ? ORDER BY saved_at DESC');
    $designStmt->execute([$userId]);
    $designs = $designStmt->fetchAll(PDO::FETCH_ASSOC);
    // Convert old schema to new format for display
    foreach ($designs as &$design) {
        $design['design_key'] = 'design_' . $design['id'];
        $design['thumbnail_path'] = $design['filename'] ?? null;
    }
    unset($design);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_coupon'])) {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $errors[] = 'Session expired. Please refresh.';
        } else {
            $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
            $coupon = $code ? find_coupon($code) : null;
            if ($coupon) {
                $_SESSION['cart_coupon'] = $coupon['code'];
                $messages[] = 'Coupon applied successfully.';
                // Recalculate totals
                $appliedCouponCode = $coupon['code'];
                $appliedCoupon = $coupon;
                $discount = calculate_coupon_discount($subtotal, $appliedCoupon);
                $discount = round($discount, 2);
                $grandTotal = round(max($subtotal - $discount, 0), 2);
            } else {
                $errors[] = 'Invalid or expired coupon code.';
            }
        }
    } elseif (isset($_POST['remove_coupon'])) {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $errors[] = 'Session expired. Please refresh.';
        } else {
            unset($_SESSION['cart_coupon']);
            $messages[] = 'Coupon removed.';
            // Recalculate totals
            $appliedCouponCode = null;
            $appliedCoupon = null;
            $discount = 0;
            $grandTotal = $subtotal;
        }
    } elseif (isset($_POST['remove_item'])) {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            $errors[] = 'Session expired. Please refresh.';
        } else {
            $productId = (int)($_POST['remove_item'] ?? 0);
            if ($productId > 0) {
                cart_remove($userId, $productId);
                $messages[] = 'Item removed from cart.';
                // Reload cart items
                $cartItems = cart_items_detailed($userId);
                $subtotal = 0;
                foreach ($cartItems as &$item) {
                    $itemPrice = !empty($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$item['price'];
                    $quantity = (int)$item['quantity'];
                    $subtotal += $quantity * $itemPrice;
                    $item['display_price'] = $itemPrice;
                }
                unset($item);
                $subtotal = round($subtotal, 2);
                $discount = $appliedCoupon ? calculate_coupon_discount($subtotal, $appliedCoupon) : 0;
                $discount = round($discount, 2);
                $grandTotal = round(max($subtotal - $discount, 0), 2);
            }
        }
    } elseif (isset($_POST['place_order'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired. Please refresh.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $payment = $_POST['payment'] ?? 'COD';
        $designKey = $_POST['design_key'] ?? null;
        $customDesignDescription = isset($_POST['custom_design_description']) ? trim($_POST['custom_design_description']) : '';
        
        // Debug: Log what we received
        error_log('Checkout - custom_design_description received: ' . var_export($customDesignDescription, true));
        error_log('Checkout - custom_design_image file: ' . var_export($_FILES['custom_design_image'] ?? 'NOT SET', true));

        if (!$name || !$email || !$phone || !$address) {
            $errors[] = 'Please fill out all shipping details.';
        }

        // Handle custom design image upload
        $customDesignImage = null;
        if (isset($_FILES['custom_design_image']) && $_FILES['custom_design_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['custom_design_image'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'Invalid image format. Please upload JPG, PNG, or GIF only.';
            } elseif ($file['size'] > $maxSize) {
                $errors[] = 'Image size too large. Maximum 5MB allowed.';
            } else {
                $uploadDir = __DIR__ . '/../admin/uploads/custom_designs/';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        $errors[] = 'Failed to create upload directory.';
                        error_log('Failed to create directory: ' . $uploadDir);
                    } else {
                        error_log('Created upload directory: ' . $uploadDir);
                    }
                }
                
                // Check if directory is writable
                if (!is_writable($uploadDir)) {
                    $errors[] = 'Upload directory is not writable.';
                    error_log('Upload directory is not writable: ' . $uploadDir);
                } else {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'custom_design_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $filePath = $uploadDir . $fileName;
                    
                    error_log('Attempting to upload file: ' . $file['tmp_name'] . ' to ' . $filePath);
                    error_log('File size: ' . $file['size'] . ' bytes, Type: ' . $file['type']);
                    
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        $customDesignImage = 'custom_designs/' . $fileName;
                        error_log('Custom design image uploaded successfully: ' . $customDesignImage);
                        error_log('Full file path: ' . $filePath);
                    } else {
                        $errors[] = 'Failed to upload image. Please try again.';
                        error_log('Failed to move uploaded file from ' . $file['tmp_name'] . ' to ' . $filePath);
                        error_log('Upload error code: ' . $file['error']);
                        error_log('is_uploaded_file check: ' . (is_uploaded_file($file['tmp_name']) ? 'YES' : 'NO'));
                    }
                }
            }
        }

        $selectedDesignId = null;
        if ($designKey) {
            // Check if we're using new schema
            try {
                $checkColumns = db()->query("SHOW COLUMNS FROM designs LIKE 'design_key'");
                $hasNewSchema = $checkColumns->rowCount() > 0;
            } catch (Exception $e) {
                $hasNewSchema = false;
            }
            
            if ($hasNewSchema) {
                // New schema: use design_key column
                $designLookup = db()->prepare('SELECT id FROM designs WHERE design_key = ? AND user_id = ? LIMIT 1');
                $designLookup->execute([$designKey, $userId]);
                $selectedDesignId = $designLookup->fetchColumn() ?: null;
            } else {
                // Old schema: extract ID from design_key format (e.g., "design_123" -> 123)
                if (preg_match('/^design_(\d+)$/', $designKey, $matches)) {
                    $designId = (int)$matches[1];
                    // Verify it belongs to user
                    $verify = db()->prepare('SELECT id FROM designs WHERE id = ? AND user_id = ? LIMIT 1');
                    $verify->execute([$designId, $userId]);
                    $selectedDesignId = $verify->fetchColumn() ?: null;
                }
            }
        }

        if (!$errors) {
            db()->beginTransaction();
            try {
                // Check which schema the orders table uses
                $checkColumns = db()->query("SHOW COLUMNS FROM orders");
                $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
                $hasNewSchema = in_array('order_number', $columns) && in_array('subtotal', $columns);
                $hasCustomDesignImage = in_array('custom_design_image', $columns);
                $hasCustomDesignDescription = in_array('custom_design_description', $columns);
                
                if ($hasNewSchema) {
                    // New schema - always include custom design columns if they exist
                    if ($hasCustomDesignImage && $hasCustomDesignDescription) {
                        $orderStmt = db()->prepare('
                            INSERT INTO orders
                            (user_id, order_number, design_id, coupon_code, subtotal, discount_total, total_amount, status, payment_method, shipping_name, shipping_email, shipping_phone, shipping_address, custom_design_image, custom_design_description)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ');
                        $orderNumber = 'BTL' . strtoupper(bin2hex(random_bytes(3)));
                        $status = 'pending';
                        
                        // Ensure values are properly set
                        $imageValue = ($customDesignImage !== null && $customDesignImage !== '') ? $customDesignImage : null;
                        $descValue = (!empty($customDesignDescription) && trim($customDesignDescription) !== '') ? trim($customDesignDescription) : null;
                        
                        // Debug logging to verify values
                        error_log('Order Insert - Image Value: ' . var_export($imageValue, true));
                        error_log('Order Insert - Description Value: ' . var_export($descValue, true));
                        error_log('Order Insert - Has Custom Design Columns: ' . ($hasCustomDesignImage && $hasCustomDesignDescription ? 'YES' : 'NO'));
                        
                        $orderStmt->execute([
                            $userId,
                            $orderNumber,
                            $selectedDesignId,
                            $appliedCouponCode,
                            $subtotal,
                            $discount,
                            $grandTotal,
                            $status,
                            $payment,
                            $name,
                            $email,
                            $phone,
                            $address,
                            $imageValue,
                            $descValue,
                        ]);
                        
                        // Debug log (remove in production)
                        error_log('Order placed with custom design - Image: ' . var_export($imageValue, true) . ', Description: ' . var_export($descValue, true));
                    } else {
                        $orderStmt = db()->prepare('
                            INSERT INTO orders
                            (user_id, order_number, design_id, coupon_code, subtotal, discount_total, total_amount, status, payment_method, shipping_name, shipping_email, shipping_phone, shipping_address)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ');
                        $orderNumber = 'BTL' . strtoupper(bin2hex(random_bytes(3)));
                        $status = 'pending';
                        $orderStmt->execute([
                            $userId,
                            $orderNumber,
                            $selectedDesignId,
                            $appliedCouponCode,
                            $subtotal,
                            $discount,
                            $grandTotal,
                            $status,
                            $payment,
                            $name,
                            $email,
                            $phone,
                            $address,
                        ]);
                    }
                } else {
                    // Old schema - use name, email, phone, address, total
                    // Check if custom design columns exist and include them
                    if ($hasCustomDesignImage && $hasCustomDesignDescription) {
                        $orderStmt = db()->prepare('
                            INSERT INTO orders
                            (user_id, name, email, phone, address, payment_method, total, status, custom_design_image, custom_design_description)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ');
                        $status = 'Pending'; // Old schema uses capitalized status
                        
                        // Prepare values for database
                        $imageValue = ($customDesignImage !== null && $customDesignImage !== '') ? $customDesignImage : null;
                        $descValue = (!empty($customDesignDescription) && trim($customDesignDescription) !== '') ? trim($customDesignDescription) : null;
                        
                        // Debug logging - BEFORE execute
                        error_log('=== OLD SCHEMA ORDER INSERT ===');
                        error_log('Has Custom Design Columns: ' . ($hasCustomDesignImage && $hasCustomDesignDescription ? 'YES' : 'NO'));
                        error_log('Custom Design Image Variable: ' . var_export($customDesignImage, true));
                        error_log('Custom Design Description Variable: ' . var_export($customDesignDescription, true));
                        error_log('Image Value (final): ' . var_export($imageValue, true));
                        error_log('Description Value (final): ' . var_export($descValue, true));
                        error_log('SQL: INSERT INTO orders (user_id, name, email, phone, address, payment_method, total, status, custom_design_image, custom_design_description)');
                        
                        try {
                            $orderStmt->execute([
                                $userId,
                                $name,
                                $email,
                                $phone,
                                $address,
                                $payment,
                                $grandTotal,
                                $status,
                                $imageValue,
                                $descValue,
                            ]);
                            $orderId = (int) db()->lastInsertId();
                            error_log('Order INSERT SUCCESS - Order ID: ' . $orderId);
                            error_log('Values inserted - Image: ' . var_export($imageValue, true) . ', Description: ' . var_export($descValue, true));
                        } catch (Exception $insertError) {
                            error_log('Order INSERT FAILED: ' . $insertError->getMessage());
                            error_log('SQL Error Info: ' . var_export($orderStmt->errorInfo(), true));
                            throw $insertError;
                        }
                    } else {
                        // Columns don't exist, use basic INSERT without custom design columns
                        $orderStmt = db()->prepare('
                            INSERT INTO orders
                            (user_id, name, email, phone, address, payment_method, total, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ');
                        $status = 'Pending'; // Old schema uses capitalized status
                        $orderStmt->execute([
                            $userId,
                            $name,
                            $email,
                            $phone,
                            $address,
                            $payment,
                            $grandTotal,
                            $status,
                        ]);
                        $orderId = (int) db()->lastInsertId();
                        error_log('Old Schema - Order saved (without custom design columns) - ID: ' . $orderId);
                    }
                    // For old schema, use order ID as the identifier
                    if (!isset($orderId)) {
                        $orderId = (int) db()->lastInsertId();
                    }
                    $orderNumber = (string) $orderId; // Use ID as order number for redirect
                    
                    error_log('Old Schema - Order saved with ID: ' . $orderId);
                }
                
                // Get order ID after insert (common for both schemas)
                if (!isset($orderId)) {
                    $orderId = (int) db()->lastInsertId();
                }

                // Check order_items schema
                $checkItemColumns = db()->query("SHOW COLUMNS FROM order_items");
                $itemColumns = $checkItemColumns->fetchAll(PDO::FETCH_COLUMN);
                $hasUnitPrice = in_array('unit_price', $itemColumns);
                
                if ($hasUnitPrice) {
                    $itemStmt = db()->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
                    foreach ($cartItems as $item) {
                        $itemPrice = $item['display_price'] ?? (!empty($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$item['price']);
                        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $itemPrice]);
                    }
                } else {
                    // Old schema uses 'price' instead of 'unit_price'
                    $itemStmt = db()->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                    foreach ($cartItems as $item) {
                        $itemPrice = $item['display_price'] ?? (!empty($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$item['price']);
                        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $itemPrice]);
                    }
                }

                // Only insert timeline if table exists
                try {
                    $checkTimeline = db()->query("SHOW TABLES LIKE 'order_status_history'");
                    if ($checkTimeline->rowCount() > 0) {
                        $timelineStmt = db()->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
                        $timelineStmt->execute([$orderId, $status, 'Order received.']);
                    }
                } catch (Exception $e) {
                    // Timeline table doesn't exist, skip it
                    error_log('order_status_history table not found, skipping timeline entry');
                }

                $clearCart = db()->prepare('DELETE FROM cart_items WHERE user_id = ?');
                $clearCart->execute([$userId]);
                unset($_SESSION['cart_coupon']);

                // Send confirmation emails (don't fail order if email fails)
                try {
                    send_order_confirmation_email($orderId);
                } catch (Throwable $emailError) {
                    error_log('Order confirmation email failed for order #' . $orderNumber . ': ' . $emailError->getMessage());
                    // Continue with order placement even if email fails
                }
                
                // Send admin order received email for action
                try {
                    send_admin_order_received_email($orderId);
                } catch (Throwable $emailError) {
                    error_log('Admin order received email failed for order #' . $orderNumber . ': ' . $emailError->getMessage());
                    // Continue with order placement even if email fails
                }

                db()->commit();
                // For redirect, use order ID if old schema (numeric), otherwise use order number
                // Ensure variables are defined
                $redirectOrderNumber = isset($orderNumber) ? $orderNumber : 'ORD-' . $orderId;
                $redirectParam = $hasNewSchema ? $redirectOrderNumber : (string)$orderId;
                header('Location: thank_you.php?order=' . urlencode($redirectParam));
                exit;
            } catch (Throwable $e) {
                db()->rollBack();
                // Log the actual error for debugging
                error_log('Order placement error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                $errors[] = 'Failed to place order. Please try again.';
                // Show actual error for debugging (remove in production):
                if (app_config('env') === 'local' || app_config('env') === 'development') {
                    $errors[] = 'Error: ' . $e->getMessage();
                }
                }
            }
        }
    }
}
$csrf = csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout | Bottle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-base" content="<?= esc(app_config('app_url')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #0c0c0c;
            color: #f0f0f0;
            padding-top: 0;
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }

        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 64px 4%;
            padding-top: 100px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            align-items: start;
        }

        .checkout-form {
            background: #141414;
            padding: 32px;
            border-radius: 8px;
            border: 2px solid #333;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: fit-content;
        }

        .summary {
            background: #141414;
            padding: 32px;
            border-radius: 8px;
            border: 2px solid #333;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary h2 {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 32px 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #333;
        }

        .summary h2:first-of-type {
            margin-top: 0;
        }

        .summary label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ccc;
            font-size: 0.95rem;
        }

        .summary select {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 2px solid #333;
            background: #1a1a1a;
            color: white;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .summary select:focus {
            outline: none;
            border-color: #00bcd4;
        }

        h1 {
            color: #00bcd4;
            text-align: center;
            margin-bottom: 32px;
            font-size: 2rem;
            font-weight: 700;
        }

        h2 {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 32px 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #333;
        }

        h2:first-of-type {
            margin-top: 0;
        }
        
        .login-prompt {
            background: rgba(0, 188, 212, 0.1);
            border: 1px solid #00bcd4;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #00bcd4;
            text-align: center;
            font-weight: 500;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ccc;
            font-size: 0.95rem;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 2px solid #333;
            background: #1a1a1a;
            color: white;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #00bcd4;
        }

        textarea { 
            resize: vertical; 
            min-height: 100px;
            font-family: inherit;
        }

        .btn {
            background: #00bcd4;
            border: none;
            padding: 12px 24px;
            color: white;
            border-radius: 12px;
            width: 100%;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            font-family: inherit;
        }
        
        .btn:hover { 
            background: #00acc1;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-place-order {
            margin-top: 40px;
            margin-bottom: 0;
            background: #00bcd4;
            font-size: 1.1rem;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }

        .btn-place-order:hover {
            background: #00acc1;
        }

        .summary h3 {
            color: #00bcd4;
            border-bottom: 1px solid #222;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            color: #ccc;
            padding: 8px 0;
            font-size: 0.95rem;
        }

        .summary-item span:first-child {
            flex: 1;
        }

        .summary-item span:last-child {
            color: #00bcd4;
            font-weight: 600;
        }

        .total {
            border-top: 2px solid #333;
            margin-top: 20px;
            padding-top: 16px;
            font-size: 1.25rem;
            color: #00bcd4;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .error {
            background: rgba(132, 32, 41, 0.2);
            border: 1px solid #842029;
            color: #f8d7da;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message {
            background: rgba(0, 188, 212, 0.1);
            border: 1px solid #00bcd4;
            color: #6ef1c2;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .design-option {
            background: #1c1c1c;
            padding: 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            margin-bottom: 8px;
        }

        .design-option:hover {
            border-color: #00bcd4;
            background: #222;
        }

        .design-option input[type="radio"] {
            width: auto;
            margin: 0;
        }

        .design-option input[type="radio"]:checked + * {
            color: #00bcd4;
        }

        .design-option img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .summary .design-option {
            width: 100%;
        }

        .coupon-applied {
            background: rgba(0, 188, 212, 0.1);
            border: 1px solid #00bcd4;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .coupon-applied span {
            color: #6ef1c2;
            font-weight: 500;
        }

        .remove-btn {
            background: #ff4444;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .remove-btn:hover {
            background: #ff6666;
            transform: scale(1.05);
        }

        .coupon-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .coupon-input-group input {
            flex: 1;
            margin: 0;
        }

        .coupon-input-group .btn {
            width: auto;
            padding: 12px 24px;
            margin: 0;
        }

        footer {
            background: #080808;
            text-align: center;
            padding: 40px 10%;
            color: #777;
            margin-top: 50px;
        }

        /* Remove any gradient backgrounds from summary */
        .summary {
            background: #141414 !important;
        }

        @media (max-width: 1024px) {
            main { 
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            main {
                padding: 100px 4% 40px;
            }

            .checkout-form, .summary {
                padding: 24px;
            }

            h1 {
                font-size: 1.5rem;
            }

            h2 {
                font-size: 1.1rem;
            }

            .coupon-input-group {
                flex-direction: column;
            }

            .coupon-input-group .btn {
                width: 100%;
            }
        }
        </style>
    <link rel="stylesheet" href="../assets/css/navbar.css">
</head>
<body>

<?php
$currentPage = 'checkout';
include __DIR__ . '/includes/navbar.php';
?>

<main>
    <form method="POST" class="checkout-form" id="checkout-form" enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <h1>Checkout Details</h1>

        <?php if ($messages): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?= esc(implode(' ', $messages)); ?>
            </div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?= esc(implode(' ', $errors)); ?>
            </div>
        <?php endif; ?>

        <h2>1. Shipping Information</h2>
        
        <label>Full Name</label>
        <input type="text" name="name" value="<?= esc($userData['name'] ?? $authUser['name']); ?>" required>

        <label>Email Address</label>
        <input type="email" name="email" value="<?= esc($userData['email'] ?? $authUser['email']); ?>" required>

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?= esc($userData['phone'] ?? ''); ?>" required>

        <label>Shipping Address</label>
        <?php
            $fullAddress = '';
            if (!empty($userData['address'])) {
                $fullAddress = $userData['address'];
                if (!empty($userData['city'])) {
                    $fullAddress .= ', ' . $userData['city'];
                }
                if (!empty($userData['pincode'])) {
                    $fullAddress .= ' - ' . $userData['pincode'];
                }
            }
        ?>
        <textarea name="address" required><?= esc($fullAddress); ?></textarea>

        <button type="submit" name="place_order" class="btn btn-place-order">
            <i class="fas fa-shopping-bag"></i> Place Order (₹<?= number_format($grandTotal, 2) ?>)
        </button>
    </form>

    <div class="summary">
        <h2>2. Payment Method</h2>
        <label>Select Payment Option</label>
        <select name="payment" form="checkout-form" required>
            <option value="COD">Cash on Delivery</option>
            <option value="Online">Online Payment (e.g., UPI/Card)</option>
        </select>

        <h2>3. Attach Design (optional)</h2>
        <p style="color:#aaa;font-size:0.9rem;margin-bottom:15px;">Upload your custom design image and add description for better understanding of your requirements.</p>
        
        <!-- Custom Design Upload -->
        <div style="margin-bottom:20px;padding:15px;background:rgba(0,188,212,0.05);border-radius:8px;border:1px dashed rgba(0,188,212,0.3);">
            <label style="display:block;margin-bottom:10px;color:#00bcd4;font-weight:500;">
                <i class="fas fa-upload"></i> Upload Design Image (optional)
            </label>
            <input type="file" name="custom_design_image" form="checkout-form" accept="image/*" style="margin-bottom:15px;padding:10px;background:#1a1a1a;border:1px solid #333;border-radius:6px;color:#fff;width:100%;">
            <small style="color:#888;display:block;margin-bottom:15px;">Supported formats: JPG, PNG, GIF (Max 5MB)</small>
            
            <label style="display:block;margin-bottom:10px;color:#00bcd4;font-weight:500;">
                <i class="fas fa-comment-alt"></i> Design Description / Requirements (optional)
            </label>
            <textarea name="custom_design_description" form="checkout-form" rows="4" placeholder="Describe your design requirements, special instructions, colors, text, or any other details..." style="width:100%;padding:12px;background:#1a1a1a;border:1px solid #333;border-radius:6px;color:#fff;resize:vertical;font-family:inherit;"></textarea>
        </div>
        
        <!-- Saved Designs (if any) -->
        <?php if ($designs): ?>
            <p style="color:#aaa;font-size:0.9rem;margin:20px 0 10px 0;">Or select from your saved designs:</p>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                <?php foreach ($designs as $design): ?>
                    <?php
                    $designKey = $design['design_key'] ?? ('design_' . $design['id']);
                    $thumbnail = $design['thumbnail_path'] ?? $design['filename'] ?? null;
                    $imagePath = $thumbnail ? (strpos($thumbnail, 'http') === 0 ? $thumbnail : '../uploads/' . ltrim($thumbnail, '/')) : null;
                    ?>
                    <label class="design-option">
                        <input type="radio" name="design_key" form="checkout-form" value="<?= esc($designKey); ?>">
                        <?php if ($imagePath): ?>
                            <img src="<?= esc($imagePath); ?>" alt="Design thumbnail">
                        <?php else: ?>
                            <div style="width:60px;height:60px;background:#333;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-image" style="color:#666;"></i>
                            </div>
                        <?php endif; ?>
                        <span><?= esc($designKey); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h2>4. Coupon Code (optional)</h2>
        <?php if ($appliedCoupon): ?>
            <div class="coupon-applied">
                <span><i class="fas fa-check-circle"></i> Coupon Applied: <strong><?= esc($appliedCoupon['code']); ?></strong> (-₹<?= number_format($discount, 2); ?>)</span>
                <form method="POST" style="display:inline;">
                    <?= csrf_field(); ?>
                    <button type="submit" name="remove_coupon" class="remove-btn">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </form>
            </div>
        <?php else: ?>
            <form method="POST">
                <?= csrf_field(); ?>
                <div class="coupon-input-group">
                    <input type="text" name="coupon_code" placeholder="Enter coupon code" required>
                    <button type="submit" name="apply_coupon" class="btn">
                        <i class="fas fa-tag"></i> Apply
                    </button>
                </div>
    </form>
        <?php endif; ?>

        <h3 style="margin-top: 32px;">Order Summary</h3>
        <?php if (empty($cartItems)): ?>
            <div class="success-message" style="text-align:center;">
                <i class="fas fa-info-circle"></i> Your cart is empty. Redirecting to cart...
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'cart.php';
                }, 2000);
            </script>
        <?php else: ?>
        <?php foreach ($cartItems as $item): ?>
            <div class="summary-item">
                <span><?= esc($item['name']); ?> × <?= (int) $item['quantity']; ?></span>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="color:#00bcd4;font-weight:600;">₹<?= number_format($item['quantity'] * ($item['display_price'] ?? (!empty($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$item['price'])), 2); ?></span>
                    <form method="POST" style="display:inline;">
                        <?= csrf_field(); ?>
                        <button type="submit" name="remove_item" value="<?= $item['product_id']; ?>" class="remove-btn" title="Remove item" style="padding:4px 8px;font-size:0.75rem;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <div class="summary-item" style="margin-top:20px;padding-top:16px;border-top:1px solid #222;">
            <span style="font-weight:600;color:#ccc;">Subtotal</span>
            <span style="color:#00bcd4;font-weight:600;">₹<?= number_format($subtotal, 2); ?></span>
        </div>
        <?php if ($appliedCoupon): ?>
            <div class="summary-item" style="color:#4caf50;">
                <span><i class="fas fa-tag"></i> Coupon (<?= esc($appliedCoupon['code']); ?>)</span>
                <span style="color:#4caf50;font-weight:600;">-₹<?= number_format($discount, 2); ?></span>
            </div>
        <?php endif; ?>
        <div class="total">
            <span>Total Payable</span>
            <span>₹<?= number_format($grandTotal, 2) ?></span>
        </div>
    </div>
</main>

<footer>
    <p>© 2025 Bottle. All Rights Reserved.</p>
</footer>

</body>
<script src="../assets/js/navbar.js" defer></script>
<script src="../assets/js/app.js" defer></script>
</html>