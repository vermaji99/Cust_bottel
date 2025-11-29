<?php
require __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$message = '';
$error = '';

// Handle Add/Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Security token expired.';
    } else {
        $id = (int) ($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($name)));
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$name) {
            $error = 'Category name is required.';
        } else {
            try {
                // Check for duplicate slug
                $checkStmt = db()->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
                $checkStmt->execute([$slug, $id]);
                if ($checkStmt->fetch()) {
                    $error = 'A category with this name already exists.';
                } else {
                    if ($id > 0) {
                        $stmt = db()->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, is_active = ? WHERE id = ?');
                        $stmt->execute([$name, $slug, $description, $is_active, $id]);
                        $message = 'Category updated successfully!';
                    } else {
                        $stmt = db()->prepare('INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$name, $slug, $description, $is_active]);
                        $message = 'Category added successfully!';
                    }
                }
            } catch (Throwable $e) {
                error_log('Category save error: ' . $e->getMessage());
                $error = 'Failed to save category. Please try again.';
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if (verify_csrf($_GET['csrf_token'] ?? null)) {
        try {
            // Check if category is used in products
            $checkStmt = db()->prepare('SELECT COUNT(*) FROM products WHERE category = (SELECT name FROM categories WHERE id = ?)');
            $checkStmt->execute([$id]);
            $productCount = (int) $checkStmt->fetchColumn();
            
            if ($productCount > 0) {
                $error = "Cannot delete category. It is used by {$productCount} product(s).";
            } else {
                db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                $message = 'Category deleted successfully!';
            }
        } catch (Throwable $e) {
            $error = 'Failed to delete category.';
        }
    } else {
        $error = 'Invalid request.';
    }
}

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all categories
$search = trim($_GET['search'] ?? '');
$where = $search ? 'WHERE name LIKE ?' : '';
$params = $search ? ["%{$search}%"] : [];

$categoriesStmt = db()->prepare("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category = c.name) as product_count FROM categories c $where ORDER BY c.name");
$categoriesStmt->execute($params);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editCategory ? 'Edit' : 'Manage' ?> Categories | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('categories') ?>
        
        <div class="admin-main">
            <?= admin_header($editCategory ? 'Edit Category' : 'Manage Categories', 'Organize products into categories') ?>
            
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

                <!-- Category Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?= $editCategory ? 'edit' : 'plus' ?>"></i> 
                            <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
                        </h3>
                        <?php if ($editCategory): ?>
                            <a href="categories.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                    <form method="POST" style="padding: 20px;">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?? 0 ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="name">Category Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?= esc($editCategory['name'] ?? '') ?>" required>
                                <small style="color: #777; font-size: 0.85rem;">Slug will be auto-generated</small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?= ($editCategory['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    Category is Active
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?= esc($editCategory['description'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $editCategory ? 'Update Category' : 'Add Category' ?>
                        </button>
                    </form>
                </div>

                <!-- Categories List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> All Categories</h3>
                        <form method="GET" class="filter-bar" style="margin: 0; padding: 0;">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search categories..." 
                                       value="<?= esc($search) ?>" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
                            <?php if ($search): ?>
                                <a href="categories.php" class="btn btn-secondary btn-sm">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: #777;">
                                            <i class="fas fa-folder-open" style="font-size: 3rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                            No categories found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><?= $cat['id'] ?></td>
                                            <td><strong><?= esc($cat['name']) ?></strong></td>
                                            <td><small style="color: #777;"><?= esc($cat['slug']) ?></small></td>
                                            <td>
                                                <span class="badge badge-info"><?= (int)$cat['product_count'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $cat['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td><?= date('d M Y', strtotime($cat['created_at'])) ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a href="?edit=<?= $cat['id'] ?>" class="action-btn action-btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?= $cat['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                                       class="action-btn action-btn-delete" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure? This will fail if category has products.')">
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
</body>
</html>

