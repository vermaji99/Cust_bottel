<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$message = '';
$error = '';

// Ensure product_images table exists
function ensureProductImagesTable() {
    try {
        $db = db();
        $db->exec("
            CREATE TABLE IF NOT EXISTS `product_images` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `product_id` INT(11) NOT NULL,
              `image_path` VARCHAR(255) NOT NULL,
              `image_order` INT(11) DEFAULT 0,
              `is_primary` TINYINT(1) DEFAULT 0,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
              PRIMARY KEY (`id`),
              KEY `product_id` (`product_id`),
              KEY `image_order` (`image_order`),
              KEY `is_primary` (`is_primary`),
              CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    } catch (Exception $e) {
        // Table might already exist or foreign key constraint might fail
        // Try without foreign key constraint
        try {
            $db = db();
            $db->exec("
                CREATE TABLE IF NOT EXISTS `product_images` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `product_id` INT(11) NOT NULL,
                  `image_path` VARCHAR(255) NOT NULL,
                  `image_order` INT(11) DEFAULT 0,
                  `is_primary` TINYINT(1) DEFAULT 0,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                  PRIMARY KEY (`id`),
                  KEY `product_id` (`product_id`),
                  KEY `image_order` (`image_order`),
                  KEY `is_primary` (`is_primary`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        } catch (Exception $e2) {
            error_log('Failed to create product_images table: ' . $e2->getMessage());
        }
    }
}

// Ensure table exists
ensureProductImagesTable();

// Handle Delete Product Image
if (isset($_GET['delete_image'])) {
    $imageId = (int) $_GET['delete_image'];
    $productId = (int) ($_GET['product_id'] ?? 0);
    if (verify_csrf($_GET['csrf_token'] ?? null) && $productId > 0) {
        try {
            // Get image path before deleting
            $imgStmt = db()->prepare('SELECT image_path FROM product_images WHERE id = ? AND product_id = ?');
            $imgStmt->execute([$imageId, $productId]);
            $imgPath = $imgStmt->fetchColumn();
            
            if ($imgPath) {
                // Delete from database
                db()->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?')->execute([$imageId, $productId]);
                
                // Delete file
                $filePath = ADMIN_UPLOADS . '/' . $imgPath;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                
                $message = 'Image deleted successfully!';
            }
        } catch (Throwable $e) {
            error_log('Image delete error: ' . $e->getMessage());
            $error = 'Failed to delete image.';
        }
    }
}

// Handle Set Primary Image
if (isset($_GET['set_primary'])) {
    $imageId = (int) $_GET['set_primary'];
    $productId = (int) ($_GET['product_id'] ?? 0);
    if (verify_csrf($_GET['csrf_token'] ?? null) && $productId > 0) {
        try {
            // Remove primary from all images of this product
            db()->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
            // Set this image as primary
            db()->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?')->execute([$imageId, $productId]);
            $message = 'Primary image updated successfully!';
        } catch (Throwable $e) {
            error_log('Set primary error: ' . $e->getMessage());
            $error = 'Failed to set primary image.';
        }
    }
}

// Handle Add/Update Product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired.';
    } else {
        $id = (int) ($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($name)));
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$name || !$price) {
            $error = 'Name and price are required.';
        } else {
            try {
                // Handle main image upload (for backward compatibility)
                $image = $_POST['existing_image'] ?? '';
                if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) {
                        $error = 'Invalid image format. Allowed: ' . implode(', ', $allowed);
                    } else {
                        $image = time() . '_' . uniqid() . '.' . $ext;
                        $uploadPath = ADMIN_UPLOADS . '/' . $image;
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                            $error = 'Failed to upload image.';
                        }
                    }
                }

                if (!$error) {
                    if ($id > 0) {
                        // Update existing product
                        if ($image) {
                            $stmt = db()->prepare('UPDATE products SET name = ?, slug = ?, description = ?, price = ?, category = (SELECT name FROM categories WHERE id = ?), image = ?, stock = ?, is_active = ? WHERE id = ?');
                            $stmt->execute([$name, $slug, $description, $price, $category_id, $image, $stock, $is_active, $id]);
                            
                            // Add to product_images if not exists
                            if ($image) {
                                try {
                                    $checkStmt = db()->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND image_path = ?');
                                    $checkStmt->execute([$id, $image]);
                                    if ($checkStmt->fetchColumn() == 0) {
                                        // Check if product has any primary image
                                        $primaryCheck = db()->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1');
                                        $primaryCheck->execute([$id]);
                                        $isPrimary = $primaryCheck->fetchColumn() == 0 ? 1 : 0;
                                        
                                        $maxOrderStmt = db()->prepare('SELECT COALESCE(MAX(image_order), 0) FROM product_images WHERE product_id = ?');
                                        $maxOrderStmt->execute([$id]);
                                        $nextOrder = (int)$maxOrderStmt->fetchColumn() + 1;
                                        
                                        db()->prepare('INSERT INTO product_images (product_id, image_path, image_order, is_primary) VALUES (?, ?, ?, ?)')
                                            ->execute([$id, $image, $nextOrder, $isPrimary]);
                                    }
                                } catch (Exception $e) {
                                    error_log('Failed to add main image to product_images: ' . $e->getMessage());
                                    // Don't fail the whole operation if this fails
                                }
                            }
                        } else {
                            $stmt = db()->prepare('UPDATE products SET name = ?, slug = ?, description = ?, price = ?, category = (SELECT name FROM categories WHERE id = ?), stock = ?, is_active = ? WHERE id = ?');
                            $stmt->execute([$name, $slug, $description, $price, $category_id, $stock, $is_active, $id]);
                        }
                        $message = 'Product updated successfully!';
                    } else {
                        // Insert new product
                        $category_name = '';
                        if ($category_id > 0) {
                            $catStmt = db()->prepare('SELECT name FROM categories WHERE id = ?');
                            $catStmt->execute([$category_id]);
                            $category_name = $catStmt->fetchColumn() ?: '';
                        }
                        
                        $stmt = db()->prepare('INSERT INTO products (name, slug, description, price, category, image, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$name, $slug, $description, $price, $category_name, $image, $stock, $is_active]);
                        $newProductId = db()->lastInsertId();
                        
                        // Add main image to product_images table
                        if ($image) {
                            try {
                                db()->prepare('INSERT INTO product_images (product_id, image_path, image_order, is_primary) VALUES (?, ?, 0, 1)')
                                    ->execute([$newProductId, $image]);
                            } catch (Exception $e) {
                                error_log('Failed to add main image to product_images: ' . $e->getMessage());
                                // Don't fail the whole operation if this fails
                            }
                        }
                        
                        $message = 'Product added successfully!';
                        $id = $newProductId; // Set id for additional images processing
                    }
                    
                    // Handle multiple image uploads (for both new and existing products)
                    if ($id > 0 && isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'])) {
                        // Handle both single and multiple file uploads
                        $files = [];
                        if (is_array($_FILES['additional_images']['name'])) {
                            foreach ($_FILES['additional_images']['name'] as $key => $name) {
                                if (!empty($name)) {
                                    $files[] = [
                                        'name' => $name,
                                        'type' => $_FILES['additional_images']['type'][$key],
                                        'tmp_name' => $_FILES['additional_images']['tmp_name'][$key],
                                        'error' => $_FILES['additional_images']['error'][$key],
                                        'size' => $_FILES['additional_images']['size'][$key]
                                    ];
                                }
                            }
                        } else {
                            // Single file upload
                            if (!empty($_FILES['additional_images']['name'])) {
                                $files[] = $_FILES['additional_images'];
                            }
                        }
                        
                        if (!empty($files)) {
                            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                            $uploadedCount = 0;
                            $uploadErrors = [];
                            
                            foreach ($files as $key => $file) {
                                if ($file['error'] === UPLOAD_ERR_OK) {
                                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                    if (in_array($ext, $allowed)) {
                                        $newImageName = time() . '_' . uniqid() . '_' . $key . '.' . $ext;
                                        $uploadPath = ADMIN_UPLOADS . '/' . $newImageName;
                                        
                                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                                            try {
                                                // Get max order
                                                $maxOrderStmt = db()->prepare('SELECT COALESCE(MAX(image_order), 0) FROM product_images WHERE product_id = ?');
                                                $maxOrderStmt->execute([$id]);
                                                $nextOrder = (int)$maxOrderStmt->fetchColumn() + 1;
                                                
                                                db()->prepare('INSERT INTO product_images (product_id, image_path, image_order, is_primary) VALUES (?, ?, ?, 0)')
                                                    ->execute([$id, $newImageName, $nextOrder]);
                                                $uploadedCount++;
                                            } catch (Exception $e) {
                                                error_log('Failed to save image to database: ' . $e->getMessage());
                                                // Delete uploaded file if database insert fails
                                                if (file_exists($uploadPath)) {
                                                    @unlink($uploadPath);
                                                }
                                                $uploadErrors[] = $file['name'];
                                            }
                                        } else {
                                            $uploadErrors[] = $file['name'] . ' (upload failed)';
                                        }
                                    } else {
                                        $uploadErrors[] = $file['name'] . ' (invalid format)';
                                    }
                                } else {
                                    $uploadErrors[] = $file['name'] . ' (error code: ' . $file['error'] . ')';
                                }
                            }
                            
                            if ($uploadedCount > 0) {
                                $message .= ' ' . $uploadedCount . ' additional image(s) uploaded!';
                            }
                            if (!empty($uploadErrors)) {
                                $error .= ' Some images failed to upload: ' . implode(', ', array_slice($uploadErrors, 0, 3));
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('Product save error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                $error = 'Failed to save product: ' . htmlspecialchars($e->getMessage());
                // If it's a database error, provide more helpful message
                if (strpos($e->getMessage(), 'product_images') !== false) {
                    $error = 'Database error. Please ensure the product_images table exists. Check error logs for details.';
                }
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if (verify_csrf($_GET['csrf_token'] ?? null)) {
        try {
            db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            $message = 'Product deleted successfully!';
        } catch (Throwable $e) {
            $error = 'Failed to delete product.';
        }
    } else {
        $error = 'Invalid request.';
    }
}

// Get product for editing
$editProduct = null;
$productImages = [];
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get category ID if exists
    if ($editProduct && $editProduct['category']) {
        $catStmt = db()->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $catStmt->execute([$editProduct['category']]);
        $editProduct['category_id'] = $catStmt->fetchColumn() ?: 0;
    }
    
    // Fetch all product images
    if ($editProduct) {
        try {
            $imgStmt = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, image_order ASC, id ASC');
            $imgStmt->execute([$id]);
            $productImages = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Table might not exist yet
            $productImages = [];
        }
        
        // If no images in product_images, add main image
        if (empty($productImages) && !empty($editProduct['image'])) {
            $productImages = [
                ['id' => 0, 'image_path' => $editProduct['image'], 'is_primary' => 1, 'image_order' => 0]
            ];
        }
    }
}

// Fetch all products with pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$categoryFilter = (int) ($_GET['category'] ?? 0);

$where = [];
$params = [];

if ($search) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($categoryFilter > 0) {
    $where[] = 'c.id = ?';
    $params[] = $categoryFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category = c.name $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$productsStmt = db()->prepare("
    SELECT p.*, c.id as category_id
    FROM products p
    LEFT JOIN categories c ON p.category = c.name
    $whereSql
    ORDER BY p.id DESC
    LIMIT $perPage OFFSET $offset
");
$productsStmt->execute($params);
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for dropdown
$categories = db()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editProduct ? 'Edit' : 'Manage' ?> Products | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <script src="assets/js/admin.js" defer></script>
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('products') ?>
        
        <div class="admin-main">
            <?= admin_header($editProduct ? 'Edit Product' : 'Manage Products', 'Add, edit, or delete products') ?>
            
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

                <!-- Product Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?= $editProduct ? 'edit' : 'plus' ?>"></i> 
                            <?= $editProduct ? 'Edit Product' : 'Add New Product' ?>
                        </h3>
                        <?php if ($editProduct): ?>
                            <a href="products.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" style="padding: 20px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?? 0 ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="name">Product Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?= esc($editProduct['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price (₹) *</label>
                                <input type="number" id="price" name="price" class="form-control" 
                                       step="0.01" min="0" value="<?= esc($editProduct['price'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="0">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" 
                                                <?= ($editProduct['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                            <?= esc($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock">Stock Quantity</label>
                                <input type="number" id="stock" name="stock" class="form-control" 
                                       min="0" value="<?= esc($editProduct['stock'] ?? 100) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4"><?= esc($editProduct['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Main Product Image</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <?php if ($editProduct && $editProduct['image']): ?>
                                <input type="hidden" name="existing_image" value="<?= esc($editProduct['image']) ?>">
                                <img src="uploads/<?= esc($editProduct['image']) ?>" alt="" class="img-preview" style="margin-top: 10px; max-width: 200px; border-radius: 8px;">
                                <small style="color: #777; display: block; margin-top: 5px;">Leave empty to keep current image</small>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($editProduct): ?>
                        <div class="form-group">
                            <label>Additional Product Images (Different Angles)</label>
                            <input type="file" id="additional_images" name="additional_images[]" class="form-control" accept="image/*" multiple>
                            <small style="color: #777; display: block; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> Select multiple images to show product from different angles. Users can view these in the product page.
                            </small>
                            
                            <?php if (!empty($productImages)): ?>
                            <div style="margin-top: 20px;">
                                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #fff;">Current Product Images:</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px;">
                                    <?php foreach ($productImages as $img): ?>
                                    <div style="position: relative; border: 2px solid <?= $img['is_primary'] ? '#00bcd4' : '#333' ?>; border-radius: 8px; overflow: hidden; background: #1a1a1a;">
                                        <img src="uploads/<?= esc($img['image_path']) ?>" alt="" style="width: 100%; height: 120px; object-fit: cover; display: block;">
                                        <div style="padding: 8px; background: #0f0f0f;">
                                            <?php if ($img['is_primary']): ?>
                                            <span style="font-size: 10px; color: #00bcd4; font-weight: 600;">
                                                <i class="fas fa-star"></i> Primary
                                            </span>
                                            <?php else: ?>
                                            <a href="?set_primary=<?= $img['id'] ?>&product_id=<?= $editProduct['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                               style="font-size: 10px; color: #999; text-decoration: none; display: inline-block; margin-bottom: 4px;"
                                               title="Set as Primary">
                                                <i class="far fa-star"></i> Set Primary
                                            </a>
                                            <?php endif; ?>
                                            <br>
                                            <?php if ($img['id'] > 0): ?>
                                            <a href="?delete_image=<?= $img['id'] ?>&product_id=<?= $editProduct['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                               onclick="return confirm('Are you sure you want to delete this image?')"
                                               style="font-size: 10px; color: #ff4444; text-decoration: none;"
                                               title="Delete Image">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" value="1" 
                                       <?= ($editProduct['is_active'] ?? 1) ? 'checked' : '' ?>>
                                Product is Active
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $editProduct ? 'Update Product' : 'Add Product' ?>
                        </button>
                    </form>
                </div>

                <!-- Products List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> All Products</h3>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-bar" style="padding: 20px 20px 0;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search products..." 
                                   value="<?= esc($search) ?>" onchange="this.form.submit()" class="form-control">
                        </div>
                        <select name="category" class="form-control" onchange="this.form.submit()" style="max-width: 200px;">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= esc($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <form method="GET" style="display: contents;">
                        <input type="hidden" name="page" value="1">
                    </form>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px; color: #777;">
                                            <i class="fas fa-box-open" style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                            No products found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td><?= $p['id'] ?></td>
                                            <td>
                                                <?php if ($p['image']): ?>
                                                    <img src="uploads/<?= esc($p['image']) ?>" alt="" class="img-thumb">
                                                <?php else: ?>
                                                    <div class="img-thumb" style="background: #1a1a1a; display: flex; align-items: center; justify-content: center; color: #777;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= esc($p['name']) ?></strong><br>
                                                <small style="color: #777;"><?= esc($p['slug']) ?></small>
                                            </td>
                                            <td><?= esc($p['category'] ?? '-') ?></td>
                                            <td>₹<?= number_format($p['price'], 2) ?></td>
                                            <td>
                                                <span class="badge <?= $p['stock'] > 10 ? 'badge-success' : ($p['stock'] > 0 ? 'badge-warning' : 'badge-danger') ?>">
                                                    <?= (int)$p['stock'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="?edit=<?= $p['id'] ?>" class="action-btn action-btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                                       class="action-btn action-btn-delete" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this product?')">
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
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" 
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
