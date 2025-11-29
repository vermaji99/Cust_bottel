# Simple Database Setup Guide

## Problem Getting Errors?

If you're getting errors like:
- `#1060 - Duplicate column name`
- `#1061 - Duplicate key name`
- `#1044 - Access denied`

## Solution: Use the Simple Fix File

Run this file instead - it's much simpler:

```
database/SIMPLE_FIX.sql
```

### How to Use:

1. Open phpMyAdmin
2. Select your database
3. Click on "SQL" tab
4. Copy and paste **ONE COMMAND AT A TIME** from `SIMPLE_FIX.sql`
5. If you get a "Duplicate" error, **that's fine** - just skip that command and move to the next one

### What Each Error Means:

| Error | Meaning | What to Do |
|-------|---------|------------|
| `#1060 - Duplicate column name` | Column already exists | Skip that command, continue |
| `#1061 - Duplicate key name` | Index already exists | Skip that command, continue |
| `#1044 - Access denied` | Permission issue | Use SIMPLE_FIX.sql instead |

## Quick Commands (Copy-Paste Ready)

Just run these one by one - skip any that give duplicate errors:

```sql
-- Categories
ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1;
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
CREATE INDEX idx_categories_active ON categories(is_active);

-- Users  
ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN blocked_at DATETIME NULL;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL;
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);

-- Products
ALTER TABLE products ADD COLUMN stock INT(11) DEFAULT 100;
ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1;
CREATE INDEX idx_products_active ON products(is_active);

-- Orders
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user ON orders(user_id);
```

That's it! Don't worry about duplicate errors - they just mean those items already exist.

