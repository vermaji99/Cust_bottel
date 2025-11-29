# Reviews System Setup Guide

## Database Setup

1. **Run the SQL file to create reviews table:**
   - Open phpMyAdmin or your MySQL client
   - Run the SQL file: `database/create_reviews_table.sql`
   - This will create the `reviews` table with all necessary fields

## Features Implemented

### 1. Review Submission
- ✅ Authenticated users can submit reviews with 1-5 star rating
- ✅ Optional comment section
- ✅ Users can update their existing review
- ✅ One review per user per product

### 2. Admin Features
- ✅ Admin can reply to any review
- ✅ Admin reply appears below the review with special styling

### 3. Dynamic Rating Display
- ✅ Product cards show average rating from actual reviews
- ✅ Average rating updates automatically when reviews are added
- ✅ Rating range: 1.0 to 5.0 (always within valid range)

### 4. Review Display
- ✅ All reviews displayed with user name, date, rating, and comment
- ✅ Review summary with average rating and distribution
- ✅ Rating distribution bar chart showing 1-5 star breakdown

## Files Created/Modified

### New Files:
1. `database/create_reviews_table.sql` - Database schema for reviews
2. `api/submit_review.php` - API endpoint for submitting reviews
3. `api/admin_reply_review.php` - API endpoint for admin replies

### Modified Files:
1. `product.php` - Complete review section UI
2. `category.php` - Dynamic rating from reviews
3. `index.php` - Dynamic rating from reviews

## How to Use

1. **Run the SQL file first:**
   ```sql
   -- Run: database/create_reviews_table.sql
   ```

2. **Test the system:**
   - Login as a user
   - Go to any product page
   - Click on "Review" tab
   - Submit a review with rating and optional comment
   - The review will appear immediately after page reload

3. **Admin Reply:**
   - Login as admin
   - Go to product page with reviews
   - Below each review, admin will see a reply form
   - Admin can reply to any review

## Notes

- Reviews table uses unique constraint: one review per user per product
- Users can update their existing review (it will update, not create duplicate)
- Average rating is calculated from actual reviews in the database
- If no reviews exist, fallback rating is shown (based on product ID)
- All ratings are guaranteed to be between 1.0 and 5.0

