-- Art World Mobile Platform Seed Data
-- Default admin password: Admin123!
-- CHANGE THIS PASSWORD IN PRODUCTION

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
USE `artworld`;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `news_views`;
TRUNCATE TABLE `video_views`;
TRUNCATE TABLE `device_tokens`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `banners`;
TRUNCATE TABLE `breaking_news`;
TRUNCATE TABLE `news_images`;
TRUNCATE TABLE `program_episodes`;
TRUNCATE TABLE `live_streams`;
TRUNCATE TABLE `videos`;
TRUNCATE TABLE `news`;
TRUNCATE TABLE `programs`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `app_settings`;
TRUNCATE TABLE `admins`;
TRUNCATE TABLE `login_attempts`;
TRUNCATE TABLE `api_rate_limits`;

SET FOREIGN_KEY_CHECKS = 1;

-- Admin user: admin@artworld.local / Admin123!
INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Süper Admin', 'admin@artworld.local', '$2y$10$.yPpn8la.Ql8/64vS1bCmeqCQuANoB39UPPp/PIQc5cao8lmO.hVi', 'super_admin', 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Gündem', 'gundem', 'Gündem haberleri', NULL, 1, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Spor', 'spor', 'Spor haberleri', NULL, 2, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 'Ekonomi', 'ekonomi', 'Ekonomi ve finans haberleri', NULL, 3, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(4, 'Kültür Sanat', 'kultur-sanat', 'Kültür ve sanat haberleri', NULL, 4, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(5, 'Teknoloji', 'teknoloji', 'Teknoloji haberleri', NULL, 5, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- News
INSERT INTO `news` (`id`, `category_id`, `title`, `slug`, `summary`, `content`, `cover_image`, `author`, `is_featured`, `is_breaking`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Art World Mobile Platformu Yayında', 'art-world-mobile-platformu-yayinda',
 'Yeni nesil haber ve internet televizyonu platformu kullanıma açıldı.',
 '<p>Art World Mobile, haberleri, videoları ve canlı yayınları tek uygulamada birleştiren modern bir medya platformudur.</p><p>Kullanıcılar manşetleri takip edebilir, son dakika haberlerini görebilir ve canlı yayını izleyebilir.</p>',
 '/images/demo/news-1.jpg', 'Editör', 1, 1, 'published', UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 2, 'Spor Gündeminde Önemli Gelişmeler', 'spor-gundeminde-onemli-gelismeler',
 'Haftanın öne çıkan spor gelişmeleri özetlendi.',
 '<p>Spor dünyasında haftanın en dikkat çekici gelişmeleri ve maç özetleri.</p>',
 '/images/demo/news-2.jpg', 'Spor Editörü', 1, 0, 'published', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 HOUR), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 3, 'Ekonomide Günün Özeti', 'ekonomide-gunun-ozeti',
 'Piyasalardaki hareketler ve ekonomik göstergeler.',
 '<p>Döviz kurları, borsa ve faiz oranlarındaki son gelişmeler.</p>',
 '/images/demo/news-3.jpg', 'Ekonomi Servisi', 1, 0, 'published', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 4 HOUR), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(4, 4, 'Sanat Galerisinde Yeni Serggi', 'sanat-galerisinde-yeni-sergi',
 'Çağdaş sanat eserlerinden oluşan yeni sergi kapılarını açtı.',
 '<p>Yerli ve yabancı sanatçıların eserlerinin yer aldığı sergi, ziyaretçilere açık.</p>',
 '/images/demo/news-4.jpg', 'Kültür Masası', 0, 0, 'published', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(5, 5, 'Yeni Teknoloji Trendleri 2026', 'yeni-teknoloji-trendleri-2026',
 'Mobil yayıncılık ve yapay zeka uygulamalarında yükselen trendler.',
 '<p>2026 yılında medya sektörünü etkileyecek teknoloji gelişmeleri.</p>',
 '/images/demo/news-5.jpg', 'Teknoloji Editörü', 0, 0, 'published', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP());

INSERT INTO `news_images` (`news_id`, `image_url`, `caption`, `sort_order`) VALUES
(1, '/images/demo/news-1-gallery-1.jpg', 'Uygulama arayüzü', 1),
(1, '/images/demo/news-1-gallery-2.jpg', 'Canlı yayın ekranı', 2);

INSERT INTO `breaking_news` (`news_id`, `title`, `target_url`, `sort_order`, `status`, `starts_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Art World Mobile platformu yayın hayatına başladı', NULL, 1, 'active', UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(NULL, 'Canlı yayın şu anda devam ediyor', NULL, 2, 'active', UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- Videos (sample public MP4 and HLS URLs for testing)
INSERT INTO `videos` (`id`, `category_id`, `title`, `slug`, `description`, `thumbnail`, `video_url`, `video_type`, `duration_seconds`, `is_featured`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Günün Manşetleri', 'gunun-mansetleri',
 'Günün öne çıkan haberlerinin video özeti.',
 '/thumbnails/demo/video-1.jpg',
 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
 'mp4', 596, 1, 'published', UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 5, 'Teknoloji Bülteni', 'teknoloji-bulteni',
 'Haftalık teknoloji bülteni.',
 '/thumbnails/demo/video-2.jpg',
 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
 'hls', 600, 1, 'published', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 HOUR), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 2, 'Spor Özeti', 'spor-ozeti',
 'Haftanın spor özeti.',
 '/thumbnails/demo/video-3.jpg',
 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
 'mp4', 653, 0, 'published', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- Programs
INSERT INTO `programs` (`id`, `title`, `slug`, `description`, `cover_image`, `presenter`, `broadcast_day`, `broadcast_time`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Güne Başlarken', 'gune-baslarken',
 'Sabah kuşağının vazgeçilmez programı. Günün önemli gelişmeleri ve röportajlar.',
 '/images/demo/program-1.jpg', 'Ayşe Yılmaz', 'Hafta içi her gün', '07:00', 1, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Sanat Söyleşileri', 'sanat-soylesileri',
 'Sanatçılar ve kültür insanlarıyla derinlemesine sohbetler.',
 '/images/demo/program-2.jpg', 'Mehmet Demir', 'Cumartesi', '20:00', 2, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP());

INSERT INTO `program_episodes` (`id`, `program_id`, `title`, `slug`, `description`, `thumbnail`, `video_url`, `video_type`, `episode_number`, `duration_seconds`, `published_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '14 Temmuz Özel Yayını', '14-temmuz-ozel-yayini',
 'Günün öne çıkan başlıkları.',
 '/thumbnails/demo/episode-1.jpg',
 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
 'mp4', 1, 15, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY), 'published', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 1, '15 Temmuz Sabah Bülteni', '15-temmuz-sabah-bulteni',
 'Sabah bülteni ve konuklar.',
 '/thumbnails/demo/episode-2.jpg',
 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
 'mp4', 2, 15, UTC_TIMESTAMP(), 'published', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 2, 'Çağdaş Sanat Üzerine', 'cagdas-sanat-uzerine',
 'Çağdaş sanat ve galeriler üzerine söyleşi.',
 '/thumbnails/demo/episode-3.jpg',
 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
 'mp4', 1, 60, DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 DAY), 'published', UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- Live stream
