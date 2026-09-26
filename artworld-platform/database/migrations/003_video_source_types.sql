-- Art World — video source_type + file metadata
-- Idempotent; keeps existing video_url / video_type for mobile BC

SET NAMES utf8mb4;

-- videos.source_type
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='videos' AND COLUMN_NAME='source_type');
SET @sql := IF(@col=0, "ALTER TABLE `videos` ADD COLUMN `source_type` ENUM('file_server','youtube','external') NOT NULL DEFAULT 'file_server' AFTER `video_type`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='videos' AND COLUMN_NAME='file_path');
SET @sql := IF(@col=0, "ALTER TABLE `videos` ADD COLUMN `file_path` VARCHAR(700) NULL DEFAULT NULL AFTER `video_url`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='videos' AND COLUMN_NAME='mime_type');
SET @sql := IF(@col=0, "ALTER TABLE `videos` ADD COLUMN `mime_type` VARCHAR(100) NULL DEFAULT NULL AFTER `file_path`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='videos' AND COLUMN_NAME='file_size');
SET @sql := IF(@col=0, "ALTER TABLE `videos` ADD COLUMN `file_size` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `mime_type`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- program_episodes mirror
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='program_episodes' AND COLUMN_NAME='source_type');
SET @sql := IF(@col=0, "ALTER TABLE `program_episodes` ADD COLUMN `source_type` ENUM('file_server','youtube','external') NOT NULL DEFAULT 'file_server' AFTER `video_type`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='program_episodes' AND COLUMN_NAME='file_path');
SET @sql := IF(@col=0, "ALTER TABLE `program_episodes` ADD COLUMN `file_path` VARCHAR(700) NULL DEFAULT NULL AFTER `video_url`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='program_episodes' AND COLUMN_NAME='mime_type');
SET @sql := IF(@col=0, "ALTER TABLE `program_episodes` ADD COLUMN `mime_type` VARCHAR(100) NULL DEFAULT NULL AFTER `file_path`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='program_episodes' AND COLUMN_NAME='file_size');
SET @sql := IF(@col=0, "ALTER TABLE `program_episodes` ADD COLUMN `file_size` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `mime_type`", 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill source_type from legacy video_type
UPDATE `videos` SET `source_type` = 'youtube' WHERE `video_type` = 'youtube' AND (`source_type` IS NULL OR `source_type` = 'file_server') AND `video_url` LIKE '%youtu%';
UPDATE `videos` SET `source_type` = 'youtube' WHERE `video_type` = 'youtube';
UPDATE `videos` SET `source_type` = 'external' WHERE `video_type` IN ('vimeo', 'external') AND `source_type` = 'file_server';
UPDATE `videos` SET `source_type` = 'file_server' WHERE `video_type` IN ('mp4', 'hls') AND `source_type` NOT IN ('youtube', 'external');
UPDATE `videos` SET `file_path` = `video_url` WHERE `source_type` = 'file_server' AND `file_path` IS NULL AND `video_url` LIKE '/%';

UPDATE `program_episodes` SET `source_type` = 'youtube' WHERE `video_type` = 'youtube';
UPDATE `program_episodes` SET `source_type` = 'external' WHERE `video_type` IN ('vimeo', 'external') AND `source_type` = 'file_server';
UPDATE `program_episodes` SET `source_type` = 'file_server' WHERE `video_type` IN ('mp4', 'hls') AND `source_type` NOT IN ('youtube', 'external');
UPDATE `program_episodes` SET `file_path` = `video_url` WHERE `source_type` = 'file_server' AND `file_path` IS NULL AND `video_url` LIKE '/%';
