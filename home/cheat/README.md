# Liquid — Liquid Glass E-Commerce (PHP Native + MySQL)

A premium, iOS “Liquid Glass” themed e-commerce store built with **native PHP (no framework)**, **PDO + MySQL**, and dependency-free vanilla CSS/JS.

## ✨ Highlights
- Frosted-glass UI (`backdrop-filter: blur()`), Apple-style rounded cards, subtle shadows, SF Pro typography, minimal thin-stroke SVG icons.
- Storefront: home, product detail (gallery + glass lightbox), category filtering, AJAX cart, checkout, order success (invoice), payment confirmation with status tracker.
- Admin: secure login (`password_hash` + session), dashboard with mini chart, product CRUD (multi-image upload), category CRUD, order management (status flow + payment proof), customers, sales reports with CSV export.
- 100% PDO prepared statements, CSRF tokens, output escaping, validated & type-restricted uploads.
- Normalized schema, indexed hot columns, pagination everywhere, cached settings/categories — ready to scale from millions toward billions of rows with further indexing/caching/sharding.

## 📁 Structure
```
liquid-glass-shop/
├─ config.php               # DB credentials + PDO bootstrap + secure session
├─ database.sql             # Schema + seed data (run once)
├─ .htaccess                # Pretty URLs + hardening
├─ index.php                # Homepage (hero, categories, latest)
├─ product.php              # Product detail + gallery/lightbox
├─ category.php             # Category listing + filter + pagination
├─ cart.php                 # Cart (session-based)
├─ cart_action.php          # AJAX cart endpoint (add/update/remove/clear)
├─ checkout.php             # Checkout (transactional, stock-safe)
├─ order_success.php        # Invoice confirmation
├─ payment_confirmation.php # Status tracker + proof upload
├─ includes/
│  ├─ functions.php         # Helpers: escaping, CSRF, cart, cache, pagination, uploads
│  ├─ header.php / footer.php
│  └─ _product_card.php     # Reusable card partial
├─ admin/
│  ├─ includes/ (auth.php, admin_header.php, admin_footer.php)
│  ├─ login.php / logout.php / setup_admin.php
│  ├─ index.php (dashboard)
│  ├─ products.php / product_edit.php
│  ├─ categories.php
│  ├─ orders.php / order_view.php
│  ├─ customers.php
│  └─ reports.php
├─ assets/
│  ├─ css/style.css         # The Liquid Glass design system
│  ├─ js/script.js          # AJAX cart, lightbox, stepper, scroll reveal
│  └─ img/placeholder.svg
├─ uploads/                 # Product images & payment proofs (writable)
└─ cache/                   # File cache for settings/categories (writable)
```

## 🚀 Installation
1. Copy the folder to your web root (Apache + PHP 8.0+ recommended, `mod_rewrite` on).
2. Create the database & tables:
   ```bash
   mysql -u root -p < database.sql
   ```
3. Edit **config.php** — set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`. Set `BASE_URL` if running in a subfolder (e.g. `/shop`).
4. Make `uploads/` and `cache/` writable:
   ```bash
   chmod -R 775 uploads cache
   ```
5. Create the admin account: open `/admin/setup_admin.php` once in the browser, then **delete that file**.
   - Default login: **admin / admin123** (change the password after first login).
6. Visit the store at `/` and the admin at `/admin/login.php`.

## 🔐 Security notes
- All queries use PDO prepared statements with real (non-emulated) prepares.
- CSRF tokens on every mutating form; output escaped with `htmlspecialchars`.
- Uploads validated by real MIME type + size limit; PHP execution disabled in `uploads/`.
- Sessions use HttpOnly + SameSite; `session_regenerate_id` on admin login.

## 📈 Scaling guidance
- Every list is paginated (`LIMIT/OFFSET`); indexes cover `category_id`, `user_id`, `order_date`, `status`, and slugs.
- Settings & categories are file-cached (swap for Redis/Memcached at scale).
- For billions of rows: add composite/covering indexes for your top queries, switch OFFSET pagination to keyset (seek) pagination, introduce read replicas, table partitioning by date, a CDN for `uploads/`, and horizontal sharding by `user_id`/`order_id`.

## 🎨 Customization
- Store name, tagline, currency, flat shipping, and bank info live in the `settings` table (editable via SQL or extendable in admin).
- Design tokens (colors, blur, radius, shadows) are CSS variables at the top of `assets/css/style.css`.
