## Bottel Project

### Overview

Modernized PHP storefront for custom bottle ordering featuring secure authentication, saved designs with live customizer, wishlist, cart/checkout with coupons, and an upgraded admin console.

### Requirements

- PHP 8.1+
- MySQL 8+ (with InnoDB)
- Composer 2+
- Node/Frontend tooling not required

### Installation

1. Clone project into your web root (`C:\xampp\htdocs\bottel-project`).
2. Copy `includes/config.php` and update DB/SMTP constants if needed.
3. Install dependencies:
   ```bash
   composer install
   ```
4. Import database schema:
   ```bash
   mysql -u root -p bottel_db < database/schema.sql
   ```
5. Ensure writable folders:
   - `admin/uploads`
   - `admin/uploads/designs`
   - `admin/uploads/designs/thumbnails`
6. Configure Apache virtual host (or use `http://localhost/bottel-project`).

### Key Features

- **Secure Auth**: password hashing, CSRF, rate limits, email verification, OTP reset.
- **Profile/Orders**: dashboard, timeline, cancellation, saved addresses.
- **Design Studio**: multi-layer customizer, saved PNG/JPG, thumbnails, re-edit.
- **Wishlist**: AJAX add/remove, move to cart, header counts.
- **Cart/Checkout**: DB-backed cart, coupons, stock sync, design attachments.
- **Admin**: filters/search, status workflow, revenue cards, timeline logging.
- **API Suite**: JSON-only `/api/*` endpoints with validation/error codes.

### API Summary

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/register_user.php` | POST | Register + send verification |
| `/api/login_user.php` | POST | Login + session |
| `/api/wishlist.php` | POST | Add/remove/list/move items |
| `/api/add_to_cart.php` | POST | Increment cart quantity |
| `/api/remove_from_cart.php` | POST | Remove cart line |
| `/api/checkout.php` | POST | Place order from cart |
| `/api/save_design.php` | POST | Save flattened design + meta |
| `/api/design.php` | GET | Fetch saved design meta |
| `/api/user-stats.php` | GET | Badge counts |
| `/api/fetch_products.php` | GET | Paginated products |
| `/api/search.php` | GET | Quick search |

All endpoints expect/return JSON and enforce authentication where required.

### Email Templates

Located in `includes/emails/`:

- `verify.php`
- `password_reset.php`
- `order_confirmation.php`
- `custom_design.php`
- `admin_notification.php`

### Folder Highlights

- `includes/bootstrap.php` global loader (Composer, secure sessions).
- `assets/js/app.js` global toasts, badge counters, wishlist actions.
- `assets/js/customize.js` enhanced customizer UX + API binding.
- `database/schema.sql` canonical schema.
- `user/*` guarded pages using `user/init.php`.
- `admin/*` now rely on `require_admin()` with role-based access.

### Security Upgrades

- Hardened sessions (HttpOnly, SameSite, regeneration).
- CSRF tokens across forms, JSON APIs enforce `application/json`.
- Prepared statements everywhere.
- Login attempt tracking + lock-outs.
- Email verification + JWT-based password reset.
- Cart/order writes require authenticated DB-backed state.

### Dependencies

- `phpmailer/phpmailer` – SMTP emails.
- `firebase/php-jwt` – signed reset tokens.

Install via Composer (`vendor/autoload.php` already required in bootstrap).

### Development Notes

- Run `composer dump-autoload` after adding helpers.
- Use `database/schema.sql` for migrations; avoid the deprecated inline table creation.
- Asset builds rely on CDN fonts/icons; no local bundler required.





"# Cust_bottel" 
