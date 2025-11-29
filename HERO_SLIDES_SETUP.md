# 🖼️ Hero Slides Dynamic Management

## ✅ Complete Implementation

Hero section ab completely dynamic hai! Ab admin panel se hero section ki background images upload/update/delete kar sakte ho.

## 📋 Setup Instructions

### Step 1: Database Table Create Karein

1. **phpMyAdmin** ya **MySQL** console kholo
2. Apni database select karo
3. Ye SQL run karo:

```sql
CREATE TABLE IF NOT EXISTS hero_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NULL,
  image VARCHAR(255) NOT NULL,
  display_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_active_order (is_active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

Ya simple file run karo:
- File: `database/hero_slides.sql`

### Step 2: Admin Panel Access

1. Admin login karo: `http://localhost/custom_bottel/Cust_bottel/admin/login.php`
2. Sidebar mein **"Hero Slides"** option dikhega
3. Click karo aur manage karo!

## 🎯 Image Size Requirements

**Recommended Size:**
- **Width:** 1920px
- **Height:** 1080px
- **Ratio:** 16:9 (perfect for hero section)
- **Max File Size:** 5MB

**Minimum Size:**
- **Width:** 1600px
- **Height:** 600px

**Supported Formats:**
- JPG / JPEG
- PNG
- WebP

## 🚀 Features

### ✅ Add Hero Slide
1. Click "Add New Hero Slide"
2. Upload image (1920×1080px recommended)
3. Set display order (lower number = shows first)
4. Enable/disable slide
5. Save!

### ✅ Edit Hero Slide
1. Click edit button (pencil icon)
2. Change image, order, or status
3. Update!

### ✅ Delete Hero Slide
1. Click delete button (trash icon)
2. Confirm deletion
3. Image file automatically delete ho jayega!

### ✅ Display Order
- Lower numbers pehle dikhte hain
- Example: Order 1 = First slide, Order 2 = Second slide

### ✅ Active/Inactive
- **Active:** Slide frontend pe dikhega
- **Inactive:** Slide hidden rahega

## 📍 File Locations

- **Admin Page:** `admin/hero-slides.php`
- **Database SQL:** `database/hero_slides.sql`
- **Upload Directory:** `admin/uploads/`
- **Frontend:** `index.php` (auto-loads from database)

## 🔧 How It Works

1. **Admin Panel:**
   - Images `admin/uploads/` folder mein save hoti hain
   - Database mein image filename store hota hai

2. **Frontend:**
   - `index.php` automatically database se active slides load karta hai
   - Fallback: Agar koi slide nahi hai, default images use hongi

3. **Automatic:**
   - Slides order ke hisaab se show hoti hain
   - Only active slides dikhte hain
   - Image size validation automatic hai

## ✨ Benefits

- ✅ **No Code Changes:** Admin se direct manage karo
- ✅ **Easy Upload:** Drag-drop ya file select
- ✅ **Image Preview:** Upload se pehle preview dikhta hai
- ✅ **Size Validation:** Wrong size images reject ho jati hain
- ✅ **Auto Cleanup:** Delete karte waqt file bhi delete hoti hai

## 🎨 Tips

1. **Best Quality:** 1920×1080px images use karo (sharp aur clear)
2. **File Size:** 2-3MB tak rakho (fast loading ke liye)
3. **Multiple Slides:** 3-5 slides ideal hain (not too many)
4. **Order Matters:** Important slides ko lower order number do

---

**Test kar lo - ab hero section completely dynamic hai!** 🎉

