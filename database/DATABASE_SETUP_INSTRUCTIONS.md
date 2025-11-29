# Database Setup Instructions

## Quick Fix for Categories Index Error

If you got this error:
```
#1072 - Key column 'is_active' doesn't exist in table
```

### Solution:

The `categories` table exists but doesn't have the `is_active` column. Run these SQL commands **one by one** in phpMyAdmin:

```sql
-- 1. First, add the is_active column
ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1;

-- 2. Add updated_at column if needed
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 3. Now create the index
CREATE INDEX idx_categories_active ON categories(is_active);
```

**Note:** If you get "Duplicate column name" errors, that's fine - it means the column already exists. Just skip that command and continue.

## Complete Database Setup

### Option 1: Simple Setup (Recommended)

1. Run `admin_schema_updates_simple.sql` in phpMyAdmin
2. If you get errors about duplicate columns/indexes, **ignore them** - it means they already exist
3. If you get the categories index error, run the fix commands above

### Option 2: Automatic Setup (Advanced)

1. Run `admin_schema_updates.sql` - it checks for existing columns/indexes before creating them
2. This version handles existing columns automatically

## Step-by-Step Manual Setup

If you prefer to set up manually:

### 1. Categories Table
```sql
-- Check if table exists first
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. Add is_active column if table already exists
```sql
-- Run this if categories table exists but doesn't have is_active
ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1;
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

### 3. User Blocking Columns
```sql
ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN blocked_at DATETIME NULL;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL;
```

### 4. Product Columns
```sql
ALTER TABLE products ADD COLUMN stock INT(11) DEFAULT 100;
ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1;
```

### 5. Indexes
```sql
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_categories_active ON categories(is_active);
```

### 6. Default Admin User
```sql
INSERT INTO users (name, email, password, role, email_verified_at) 
SELECT 'Admin User', 'admin@bottel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@bottel.com');
```

## Troubleshooting

### Error: Duplicate column name
- **Meaning:** Column already exists
- **Action:** Skip that command, continue with next

### Error: Duplicate key name (for indexes)
- **Meaning:** Index already exists
- **Action:** Skip that command, continue with next

### Error: Table doesn't exist
- **Meaning:** Main tables are missing
- **Action:** Run your main database schema first (`schema.sql`)

## Verification

After setup, verify everything worked:

```sql
-- Check categories table
DESCRIBE categories;

-- Check users table has blocking columns
DESCRIBE users;

-- Check products table
DESCRIBE products;

-- Check admin user exists
SELECT * FROM users WHERE role = 'admin';
```

All good? You're ready to use the admin panel! 🎉

