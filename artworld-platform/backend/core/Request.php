<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $json = self::json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }
        return $_POST[$key] ?? $default;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST, self::json());
    }

    public static function json(): array
    {
        static $parsed = null;
        if ($parsed !== null) {
            return $parsed;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            $parsed = [];
            return $parsed;
        }

        $data = json_decode($raw, true);
        $parsed = is_array($data) ? $data : [];
        return $parsed;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::query($key, self::input($key, $default));
        return (int) $value;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::query($key, self::input($key, $default));
        return is_string($value) ? trim($value) : $default;
    }

    public static function page(): int
    {
        $page = max(1, self::int('page', 1));
        return $page;
    }

    public static function perPage(int $default = 20, int $max = 50): int
    {
        $perPage = self::int('per_page', $default);
        return max(1, min($max, $perPage));
    }

    public static function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? $default;
    }
}
