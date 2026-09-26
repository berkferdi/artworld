<?php

declare(strict_types=1);

// Nginx try_files + PHP-FPM: answer HEAD without full render failure
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    exit;
}


require_once dirname(__DIR__) . '/config/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self' https: data: blob:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net blob:; worker-src 'self' blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' https: data:; media-src 'self' https: blob:; frame-src https://www.youtube.com https://youtube.com; connect-src 'self' https:");

use Web\Controllers\ArchiveController;
use Web\Controllers\AuthorController;
use Web\Controllers\CategoryController;
use Web\Controllers\ContactController;
use Web\Controllers\GalleryController;
use Web\Controllers\HomeController;
use Web\Controllers\InterviewController;
use Web\Controllers\LiveController;
use Web\Controllers\NewsController;
use Web\Controllers\PageController;
use Web\Controllers\ProgramController;
use Web\Controllers\SearchController;
use Web\Controllers\SeoController;
use Web\Controllers\VideoController;
use Web\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/haber/{slug}', [NewsController::class, 'show']);
$router->get('/kategori/{slug}', [CategoryController::class, 'show']);
// Short category URLs (legacy / SEO friendly) — keep /kategori/{slug} too
$router->get('/gundem', [CategoryController::class, 'show'], ['slug' => 'gundem']);
$router->get('/spor', [CategoryController::class, 'show'], ['slug' => 'spor']);
$router->get('/ekonomi', [CategoryController::class, 'show'], ['slug' => 'ekonomi']);
$router->get('/kultur-sanat', [CategoryController::class, 'show'], ['slug' => 'kultur-sanat']);
$router->get('/teknoloji', [CategoryController::class, 'show'], ['slug' => 'teknoloji']);
$router->get('/antalya', [CategoryController::class, 'show'], ['slug' => 'antalya']);
$router->get('/asayis', [CategoryController::class, 'show'], ['slug' => 'asayis']);
$router->get('/saglik', [CategoryController::class, 'show'], ['slug' => 'saglik']);
$router->get('/turizm', [CategoryController::class, 'show'], ['slug' => 'turizm']);
$router->get('/arama', [SearchController::class, 'index']);
$router->get('/arsiv', [ArchiveController::class, 'index']);

$router->get('/video', [VideoController::class, 'index']);
$router->get('/videolar', [VideoController::class, 'index']);
$router->get('/video/{slug}', [VideoController::class, 'show']);
$router->get('/canli', [LiveController::class, 'index']);
$router->get('/canli-yayin', [LiveController::class, 'index']);
$router->get('/programlar', [ProgramController::class, 'index']);
$router->get('/program/{slug}', [ProgramController::class, 'show']);

$router->get('/foto-galeri', [GalleryController::class, 'index']);
$router->get('/galeri/{slug}', [GalleryController::class, 'show']);
$router->get('/yazarlar', [AuthorController::class, 'index']);
$router->get('/yazar/{slug}', [AuthorController::class, 'show']);
$router->get('/roportajlar', [InterviewController::class, 'index']);
$router->get('/roportaj/{slug}', [InterviewController::class, 'show']);

$router->get('/sayfa/{slug}', [PageController::class, 'show']);
$router->get('/hakkimizda', [PageController::class, 'hakkimizda']);
$router->get('/yayin-ilkeleri', [PageController::class, 'yayinIlkeleri']);
$router->get('/kullanim-sartlari', [PageController::class, 'kullanimSartlari']);
$router->get('/gizlilik-politikasi', [PageController::class, 'gizlilikPolitikasi']);
$router->get('/kvkk', [PageController::class, 'kvkk']);
$router->get('/kunye', [PageController::class, 'kunye']);
$router->get('/iletisim', [ContactController::class, 'index']);
$router->post('/iletisim', [ContactController::class, 'submit']);

$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/sitemap-news.xml', [SeoController::class, 'sitemapNews']);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($method, $uri);
