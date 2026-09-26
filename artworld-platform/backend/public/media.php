<?php

declare(strict_types=1);

/**
 * Serves files from backend/uploads with safe MIME types.
 * Prefer Nginx/Apache static serving of /uploads → backend/uploads in production.
 * Example Nginx:
 *   location /uploads/ {
 *       alias /path/to/backend/uploads/;
 *       autoindex off;
 *       location ~* \.php$ { deny all; }
 *   }
 */

$path = $_GET['path'] ?? '';
$path = str_replace(['\\', "\0"], '', (string) $path);
$path = ltrim($path, '/');

if ($path === '' || str_contains($path, '..')) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Geçersiz dosya yolu.';
    exit;
}

$base = realpath(dirname(__DIR__) . '/uploads');
$file = realpath($base . '/' . $path);

if ($base === false || $file === false || !str_starts_with($file, $base) || !is_file($file)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Dosya bulunamadı.';
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (in_array($ext, ['php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh'], true)) {
    http_response_code(403);
    exit;
}

$mimes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'gif' => 'image/gif',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mov' => 'video/quicktime',
    'm3u8' => 'application/vnd.apple.mpegurl',
    'ts' => 'video/mp2t',
    'pdf' => 'application/pdf',
];

$mime = $mimes[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($file));
header('Cache-Control: public, max-age=86400');
readfile($file);
