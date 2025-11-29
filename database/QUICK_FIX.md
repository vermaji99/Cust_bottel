# Quick Fix for Duplicate Column Errors

## Problem
You're getting errors like:
```
#1060 - Duplicate column name 'is_blocked'
```

## Solution Options

### Option 1: Use the Safe Version (Recommended)
Run this file instead - it checks if columns exist before adding them:
```
database/admin_schema_updates_simple_FIXED.sql
```

This file will automatically skip columns that already exist!

### Option 2: Skip Already-Existing Columns Manually

Since `is_blocked` already exists, just **skip those commands** and run only the missing ones:

```sql
-- Skip this (already exists):
-- ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0;

-- Run these only if they're missing:
ALTER TABLE users ADD COLUMN blocked_at DATETIME NULL;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL;

-- Products columns (skip if they exist):
ALTER TABLE products ADD COLUMN stock INT(11) DEFAULT 100;
ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- Categories columns:
ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1;
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Indexes:
CREATE INDEX idx_categories_active ON categories(is_active);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_products_active ON products(is_active);
```

### Option 3: Check What's Missing

Run this to see which columns are missing:

```sql
-- Check users table
DESCRIBE users;

-- Check products table  
DESCRIBE products;

-- Check categories table
DESCRIBE categories;
```

Then only add the columns that are missing.

## Recommended Action

**Just use the FIXED version:**
```
Run: admin_schema_updates_simple_FIXED.sql
```

It handles everything automatically! 🎉

