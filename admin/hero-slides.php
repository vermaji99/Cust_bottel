<?php
require_once __DIR__ . '/includes/bootstrap.php';
$admin = require_admin_auth();

$db = db();
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for security
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $title = $_POST['title'] ?? '';
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $slide_id = isset($_POST['slide_id']) ? (int)$_POST['slide_id'] : 0;

            // Handle image upload
            $image = null;
            $hasNewImage = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            
            if ($hasNewImage) {
                $file = $_FILES['image'];
                $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                
                if (!in_array($file['type'], $allowed)) {
                    $errors[] = 'Invalid image format. Only JPG, PNG, and WebP are allowed.';
                } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB
                    $errors[] = 'Image size should be less than 5MB.';
                } else {
                    // Check image dimensions
                    $imageInfo = getimagesize($file['tmp_name']);
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    $recommendedWidth = 1920;
                    $recommendedHeight = 1080;
                    
                    if ($width < 1600 || $height < 600) {
                        $errors[] = "Image dimensions too small. Recommended: {$recommendedWidth}×{$recommendedHeight}px (minimum: 1600×600px).";
                    } else {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'hero_' . time() . '_' . uniqid() . '.' . $ext;
                        $uploadPath = ADMIN_UPLOADS . '/' . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            $image = $filename;
                        } else {
                            $errors[] = 'Failed to upload image.';
                        }
                    }
                }
            } elseif ($_POST['action'] === 'edit' && $slide_id) {
                // Keep existing image if not uploading new one during edit
                $stmt = $db->prepare("SELECT image FROM hero_slides WHERE id = ?");
                $stmt->execute([$slide_id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                $image = $existing['image'] ?? null;
                
                if (!$image) {
                    $errors[] = 'Please upload an image.';
                }
            } else {
                // Adding new slide - image is required
                if ($_POST['action'] === 'add') {
                    $errors[] = 'Please upload an image.';
                }
            }

            if (empty($errors)) {
                if ($_POST['action'] === 'add') {
                    $stmt = $db->prepare("INSERT INTO hero_slides (title, image, display_order, is_active) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $image, $display_order, $is_active]);
                    $success = 'Hero slide added successfully!';
                } else {
                    // Edit existing slide - delete old image only if new one was uploaded
                    if ($hasNewImage && $image && $slide_id) {
                        $stmt = $db->prepare("SELECT image FROM hero_slides WHERE id = ?");
                        $stmt->execute([$slide_id]);
                        $old = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($old && $old['image'] && $old['image'] !== $image && file_exists(ADMIN_UPLOADS . '/' . $old['image'])) {
                            unlink(ADMIN_UPLOADS . '/' . $old['image']);
                        }
                    }
                    $stmt = $db->prepare("UPDATE hero_slides SET title = ?, image = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$title, $image, $display_order, $is_active, $slide_id]);
                    $success = 'Hero slide updated successfully!';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $slide_id = (int)$_POST['slide_id'];
            $stmt = $db->prepare("SELECT image FROM hero_slides WHERE id = ?");
            $stmt->execute([$slide_id]);
            $slide = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($slide) {
                if ($slide['image'] && file_exists(ADMIN_UPLOADS . '/' . $slide['image'])) {
                    unlink(ADMIN_UPLOADS . '/' . $slide['image']);
                }
                $stmt = $db->prepare("DELETE FROM hero_slides WHERE id = ?");
                $stmt->execute([$slide_id]);
                $success = 'Hero slide deleted successfully!';
            }
        } elseif ($_POST['action'] === 'reorder') {
            foreach ($_POST['order'] as $id => $order) {
                $stmt = $db->prepare("UPDATE hero_slides SET display_order = ? WHERE id = ?");
                $stmt->execute([$order, $id]);
            }
            $success = 'Order updated successfully!';
        } elseif ($_POST['action'] === 'toggle_active') {
            $slide_id = (int)$_POST['slide_id'];
            $stmt = $db->prepare("SELECT is_active FROM hero_slides WHERE id = ?");
            $stmt->execute([$slide_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($current) {
                $newStatus = $current['is_active'] ? 0 : 1;
                $stmt = $db->prepare("UPDATE hero_slides SET is_active = ? WHERE id = ?");
                $stmt->execute([$newStatus, $slide_id]);
                $success = $newStatus ? 'Slide activated successfully!' : 'Slide deactivated successfully!';
            }
        }
    }
}

// Get all slides
$stmt = $db->query("SELECT * FROM hero_slides ORDER BY display_order ASC, id ASC");
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get slide for editing
$editSlide = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM hero_slides WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editSlide = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editSlide ? 'Edit' : 'Manage' ?> Hero Slides | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-main.css">
    <script src="assets/js/admin.js" defer></script>
    <style>
        /* Hero Slides Page Specific Styles */
        .content {
            padding: 0;
        }
        
        .content-header {
            margin-bottom: 20px;
        }
        
        .content-header h1 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            color: var(--accent);
            margin-bottom: 10px;
        }
        
        .content-header p {
            color: var(--text-secondary);
            font-size: clamp(0.85rem, 2vw, 0.95rem);
        }
        
        .form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        
        .table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }
        
        .table img {
            max-width: 100%;
            height: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
            }
            
            .table-responsive {
                margin: 0 -1rem;
                padding: 0 1rem;
            }
            
            .table {
                font-size: clamp(0.8rem, 2vw, 0.9rem);
                min-width: 700px;
            }
            
            .table th,
            .table td {
                padding: clamp(0.75rem, 2vw, 1rem) clamp(0.5rem, 1.5vw, 0.75rem);
            }
            
            .table td img {
                width: clamp(80px, 20vw, 120px);
                height: clamp(40px, 10vw, 60px);
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .table {
                min-width: 600px;
                font-size: 0.75rem;
            }
            
            .table td img {
                width: 60px;
                height: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?= admin_sidebar('hero-slides') ?>
        <div class="admin-main">
            <?= admin_header('Hero Slides Management', 'Manage hero section background images') ?>
            
            <div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-images"></i> Hero Slides Management</h1>
        <p>Manage hero section background images. Recommended size: <strong>1920×1080px</strong> (minimum: 1600×600px)</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="card">
        <h2><?= $editSlide ? 'Edit Hero Slide' : 'Add New Hero Slide' ?></h2>
        <form method="POST" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editSlide ? 'edit' : 'add' ?>">
            <?php if ($editSlide): ?>
                <input type="hidden" name="slide_id" value="<?= $editSlide['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Title (Optional)</label>
                <input type="text" name="title" value="<?= htmlspecialchars($editSlide['title'] ?? '') ?>" placeholder="Hero Slide Title">
            </div>

            <div class="form-group">
                <label>Hero Image <span class="required">*</span></label>
                <?php if ($editSlide && $editSlide['image']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../admin/uploads/<?= htmlspecialchars($editSlide['image']) ?>" 
                             alt="Current" style="max-width: 100%; width: clamp(200px, 50vw, 300px); height: auto; border-radius: 8px; border: 1px solid #ddd;">
                        <p style="color: #666; font-size: clamp(0.8rem, 2vw, 0.9rem); margin-top: 5px;">Current image</p>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" class="form-control" <?= !$editSlide ? 'required' : '' ?>>
                <small style="color: #666; display: block; margin-top: 5px; font-size: clamp(0.8rem, 2vw, 0.85rem);">
                    <strong>Recommended:</strong> 1920×1080px (16:9 ratio)<br>
                    <strong>Minimum:</strong> 1600×600px<br>
                    <strong>Max size:</strong> 5MB | Formats: JPG, PNG, WebP
                </small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" value="<?= $editSlide['display_order'] ?? 0 ?>" min="0">
                    <small>Lower numbers appear first</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" <?= ($editSlide['is_active'] ?? 1) ? 'checked' : '' ?>>
                        Active
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $editSlide ? 'Update' : 'Add' ?> Slide
                </button>
                <?php if ($editSlide): ?>
                    <a href="hero-slides.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Slides List -->
    <div class="card">
        <h2>All Hero Slides (<?= count($slides) ?>)</h2>
        <?php if (empty($slides)): ?>
            <p style="text-align: center; color: #999; padding: 40px;">No hero slides found. Add your first slide above.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Title</th>
                            <th>Image</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $slide): ?>
                            <tr>
                                <td>
                                    <img src="../admin/uploads/<?= htmlspecialchars($slide['image']) ?>" 
                                         alt="Preview" 
                                         style="width: clamp(80px, 20vw, 120px); height: clamp(40px, 10vw, 60px); object-fit: cover; border-radius: 4px;">
                                </td>
                                <td><?= htmlspecialchars($slide['title'] ?: 'Untitled') ?></td>
                                <td><small><?= htmlspecialchars($slide['image']) ?></small></td>
                                <td><?= $slide['display_order'] ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="slide_id" value="<?= $slide['id'] ?>">
                                        <button type="submit" class="badge <?= $slide['is_active'] ? 'badge-success' : 'badge-secondary' ?>" style="border: none; cursor: pointer; padding: 6px 12px; border-radius: 4px; font-size: 0.85rem; transition: all 0.2s;" title="Click to <?= $slide['is_active'] ? 'deactivate' : 'activate' ?>">
                                            <?= $slide['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?= $slide['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this slide?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="slide_id" value="<?= $slide['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

