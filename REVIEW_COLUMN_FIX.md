# Review System - Missing Column Fix

## 🔍 Root Cause

**Error Message:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'comment' in 'field list'
```

**Problem:**
The `reviews` table exists in the database but is **missing the `comment` column**. The code was trying to INSERT/UPDATE using `comment` column which doesn't exist.

## ✅ Fix Applied

### 1. Added Automatic Column Check in `bootstrap.php`

**Location:** `Cust_bottel/includes/bootstrap.php` (Lines 72-111)

**What it does:**
- Checks if `reviews` table exists
- If table exists, checks if `comment` column exists
- If `comment` column is missing, automatically adds it:
  ```sql
  ALTER TABLE reviews ADD COLUMN comment TEXT DEFAULT NULL AFTER rating
  ```

### 2. Added Backup Check in `submit_review.php`

**Location:** `Cust_bottel/api/submit_review.php` (Lines 48-58)

**What it does:**
- Before inserting/updating reviews, checks if `comment` column exists
- If missing, adds it automatically
- This serves as a backup if bootstrap hasn't run yet

## 🔧 How It Works

1. **On Page Load:**
   - `bootstrap.php` automatically checks and fixes the table structure
   - Missing columns are added automatically

2. **On Review Submission:**
   - `submit_review.php` double-checks the column exists
   - If still missing, adds it before proceeding

## ✅ Testing

1. **Refresh the page** (Ctrl+F5) - Bootstrap will add the column
2. **Submit a review** - Should work now!
3. **Check database** - `comment` column should now exist

## 📋 Files Modified

1. ✅ `Cust_bottel/includes/bootstrap.php` - Added column check and auto-fix
2. ✅ `Cust_bottel/api/submit_review.php` - Added backup column check

## 🎯 Result

- ✅ `comment` column will be automatically added if missing
- ✅ No manual database changes required
- ✅ Review submission will work correctly
- ✅ Existing reviews will continue to work (comment will be NULL for old reviews)

---

**Status:** ✅ Fixed - Column will be added automatically on next page load!

