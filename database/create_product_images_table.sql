-- ============================================
-- SQL for Product Multiple Images (Different Angles)
-- ============================================

-- Create product_images table for storing multiple angle views
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrate existing product images to product_images table
-- This will copy all existing images from products.image to product_images
INSERT INTO `product_images` (`product_id`, `image_path`, `image_order`, `is_primary`)
SELECT `id`, `image`, 0, 1 
FROM `products` 
WHERE `image` IS NOT NULL AND `image` != ''
ON DUPLICATE KEY UPDATE `image_path` = VALUES(`image_path`);

-- Note: After running this, you can add more images for each product
-- Example:
-- INSERT INTO product_images (product_id, image_path, image_order, is_primary) VALUES
-- (1, 'product_1_front.jpg', 0, 1),
-- (1, 'product_1_angle.jpg', 1, 0),
-- (1, 'product_1_side.jpg', 2, 0),
-- (1, 'product_1_top.jpg', 3, 0);

