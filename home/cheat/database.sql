-- ============================================================
--  LIQUID GLASS SHOP — MySQL Schema (Normalized & Indexed)
--  Designed for high-scale reads/writes (millions–billions rows)
--  Engine: InnoDB | Charset: utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

CREATE DATABASE IF NOT EXISTS `liquid_glass_shop`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `liquid_glass_shop`;

-- ---------- USERS (customers) ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120)    NOT NULL,
  `email`         VARCHAR(190)    NOT NULL,
  `phone`         VARCHAR(32)     DEFAULT NULL,
  `password_hash` VARCHAR(255)    DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ADMINS ----------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(60)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CATEGORIES ----------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `slug`       VARCHAR(140) NOT NULL,
  `icon`       VARCHAR(190) DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- PRODUCTS ----------
CREATE TABLE IF NOT EXISTS `products` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED    DEFAULT NULL,
  `name`        VARCHAR(190)    NOT NULL,
  `slug`        VARCHAR(210)    NOT NULL,
  `description` TEXT            DEFAULT NULL,
  `price`       DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
  `stock`       INT             NOT NULL DEFAULT 0,
  `sold`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_active_created` (`is_active`, `created_at`),
  KEY `idx_products_active_sold` (`is_active`, `sold`),
  KEY `idx_products_price` (`price`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- PRODUCT IMAGES ----------
CREATE TABLE IF NOT EXISTS `product_images` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `path`       VARCHAR(255)    NOT NULL,
  `is_primary` TINYINT(1)      NOT NULL DEFAULT 0,
  `sort_order` INT             NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pimg_product` (`product_id`, `sort_order`),
  CONSTRAINT `fk_pimg_product` FOREIGN KEY (`product_id`)
    REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ORDERS ----------
--  status: pending | paid | shipped | completed | cancelled
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice`        VARCHAR(40)     NOT NULL,
  `user_id`        BIGINT UNSIGNED DEFAULT NULL,
  `customer_name`  VARCHAR(120)    NOT NULL,
  `phone`          VARCHAR(32)     NOT NULL,
  `address`        VARCHAR(255)    NOT NULL,
  `city`           VARCHAR(120)    NOT NULL,
  `postal_code`    VARCHAR(20)     NOT NULL,
  `payment_method` VARCHAR(40)     NOT NULL,
  `subtotal`       DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
  `shipping`       DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
  `status`         ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `order_date`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_invoice` (`invoice`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status_date` (`status`, `order_date`),
  KEY `idx_orders_date` (`order_date`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ORDER ITEMS ----------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     BIGINT UNSIGNED NOT NULL,
  `product_id`   BIGINT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(190)    NOT NULL,
  `price`        DECIMAL(14,2)   NOT NULL,
  `qty`          INT             NOT NULL,
  `line_total`   DECIMAL(14,2)   NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oitems_order` (`order_id`),
  KEY `idx_oitems_product` (`product_id`),
  CONSTRAINT `fk_oitems_order` FOREIGN KEY (`order_id`)
    REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- PAYMENTS (proof of transfer) ----------
CREATE TABLE IF NOT EXISTS `payments` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   BIGINT UNSIGNED NOT NULL,
  `proof_path` VARCHAR(255)    DEFAULT NULL,
  `amount`     DECIMAL(14,2)   DEFAULT NULL,
  `note`       VARCHAR(255)    DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`)
    REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- SETTINGS (key/value cache-friendly) ----------
CREATE TABLE IF NOT EXISTS `settings` (
  `key`   VARCHAR(80)  NOT NULL,
  `value` TEXT         DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
--  SEED DATA
-- ============================================================

-- Default admin (username: admin | password: admin123)
-- Hash generated with PHP password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `admins` (`username`, `password_hash`, `name`) VALUES
('admin', '$2y$10$e0NRzp3G3n0oQ0m0m0m0mОPLACEHOLDER', 'Store Admin')
ON DUPLICATE KEY UPDATE `username` = `username`;
-- NOTE: run /admin/setup_admin.php once to (re)generate a valid hash.

INSERT INTO `settings` (`key`, `value`) VALUES
('store_name', 'Liquid'),
('store_tagline', 'Objects of pure clarity.'),
('currency', 'Rp'),
('shipping_flat', '15000'),
('bank_info', 'BCA 1234567890 a.n. Liquid Store')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `categories` (`name`, `slug`, `icon`) VALUES
('Audio', 'audio', 'headphones'),
('Wearables', 'wearables', 'watch'),
('Displays', 'displays', 'display'),
('Accessories', 'accessories', 'sparkles')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `stock`, `sold`, `is_active`) VALUES
(1, 'Aura Wireless Earbuds', 'aura-wireless-earbuds', 'Immersive spatial audio in a feather-light glass shell. Adaptive noise isolation and 30-hour battery.', 2499000, 120, 340, 1),
(2, 'Halo Smart Watch', 'halo-smart-watch', 'A crystal display that floats on your wrist. Health sensing, always-on clarity.', 4990000, 60, 210, 1),
(3, 'Lumen 4K Monitor', 'lumen-4k-monitor', 'Edge-to-edge glass panel with true-tone brilliance and whisper-thin bezels.', 8990000, 25, 75, 1),
(4, 'Prism Charging Pad', 'prism-charging-pad', 'A single sheet of frosted glass that powers your world. 15W fast, silent.', 899000, 300, 890, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
