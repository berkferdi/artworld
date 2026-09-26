-- Art World Unified Platform — Web extensions
-- Idempotent migration (safe to re-run)
-- Charset: utf8mb4 | Datetimes: UTC

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NULL,
  `meta_title` VARCHAR(255) NULL DEFAULT NULL,
  `meta_description` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pages_slug` (`slug`),
  KEY `idx_pages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `authors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `bio` TEXT NULL,
  `photo` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_authors_slug` (`slug`),
  KEY `idx_authors_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `galleries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `cover_image` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_galleries_slug` (`slug`),
  KEY `idx_galleries_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gallery_id` BIGINT UNSIGNED NOT NULL,
  `image` VARCHAR(500) NOT NULL,
  `caption` VARCHAR(300) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gallery_images_gallery` (`gallery_id`, `sort_order`),
  CONSTRAINT `fk_gallery_images_gallery` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `interviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(320) NOT NULL,
  `summary` TEXT NULL,
  `content` LONGTEXT NULL,
  `cover_image` VARCHAR(500) NULL DEFAULT NULL,
  `author` VARCHAR(150) NULL DEFAULT NULL,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_interviews_slug` (`slug`),
  KEY `idx_interviews_status_published` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `position` VARCHAR(80) NOT NULL,
  `type` ENUM('image', 'html', 'script') NOT NULL DEFAULT 'image',
  `content` TEXT NULL,
  `image` VARCHAR(500) NULL DEFAULT NULL,
  `link` VARCHAR(500) NULL DEFAULT NULL,
  `start_at` DATETIME NULL DEFAULT NULL,
  `end_at` DATETIME NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ads_position_status` (`position`, `status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menus` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `location` VARCHAR(80) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menus_location` (`location`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id` BIGINT UNSIGNED NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `title` VARCHAR(150) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `target` VARCHAR(20) NOT NULL DEFAULT '_self',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_items_menu` (`menu_id`, `sort_order`),
  CONSTRAINT `fk_menu_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `type` VARCHAR(80) NOT NULL,
  `config` JSON NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'inactive',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_services_slug` (`slug`),
  KEY `idx_services_status` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_type` VARCHAR(80) NOT NULL,
  `page_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `meta_title` VARCHAR(255) NULL DEFAULT NULL,
  `meta_description` VARCHAR(500) NULL DEFAULT NULL,
  `canonical_url` VARCHAR(500) NULL DEFAULT NULL,
  `robots` VARCHAR(80) NULL DEFAULT NULL,
  `og_title` VARCHAR(255) NULL DEFAULT NULL,
  `og_description` VARCHAR(500) NULL DEFAULT NULL,
  `og_image` VARCHAR(500) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seo_page` (`page_type`, `page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional author link on news (non-destructive)
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'author_id'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `news` ADD COLUMN `author_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `author`, ADD KEY `idx_news_author_id` (`author_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Hakkımızda' AS title, 'hakkimizda' AS slug,
         '<p>Art World TV — Dünyanın buluştuğu yerdesiniz.</p>' AS content,
         'Hakkımızda' AS meta_title, 'Art World hakkında' AS meta_description,
         'published' AS status, UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'hakkimizda');

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Yayın İlkeleri' AS title, 'yayin-ilkeleri' AS slug,
         '<p>Yayın ilkelerimiz yakında güncellenecektir.</p>' AS content,
         'Yayın İlkeleri' AS meta_title, 'Art World yayın ilkeleri' AS meta_description,
         'published' AS status, UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'yayin-ilkeleri');

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Kullanım Şartları' AS title, 'kullanim-sartlari' AS slug,
         '<p>Kullanım şartları yakında güncellenecektir.</p>' AS content,
         'Kullanım Şartları' AS meta_title, 'Art World kullanım şartları' AS meta_description,
         'published' AS status, UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'kullanim-sartlari');

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Gizlilik Politikası' AS title, 'gizlilik-politikasi' AS slug,
         '<p>Gizlilik politikası yakında güncellenecektir.</p>' AS content,
         'Gizlilik Politikası' AS meta_title, 'Art World gizlilik politikası' AS meta_description,
         'published' AS status, UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'gizlilik-politikasi');

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'KVKK / Veri Politikası' AS title, 'kvkk' AS slug,
         '<p>KVKK metni yakında güncellenecektir.</p>' AS content,
         'KVKK' AS meta_title, 'Art World KVKK' AS meta_description,
         'published' AS status, UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'kvkk');

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Künye' AS title, 'kunye' AS slug,
         '<p>Art World TV künye bilgileri.</p>' AS content,
         'Künye' AS meta_title, 'Art World künye' AS meta_description,
         'published' AS status, UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'kunye');

INSERT INTO `menus` (`title`, `location`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Ana Menü' AS title, 'header' AS location, 0 AS sort_order, 'active' AS status,
         UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM menus WHERE location = 'header');

INSERT INTO `menu_items` (`menu_id`, `parent_id`, `title`, `url`, `target`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT m.id, NULL, t.title, t.url, '_self', t.sort_order, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()
FROM menus m
JOIN (
  SELECT 'Ana Sayfa' AS title, '/' AS url, 0 AS sort_order
  UNION ALL SELECT 'Canlı Yayın', '/canli', 1
  UNION ALL SELECT 'Videolar', '/video', 2
  UNION ALL SELECT 'Programlar', '/programlar', 3
  UNION ALL SELECT 'Foto Galeri', '/foto-galeri', 4
  UNION ALL SELECT 'İletişim', '/iletisim', 5
) t
WHERE m.location = 'header'
  AND NOT EXISTS (SELECT 1 FROM menu_items mi WHERE mi.menu_id = m.id AND mi.url = t.url);

INSERT INTO `services` (`name`, `slug`, `type`, `config`, `status`, `sort_order`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Piyasa Ticker' AS name, 'market-ticker' AS slug, 'market' AS type,
         JSON_OBJECT('provider', 'none', 'symbols', JSON_ARRAY('BIST100', 'GOLD', 'USDTRY', 'EURTRY')) AS config,
         'inactive' AS status, 0 AS sort_order, UTC_TIMESTAMP() AS created_at, UTC_TIMESTAMP() AS updated_at
) AS tmp WHERE NOT EXISTS (SELECT 1 FROM services WHERE slug = 'market-ticker');
