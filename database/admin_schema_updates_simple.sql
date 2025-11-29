-- Admin Website Database Updates (Simple Version)
-- Run this SQL file in phpMyAdmin or MySQL console
-- If you get errors about columns already existing, that's fine - just skip those lines

-- Categories Table (create if doesn't exist)
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- IMPORTANT: If you get "Duplicate column name" errors,
-- those columns already exist - just SKIP those commands!
-- ============================================================

-- Add missing columns to categories table
-- Skip if you get "Duplicate column name" error
ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1;
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- User Status Columns
-- Skip any line that gives "Duplicate column name" error
ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN blocked_at DATETIME NULL;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL;

-- Products table columns
-- Skip any line that gives "Duplicate column name" error
ALTER TABLE products ADD COLUMN stock INT(11) DEFAULT 100;
ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- Indexes for better performance (skip if index already exists)
-- Note: If you get errors about indexes already existing, that's fine - just skip those lines
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_categories_active ON categories(is_active);

-- Insert default admin user (password: admin123 - CHANGE AFTER FIRST LOGIN!)
-- Only runs if email doesn't exist
INSERT INTO users (name, email, password, role, email_verified_at) 
SELECT 'Admin User', 'admin@bottel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@bottel.com');

