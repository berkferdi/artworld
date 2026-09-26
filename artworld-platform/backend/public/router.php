<?php

declare(strict_types=1);

/**
 * PHP built-in server router for local development.
 * Usage: php -S 127.0.0.1:8080 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Serve existing static files directly
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

// Media files from uploads
if (preg_match('#^/(images|videos|thumbnails|hls)/#', $uri)) {
    require __DIR__ . '/media.php';
    return true;
}

// All other requests go to API front controller
require __DIR__ . '/index.php';
return true;
