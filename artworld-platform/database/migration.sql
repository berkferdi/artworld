-- Art World Mobile Platform
-- MySQL 8 Migration
-- Charset: utf8mb4
-- All datetime fields stored in UTC

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE IF NOT EXISTS `artworld`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `artworld`;

-- --------------------------------------------------------
-- admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'admin', 'editor') NOT NULL DEFAULT 'admin',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_email` (`email`),
  KEY `idx_admins_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(500) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- news
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(320) NOT NULL,
  `summary` TEXT NULL,
  `content` LONGTEXT NULL,
  `cover_image` VARCHAR(500) NULL DEFAULT NULL,
  `author` VARCHAR(150) NULL DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_breaking` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`),
  KEY `idx_news_status_published` (`status`, `published_at`),
  KEY `idx_news_featured` (`is_featured`, `status`, `published_at`),
  KEY `idx_news_breaking` (`is_breaking`, `status`),
  KEY `idx_news_category` (`category_id`),
  CONSTRAINT `fk_news_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- news_images
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `news_id` BIGINT UNSIGNED NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `caption` VARCHAR(300) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_news_images_news` (`news_id`, `sort_order`),
  CONSTRAINT `fk_news_images_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- breaking_news
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `breaking_news` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `news_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `title` VARCHAR(300) NOT NULL,
  `target_url` VARCHAR(500) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `starts_at` DATETIME NULL DEFAULT NULL,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_breaking_status_sort` (`status`, `sort_order`),
  KEY `idx_breaking_dates` (`starts_at`, `expires_at`),
  KEY `idx_breaking_news` (`news_id`),
  CONSTRAINT `fk_breaking_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- videos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `videos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(320) NOT NULL,
  `description` TEXT NULL,
  `thumbnail` VARCHAR(500) NULL DEFAULT NULL,
  `video_url` VARCHAR(700) NOT NULL,
  `video_type` ENUM('mp4', 'hls', 'youtube', 'vimeo', 'external') NOT NULL DEFAULT 'mp4',
  `duration_seconds` INT UNSIGNED NULL DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_videos_slug` (`slug`),
  KEY `idx_videos_status_published` (`status`, `published_at`),
  KEY `idx_videos_featured` (`is_featured`, `status`, `published_at`),
  KEY `idx_videos_category` (`category_id`),
  CONSTRAINT `fk_videos_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- programs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `programs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(320) NOT NULL,
  `description` TEXT NULL,
  `cover_image` VARCHAR(500) NULL DEFAULT NULL,
  `presenter` VARCHAR(150) NULL DEFAULT NULL,
  `broadcast_day` VARCHAR(50) NULL DEFAULT NULL,
  `broadcast_time` VARCHAR(20) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_programs_slug` (`slug`),
  KEY `idx_programs_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- program_episodes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_episodes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(320) NOT NULL,
  `description` TEXT NULL,
  `thumbnail` VARCHAR(500) NULL DEFAULT NULL,
  `video_url` VARCHAR(700) NOT NULL,
  `video_type` ENUM('mp4', 'hls', 'youtube', 'vimeo', 'external') NOT NULL DEFAULT 'mp4',
  `episode_number` INT UNSIGNED NULL DEFAULT NULL,
  `duration_seconds` INT UNSIGNED NULL DEFAULT NULL,
  `published_at` DATETIME NULL DEFAULT NULL,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_episodes_program_slug` (`program_id`, `slug`),
  KEY `idx_episodes_status_published` (`status`, `published_at`),
  KEY `idx_episodes_program` (`program_id`, `episode_number`),
  CONSTRAINT `fk_episodes_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- live_streams
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `live_streams` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `description` TEXT NULL,
  `stream_url` VARCHAR(700) NOT NULL,
  `stream_type` ENUM('hls', 'youtube', 'external') NOT NULL DEFAULT 'hls',
  `poster_image` VARCHAR(500) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_live_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- banners
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `banners` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `image` VARCHAR(500) NOT NULL,
  `target_type` ENUM('news', 'video', 'program', 'url', 'none') NOT NULL DEFAULT 'none',
  `target_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `target_url` VARCHAR(500) NULL DEFAULT NULL,
  `position` VARCHAR(50) NOT NULL DEFAULT 'home_hero',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `starts_at` DATETIME NULL DEFAULT NULL,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_banners_position_status` (`position`, `status`, `sort_order`),
  KEY `idx_banners_dates` (`starts_at`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `body` TEXT NOT NULL,
  `image` VARCHAR(500) NULL DEFAULT NULL,
  `target_type` ENUM('news', 'video', 'program', 'url', 'none') NOT NULL DEFAULT 'none',
  `target_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `target_url` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('draft', 'sent', 'failed') NOT NULL DEFAULT 'draft',
  `sent_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- app_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `setting_type` ENUM('string', 'boolean', 'number', 'json', 'color', 'url') NOT NULL DEFAULT 'string',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- device_tokens
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_uuid` VARCHAR(100) NULL DEFAULT NULL,
  `fcm_token` VARCHAR(512) NOT NULL,
  `platform` ENUM('android', 'ios') NOT NULL,
  `app_version` VARCHAR(30) NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_seen_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_tokens_fcm` (`fcm_token`),
  KEY `idx_device_tokens_uuid` (`device_uuid`),
  KEY `idx_device_tokens_status` (`status`, `platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- video_views
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `video_views` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `video_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `episode_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `device_uuid` VARCHAR(100) NULL DEFAULT NULL,
  `ip_hash` VARCHAR(64) NULL DEFAULT NULL,
  `watched_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `completed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_video_views_video` (`video_id`, `created_at`),
  KEY `idx_video_views_episode` (`episode_id`, `created_at`),
  KEY `idx_video_views_device` (`device_uuid`, `created_at`),
  CONSTRAINT `fk_video_views_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_video_views_episode` FOREIGN KEY (`episode_id`) REFERENCES `program_episodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- news_views
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news_views` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `news_id` BIGINT UNSIGNED NOT NULL,
  `device_uuid` VARCHAR(100) NULL DEFAULT NULL,
  `ip_hash` VARCHAR(64) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_news_views_news` (`news_id`, `created_at`),
  KEY `idx_news_views_device` (`device_uuid`, `created_at`),
  CONSTRAINT `fk_news_views_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- login_attempts (rate limiting)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_lookup` (`email`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- api_rate_limits
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_rate_limits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_hash` VARCHAR(64) NOT NULL,
  `endpoint` VARCHAR(200) NOT NULL,
  `hit_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `window_start` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rate_limit_window` (`ip_hash`, `endpoint`, `window_start`),
  KEY `idx_rate_limit_cleanup` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