INSERT INTO `live_streams` (`id`, `title`, `description`, `stream_url`, `stream_type`, `poster_image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Art World Canlı Yayın',
 'Kesintisiz haber ve program yayını.',
 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
 'hls', '/images/demo/live-poster.jpg', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- Banners
INSERT INTO `banners` (`id`, `title`, `image`, `target_type`, `target_id`, `target_url`, `position`, `sort_order`, `status`, `starts_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Art World Açılış', '/images/demo/banner-1.jpg', 'news', 1, NULL, 'home_hero', 1, 'active', UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Canlı Yayın', '/images/demo/banner-2.jpg', 'url', NULL, '/live', 'home_hero', 2, 'active', UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY), UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- App settings
INSERT INTO `app_settings` (`setting_key`, `setting_value`, `setting_type`, `updated_at`) VALUES
('app_name', 'Art World Mobile', 'string', UTC_TIMESTAMP()),
('app_logo', '/images/demo/logo.png', 'url', UTC_TIMESTAMP()),
('primary_color', '#C8102E', 'color', UTC_TIMESTAMP()),
('secondary_color', '#111111', 'color', UTC_TIMESTAMP()),
('breaking_news_enabled', '1', 'boolean', UTC_TIMESTAMP()),
('live_stream_enabled', '1', 'boolean', UTC_TIMESTAMP()),
('maintenance_mode', '0', 'boolean', UTC_TIMESTAMP()),
('contact_email', 'info@artworld.local', 'string', UTC_TIMESTAMP()),
('contact_phone', '+90 555 000 0000', 'string', UTC_TIMESTAMP()),
('website_url', 'https://artworld.local', 'url', UTC_TIMESTAMP()),
('facebook_url', 'https://facebook.com', 'url', UTC_TIMESTAMP()),
('instagram_url', 'https://instagram.com', 'url', UTC_TIMESTAMP()),
('youtube_url', 'https://youtube.com', 'url', UTC_TIMESTAMP()),
('x_url', 'https://x.com', 'url', UTC_TIMESTAMP()),
('about_text', 'Art World Mobile, haber ve internet televizyonu platformudur.', 'string', UTC_TIMESTAMP()),
('max_upload_image_mb', '5', 'number', UTC_TIMESTAMP()),
('max_upload_video_mb', '200', 'number', UTC_TIMESTAMP());
