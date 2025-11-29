-- Simple Fix for Database Setup
-- Run these commands ONE BY ONE in phpMyAdmin
-- If you get "Duplicate" errors, that means the item already exists - just skip that command!

-- ============================================================
-- CATEGORIES TABLE FIXES
-- ============================================================

-- Add is_active column (skip if you get duplicate error)
ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- Add updated_at column (skip if you get duplicate error)
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create index (skip if you get duplicate error)
CREATE INDEX idx_categories_active ON categories(is_active);

-- ============================================================
-- USERS TABLE FIXES
-- ============================================================

-- Add blocking columns (skip if you get duplicate errors)
ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN blocked_at DATETIME NULL;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL;

-- Create indexes (skip if you get duplicate errors)
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);

-- ============================================================
-- PRODUCTS TABLE FIXES
-- ============================================================

-- Add columns (skip if you get duplicate errors)
ALTER TABLE products ADD COLUMN stock INT(11) DEFAULT 100;
ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- Create index (skip if you get duplicate error)
CREATE INDEX idx_products_active ON products(is_active);

-- ============================================================
-- ORDERS TABLE INDEXES
-- ============================================================

-- Create indexes (skip if you get duplicate errors)
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user ON orders(user_id);

-- ============================================================
-- DEFAULT ADMIN USER
-- ============================================================

-- Insert admin user (will only insert if email doesn't exist)
INSERT INTO users (name, email, password, role, email_verified_at) 
SELECT 'Admin User', 'admin@bottel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@bottel.com');

-- ============================================================
-- DONE!
-- ============================================================
-- If you got some "duplicate" errors, that's normal - those items already existed.
-- Your database is now ready for the admin panel!

