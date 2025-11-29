# Bottel Admin Website

A comprehensive standalone admin management system for the Bottel e-commerce platform.

## Features

✅ **Separate Admin Authentication** - Independent login/registration system
✅ **Role-Based Access Control** - Admin and Staff roles
✅ **Dashboard Analytics** - Revenue charts, stats, and insights
✅ **Product Management** - Full CRUD with stock management and image uploads
✅ **Category Management** - Organize products into categories
✅ **Orders Management** - View, filter, and update order statuses with timeline
✅ **Users Management** - List, block/unblock, and delete users
✅ **Coupons Management** - Create and manage discount coupons
✅ **Settings** - Admin profile management

## Installation

### 1. Database Setup

Run the database migration file to add required tables and columns:

```sql
-- Run this file in phpMyAdmin or MySQL console
database/admin_schema_updates.sql
```

This will create:
- `categories` table
- Add `is_blocked`, `blocked_at`, `blocked_reason` columns to `users` table
- Add `stock` and `is_active` columns to `products` table (if not exist)
- Create necessary indexes

### 2. Default Admin Account

After running the migration, you can login with:

- **Email:** admin@bottel.com
- **Password:** admin123

⚠️ **Important:** Change the password immediately after first login!

### 3. File Structure

```
admin/
├── includes/
│   ├── bootstrap.php      # Admin bootstrap
│   ├── auth.php           # Admin authentication functions
│   └── layout.php         # Sidebar and header components
├── assets/
│   └── css/
│       └── admin-main.css # Main admin stylesheet
├── api/
│   └── quick_stats.php    # API endpoint for dashboard stats
├── uploads/               # Product image uploads directory
├── index.php              # Dashboard
├── login.php              # Admin login
├── register.php           # Admin registration
├── logout.php             # Logout handler
├── products.php           # Product management
├── categories.php         # Category management
├── orders.php             # Order management
├── users.php              # User management
├── coupons.php            # Coupon management
└── settings.php           # Admin settings
```

## Access URLs

### Admin Login
```
http://localhost/custom_bottel/Cust_bottel/admin/login.php
```

### Admin Dashboard
```
http://localhost/custom_bottel/Cust_bottel/admin/index.php
```

## Features Overview

### Dashboard
- Real-time statistics (users, orders, products, revenue)
- Revenue chart (last 30 days)
- Top selling products
- Recent orders list

### Product Management
- Add/Edit/Delete products
- Stock quantity management
- Product image uploads
- Category assignment
- Active/Inactive status
- Search and filter by category

### Category Management
- Create/Edit/Delete categories
- Automatic slug generation
- Product count per category
- Active/Inactive status

### Orders Management
- View all orders with filters
- Status updates (pending, processing, printed, shipped, delivered, cancelled)
- Order details modal with items and shipping info
- Status history timeline
- Search by order number, email, or name

### Users Management
- List all users
- Block/Unblock users
- Delete users (only if no orders)
- View user stats (orders count, total spent)
- Search and filter by status

### Coupons Management
- Create discount coupons
- Percentage or flat amount discounts
- Maximum discount limit
- Minimum order amount
- Start/End dates
- Active/Inactive status

### Settings
- Update admin profile
- Change password
- View system information

## Design System

The admin panel uses the same design system as the user panel:
- **Background:** #0b0b0b (dark)
- **Cards:** #141414 (dark gray)
- **Accent:** #00bcd4 (cyan)
- **Secondary Accent:** #007bff (blue)
- **Font:** Poppins (Google Fonts)

## Security Features

- ✅ CSRF protection on all forms
- ✅ Role-based access control
- ✅ Separate admin authentication
- ✅ Password hashing
- ✅ SQL injection protection (prepared statements)
- ✅ XSS protection (input sanitization)

## API Endpoints

### Quick Stats
```
GET /admin/api/quick_stats.php
```
Returns JSON with dashboard statistics.

## Database Schema

### Categories Table
```sql
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### User Status Columns
```sql
ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN blocked_at DATETIME NULL;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL;
```

## Troubleshooting

### Cannot login
1. Check if admin user exists in database
2. Verify user role is 'admin' or 'staff'
3. Clear browser cookies/session

### Images not uploading
1. Check `admin/uploads/` directory permissions (should be writable)
2. Verify PHP upload settings in `php.ini`

### Database errors
1. Run `database/admin_schema_updates.sql` migration
2. Check database connection in `includes/config.php`

## Support

For issues or questions, check the main project documentation or contact the development team.

---

**Version:** 1.0.0  
**Last Updated:** 2025

