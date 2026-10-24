-- =============================================================================
-- Radhe Fashion — Database Schema & Initial Data
-- Database Name: radhe_fashion
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `radhe_fashion` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `radhe_fashion`;

-- -----------------------------------------------------------------------------
-- 1. Table: admins
-- Stores authenticated boutique managers. Passwords MUST be hashed with BCRYPT.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: categories
-- Product categories displayed on public filters & manageable from /admin
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cat_active_order` (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: products
-- Kurti & ethnic wear showcase items. Deleting a category with active products
-- is strictly restricted by foreign key to prevent orphaned data.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_code` VARCHAR(50) NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `description` TEXT NULL,
  `image_path` VARCHAR(255) NULL,
  `price` DECIMAL(10,2) NULL,
  `show_price` TINYINT(1) NOT NULL DEFAULT 0,
  `availability_status` ENUM('in_stock', 'made_to_order', 'out_of_stock') NOT NULL DEFAULT 'in_stock',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_prod_category` (`category_id`),
  INDEX `idx_prod_active` (`is_active`),
  INDEX `idx_prod_code` (`product_code`),
  CONSTRAINT `fk_products_category` 
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: settings
-- Key-value pair configuration for shop name, phone, WhatsApp, and socials.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `display_order`, `is_active`) VALUES
(1, 'Daily Wear Kurtis', 'daily-wear-kurtis', 'Effortless, breathable cotton & linen kurtis for daily comfort.', 1, 1),
(2, 'Festive Wear', 'festive-wear', 'Celebratory silhouettes in rich jewel tones with intricate threadwork.', 2, 1),
(3, 'Traditional Ethnic Wear', 'traditional-ethnic-wear', 'Heritage prints, authentic motifs, and timeless cuts.', 3, 1),
(4, 'Printed Kurtis', 'printed-kurtis', 'Contemporary cuts and lively artisan block prints.', 4, 1),
(5, 'Party Wear', 'party-wear', 'Subtle shimmer, tailored drapes, and refined embellishments.', 5, 1),
(6, 'New Arrivals', 'new-arrivals', 'Fresh boutique arrivals crafted for the modern Indian woman.', 6, 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Initial Showcase Products (using actual shop kurti photography)
INSERT INTO `products` (`id`, `product_code`, `name`, `slug`, `category_id`, `description`, `image_path`, `price`, `show_price`, `availability_status`, `is_active`) VALUES
(1, 'RF-101', 'Olive Embroidered Casual Kurti Co-ord', 'olive-embroidered-casual-kurti-co-ord', 1, 'Breathable natural fabric featuring delicate floral neckline embroidery with relaxed straight pants. Ideal for office and everyday wear.', 'assets/aistudio/WhatsApp_Image_2026-10-20_at_6.33.27_PM_de1107f2.jpeg', 1499.00, 0, 'in_stock', 1),
(2, 'RF-102', 'Royal Maroon Flared Anarkali Suit', 'royal-maroon-flared-anarkali-suit', 2, 'Deep crimson anarkali kurti featuring traditional printed border, drawstring tassels, and coordinating dupatta for festive celebrations.', 'assets/aistudio/WhatsApp_Image_2026-10-20_at_6.33.58_PM_5fb97872.jpeg', 2499.00, 0, 'in_stock', 1),
(3, 'RF-103', 'Magenta Ajrakh Heritage Kurti Set', 'magenta-ajrakh-heritage-kurti-set', 3, 'Graceful magenta straight kurti paired with an authentic black and gold Ajrakh block-printed dupatta and matching tailored trousers.', 'assets/aistudio/WhatsApp_Image_2026-10-20_at_6.32.43_PM_ca403740.jpeg', 1899.00, 0, 'in_stock', 1),
(4, 'RF-104', 'Mustard Botanical Printed Kurti Suit', 'mustard-botanical-printed-kurti-suit', 4, 'Vibrant mustard yellow kurti with refined V-neckline embroidery and a wide geometric motif printed dupatta.', 'assets/aistudio/WhatsApp_Image_2026-10-20_at_6.33.46_PM_7e668eaa.jpeg', 1650.00, 0, 'in_stock', 1),
(5, 'RF-105', 'Silk Shimmer Embroidered Kurti Trio', 'silk-shimmer-embroidered-kurti-trio', 5, 'Luxurious raw silk finish ethnic co-ord set with embroidered jacket overlay and wide palazzo trousers in warm antique tones.', 'assets/aistudio/WhatsApp_Image_2026-10-20_at_6.34.00_PM__1__d7deef20.jpeg', 2899.00, 0, 'in_stock', 1),
(6, 'RF-106', 'Natural Linen Relaxed Kurti Pant', 'natural-linen-relaxed-kurti-pant', 6, 'Subtle earthy linen kurti pant set with hand-finished collar motifs and deep utility pockets for effortless elegance.', 'assets/aistudio/WhatsApp_Image_2026-10-20_at_6.33.26_PM__1__6c76912c.jpeg', 1399.00, 0, 'in_stock', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('shop_name', 'Radhe Fashion'),
('tagline', 'Elegance in Every Thread'),
('shop_phone', '+91 9586914546'),
('shop_alt_phone', '+91 9638550377'),
('whatsapp_number', '919586914546'),
('shop_address', 'Radhe Fashion (રાધે ફેશન), Ladies Wear, Station Road Market, Gujarat, India'),
('instagram_url', 'https://instagram.com/radhefashion'),
('contact_email', 'contact@radhefashion.com'),
('default_currency', '₹'),
('show_price_default', '0')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
