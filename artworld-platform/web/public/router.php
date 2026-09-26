<?php

declare(strict_types=1);

// PHP built-in server router for web frontend
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

// Serve assets from sibling assets/ directory
if (str_starts_with($uri, '/assets/')) {
    $asset = dirname(__DIR__) . $uri;
    if (is_file($asset)) {
        $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
        $types = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff2' => 'font/woff2',
        ];
        if (isset($types[$ext])) {
            header('Content-Type: ' . $types[$ext]);
        }
        readfile($asset);
        return true;
    }
}

require __DIR__ . '/index.php';
