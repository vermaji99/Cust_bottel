-- Create Reviews Table for Product Reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `admin_replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `rating` (`rating`),
  UNIQUE KEY `unique_user_product_review` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign keys if they don't exist (might fail if tables don't exist)
SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'reviews' 
    AND CONSTRAINT_NAME = 'reviews_ibfk_1'
);

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE reviews ADD CONSTRAINT reviews_ibfk_1 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE',
    'SELECT "Foreign key already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists2 = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'reviews' 
    AND CONSTRAINT_NAME = 'reviews_ibfk_2'
);

SET @sql2 = IF(@fk_exists2 = 0, 
    'ALTER TABLE reviews ADD CONSTRAINT reviews_ibfk_2 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT "Foreign key already exists"'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Index for faster average rating queries (ignore error if exists)
CREATE INDEX IF NOT EXISTS idx_product_rating ON reviews(product_id, rating);

