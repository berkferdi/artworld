<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function sanitizeHtml(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><img><span>';
        $clean = strip_tags($html, $allowed);

        // Remove event handlers and javascript: URLs
        $clean = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/\shref\s*=\s*(["\'])\s*javascript:.*?\1/iu', ' href="#"', $clean) ?? $clean;
        $clean = preg_replace('/\ssrc\s*=\s*(["\'])\s*javascript:.*?\1/iu', '', $clean) ?? $clean;

        return $clean;
    }

    public static function slugify(string $text): string
    {
        $map = [
            'ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
        ];
        $text = strtr($text, $map);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        return $text !== '' ? $text : 'icerik-' . time();
    }

    public static function generateToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function hashIp(string $ip): string
    {
        $salt = Config::get('APP_URL', 'artworld');
        return hash('sha256', $ip . '|' . $salt);
    }

    public static function clientIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    public static function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 0');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if (Config::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function applyCors(): void
    {
        $allowed = Config::get('CORS_ALLOWED_ORIGINS', '*');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($allowed === '*') {
            header('Access-Control-Allow-Origin: *');
        } elseif ($origin !== '' && in_array($origin, array_map('trim', explode(',', $allowed)), true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Device-UUID');
        header('Access-Control-Max-Age: 86400');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
