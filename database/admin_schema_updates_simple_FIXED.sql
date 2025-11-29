-- Admin Website Database Updates (Safe Version)
-- This version checks if columns exist before adding them
-- Run this entire file - it will skip columns that already exist

-- Categories Table (create if doesn't exist)
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add columns to categories only if they don't exist
SET @dbname = DATABASE();
SET @sql = '';

-- Check and add is_active column
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'categories' 
  AND COLUMN_NAME = 'is_active';

SET @sql = IF(@exists = 0, 
    'ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1', 
    'SELECT "Column is_active already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add updated_at column
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'categories' 
  AND COLUMN_NAME = 'updated_at';

SET @sql = IF(@exists = 0, 
    'ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 
    'SELECT "Column updated_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- User Status Columns
-- is_blocked
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'is_blocked';

SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0', 
    'SELECT "Column is_blocked already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- blocked_at
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'blocked_at';

SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN blocked_at DATETIME NULL', 
    'SELECT "Column blocked_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- blocked_reason
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'users' 
  AND COLUMN_NAME = 'blocked_reason';

SET @sql = IF(@exists = 0, 
    'ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL', 
    'SELECT "Column blocked_reason already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Products table columns
-- stock
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'products' 
  AND COLUMN_NAME = 'stock';

SET @sql = IF(@exists = 0, 
    'ALTER TABLE products ADD COLUMN stock INT(11) DEFAULT 100', 
    'SELECT "Column stock already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- is_active
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'products' 
  AND COLUMN_NAME = 'is_active';

SET @sql = IF(@exists = 0, 
    'ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1', 
    'SELECT "Column is_active already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Indexes (only create if they don't exist)
-- Users indexes
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'users' 
  AND INDEX_NAME = 'idx_users_role';

SET @sql = IF(@exists = 0, 
    'CREATE INDEX idx_users_role ON users(role)', 
    'SELECT "Index idx_users_role already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'users' 
  AND INDEX_NAME = 'idx_users_email';

SET @sql = IF(@exists = 0, 
    'CREATE INDEX idx_users_email ON users(email)', 
    'SELECT "Index idx_users_email already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Orders indexes
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'orders' 
  AND INDEX_NAME = 'idx_orders_status';

SET @sql = IF(@exists = 0, 
    'CREATE INDEX idx_orders_status ON orders(status)', 
    'SELECT "Index idx_orders_status already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'orders' 
  AND INDEX_NAME = 'idx_orders_user';

SET @sql = IF(@exists = 0, 
    'CREATE INDEX idx_orders_user ON orders(user_id)', 
    'SELECT "Index idx_orders_user already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Products indexes
SELECT COUNT(*) INTO @exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'products' 
  AND INDEX_NAME = 'idx_products_active';

SET @sql = IF(@exists = 0, 
    'CREATE INDEX idx_products_active ON products(is_active)', 
    'SELECT "Index idx_products_active already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Categories indexes (only if is_active column exists)
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'categories' 
  AND COLUMN_NAME = 'is_active';

SELECT COUNT(*) INTO @idx_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = 'categories' 
  AND INDEX_NAME = 'idx_categories_active';

SET @sql = IF(@col_exists > 0 AND @idx_exists = 0, 
    'CREATE INDEX idx_categories_active ON categories(is_active)', 
    'SELECT "Index already exists or column missing" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default admin user (password: admin123 - CHANGE AFTER FIRST LOGIN!)
-- Only runs if email doesn't exist
INSERT INTO users (name, email, password, role, email_verified_at) 
SELECT 'Admin User', 'admin@bottel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@bottel.com');

-- Done! All columns and indexes have been added (or already existed)

