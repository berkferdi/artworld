<?php

declare(strict_types=1);

use Web\Core\Config;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function asset(string $path): string
{
    $path = '/' . ltrim($path, '/');
    return $path;
}

function url(string $path = '/', array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    $url = $path === '' ? '/' : $path;
    if ($query !== []) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }
    return $url;
}

function absolute_url(string $path = '/'): string
{
    $host = rtrim((string) Config::get('CANONICAL_HOST', Config::get('APP_URL', '')), '/');
    $path = '/' . ltrim($path, '/');
    return $host . ($path === '//' ? '/' : $path);
}

function format_date(?string $datetime, string $format = 'd.m.Y H:i'): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }
    try {
        $dt = new DateTimeImmutable($datetime, new DateTimeZone('UTC'));
        $tz = new DateTimeZone((string) Config::get('TIMEZONE', 'Europe/Istanbul'));
        return $dt->setTimezone($tz)->format($format);
    } catch (Throwable) {
        return $datetime;
    }
}

function truncate(?string $text, int $limit = 140): string
{
    $text = trim(strip_tags((string) $text));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

function news_url(string $slug): string
{
    return url('/haber/' . rawurlencode($slug));
}

function category_url(string $slug): string
{
    return url('/kategori/' . rawurlencode($slug));
}

function video_url(string $slug): string
{
    return url('/video/' . rawurlencode($slug));
}

function program_url(string $slug): string
{
    return url('/program/' . rawurlencode($slug));
}

function gallery_url(string $slug): string
{
    return url('/galeri/' . rawurlencode($slug));
}

function author_url(string $slug): string
{
    return url('/yazar/' . rawurlencode($slug));
}

function interview_url(string $slug): string
{
    return url('/roportaj/' . rawurlencode($slug));
}

function youtube_embed_url(?string $videoUrl): ?string
{
    if ($videoUrl === null || $videoUrl === '') {
        return null;
    }
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $videoUrl, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    return null;
}

/**
 * @param array<string, mixed>|null $settings
 * @return list<array{title:string,url:string}>
 */
function default_menu(?array $settings = null): array
{
    return [
        ['title' => 'Ana Sayfa', 'url' => url('/')],
        ['title' => 'Canlı Yayın', 'url' => url('/canli-yayin')],
        ['title' => 'Videolar', 'url' => url('/videolar')],
        ['title' => 'Programlar', 'url' => url('/programlar')],
        ['title' => 'Foto Galeri', 'url' => url('/foto-galeri')],
        ['title' => 'İletişim', 'url' => url('/iletisim')],
        ['title' => 'Gündem', 'url' => url('/gundem')],
        ['title' => 'Spor', 'url' => url('/spor')],
        ['title' => 'Ekonomi', 'url' => url('/ekonomi')],
        ['title' => 'Kültür Sanat', 'url' => url('/kultur-sanat')],
        ['title' => 'Teknoloji', 'url' => url('/teknoloji')],
    ];
}
