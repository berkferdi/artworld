<?php

declare(strict_types=1);

namespace Web\Core;

final class Cache
{
    public static function get(string $key): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return null;
        }
        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['expires'], $payload['data'])) {
            return null;
        }
        if ((int) $payload['expires'] < time()) {
            @unlink($file);
            return null;
        }
        return $payload['data'];
    }

    public static function put(string $key, mixed $data, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            return;
        }
        $dir = WEB_STORAGE . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $payload = json_encode([
            'expires' => time() + $ttlSeconds,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }
        file_put_contents(self::path($key), $payload, LOCK_EX);
    }

    public static function forget(string $key): void
    {
        $file = self::path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function path(string $key): string
    {
        return WEB_STORAGE . '/cache/' . hash('sha256', $key) . '.json';
    }
}
