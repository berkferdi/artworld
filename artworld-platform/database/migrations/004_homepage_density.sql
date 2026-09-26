-- Homepage density: source attribution, manşet order, extra categories
-- Safe / idempotent for production.

SET NAMES utf8mb4;

-- News source + manşet ordering
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'source_url'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `news` ADD COLUMN `source_url` VARCHAR(500) NULL DEFAULT NULL AFTER `author`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'source_name'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `news` ADD COLUMN `source_name` VARCHAR(150) NULL DEFAULT NULL AFTER `source_url`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'manset_order'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE `news` ADD COLUMN `manset_order` INT NULL DEFAULT NULL AFTER `is_breaking`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND INDEX_NAME = 'idx_news_source_url'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `news` ADD KEY `idx_news_source_url` (`source_url`(191))',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND INDEX_NAME = 'idx_news_manset'
);
SET @sql := IF(@idx = 0,
  'ALTER TABLE `news` ADD KEY `idx_news_manset` (`manset_order`, `status`, `published_at`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Extra categories used on artworld.com.tr
INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'Antalya', 'antalya', 'Antalya haberleri', 6, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'antalya');

INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'Asayiş', 'asayis', 'Asayiş haberleri', 7, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'asayis');

INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'Sağlık', 'saglik', 'Sağlık haberleri', 8, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'saglik');

INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`, `status`, `created_at`, `updated_at`)
SELECT 'Turizm', 'turizm', 'Turizm haberleri', 9, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `slug` = 'turizm');
