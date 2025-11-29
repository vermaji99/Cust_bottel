# 🔗 Website Access Guide

## ✅ Correct URL to Access Your Website

Your website is located at:
```
http://localhost/custom_bottel/Cust_bottel/
```

Or directly:
```
http://localhost/custom_bottel/Cust_bottel/index.php
```

## 📋 Quick Access URLs

### Main Pages:
- **Home**: `http://localhost/custom_bottel/Cust_bottel/`
- **Shop**: `http://localhost/custom_bottel/Cust_bottel/category.php`
- **Login**: `http://localhost/custom_bottel/Cust_bottel/login.php`
- **Register**: `http://localhost/custom_bottel/Cust_bottel/register.php`
- **About**: `http://localhost/custom_bottel/Cust_bottel/about.php`
- **Contact**: `http://localhost/custom_bottel/Cust_bottel/contact.php`

### Admin Panel:
- **Admin Login**: `http://localhost/custom_bottel/Cust_bottel/admin/login.php`
- **Admin Dashboard**: `http://localhost/custom_bottel/Cust_bottel/admin/`

### User Pages (after login):
- **Cart**: `http://localhost/custom_bottel/Cust_bottel/user/cart.php`
- **Checkout**: `http://localhost/custom_bottel/Cust_bottel/user/checkout.php`
- **Orders**: `http://localhost/custom_bottel/Cust_bottel/user/orders.php`
- **Profile**: `http://localhost/custom_bottel/Cust_bottel/user/profile.php`
- **Wishlist**: `http://localhost/custom_bottel/Cust_bottel/user/wishlist.php`

## 🔧 Troubleshooting

### If you get "Not Found" error:

1. **Check Apache is running**
   - Open XAMPP Control Panel
   - Ensure Apache is "Running" (green)

2. **Check MySQL is running**
   - Ensure MySQL is "Running" in XAMPP Control Panel

3. **Verify project path**
   - Project should be in: `C:\xampp\htdocs\custom_bottel\Cust_bottel\`
   - Make sure `index.php` exists in this folder

4. **Clear browser cache**
   - Press `Ctrl + F5` to hard refresh
   - Or clear browser cache completely

5. **Check browser URL**
   - Make sure you're using the **exact** URL: `http://localhost/custom_bottel/Cust_bottel/`
   - Note: There are **two** folders (`custom_bottel` AND `Cust_bottel`)

## 📁 Project Structure

```
C:\xampp\htdocs\
└── custom_bottel\
    └── Cust_bottel\          ← Your website is here
        ├── index.php         ← Main homepage
        ├── category.php
        ├── login.php
        ├── admin\
        ├── user\
        └── ...
```

## ⚠️ Common Mistakes

❌ **WRONG**: `http://localhost/`  
❌ **WRONG**: `http://localhost/custom_bottel/`  
❌ **WRONG**: `http://localhost/Cust_bottel/`

✅ **CORRECT**: `http://localhost/custom_bottel/Cust_bottel/`

## 🚀 Quick Start

1. Start XAMPP (Apache + MySQL)
2. Open browser
3. Go to: `http://localhost/custom_bottel/Cust_bottel/`
4. You should see the Bottel homepage!

