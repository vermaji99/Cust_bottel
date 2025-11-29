-- Quick Fix for Categories Index Error
-- Run these commands ONE BY ONE in phpMyAdmin
-- If you get "Duplicate column" or "Duplicate key" errors, that's fine - just skip that command!

-- Step 1: Add is_active column to categories table
-- If you get error "#1060 - Duplicate column name", skip this command - column already exists!
ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- Step 2: Add updated_at column to categories table  
-- If you get error "#1060 - Duplicate column name", skip this command - column already exists!
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Step 3: Create the index
-- If you get error "#1061 - Duplicate key name", skip this command - index already exists!
CREATE INDEX idx_categories_active ON categories(is_active);

-- Done! 
-- Note: Getting "duplicate" errors means those items already exist - that's perfectly fine!

