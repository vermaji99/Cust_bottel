# Admin Dashboard Fixes Applied

## Issue Fixed
Error: `Column not found: 1054 Unknown column 'oi.unit_price' in 'field list'`

## Root Cause
The actual database has `price` column in `order_items` table, but the code was using `unit_price`.

## Changes Made

1. **admin/index.php** - Fixed Top Products query:
   - Changed `oi.unit_price` → `oi.price`
   - Made status checks case-insensitive using `LOWER(status)`

2. **admin/orders.php** - Fixed Order Details display:
   - Changed `$item['unit_price']` → `$item['price']`

## Database Column Names

| Table | Column Name | Description |
|-------|-------------|-------------|
| `order_items` | `price` | Price per unit (NOT `unit_price`) |
| `order_items` | `quantity` | Quantity ordered |

## Status Values

The database might have status values in different cases:
- `Pending` or `pending`
- `Delivered` or `delivered`
- `Shipped` or `shipped`

All queries now use `LOWER(status)` to make them case-insensitive.

## Testing

After these fixes, the admin dashboard should work without errors. If you still get errors, check:
1. Database connection is working
2. Tables exist: `order_items`, `orders`, `products`
3. Column names match: `price` (not `unit_price`)

