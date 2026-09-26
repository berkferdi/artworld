<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = Config::isProduction();
        $lifetime = Config::int('SESSION_LIFETIME', 7200);

        session_name((string) Config::get('SESSION_NAME', 'artworld_admin_session'));
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        }

        if (!isset($_SESSION['_last_activity'])) {
            $_SESSION['_last_activity'] = time();
        }

        if ((time() - (int) $_SESSION['_last_activity']) > $lifetime) {
            self::destroy();
            session_start();
            $_SESSION['_created_at'] = time();
        }

        $_SESSION['_last_activity'] = time();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public static function csrfToken(): string
    {
        $name = (string) Config::get('CSRF_TOKEN_NAME', '_csrf_token');
        $token = self::get($name);
        if (!is_string($token) || $token === '') {
            $token = Security::generateToken(32);
            self::set($name, $token);
        }
        return $token;
    }

    public static function validateCsrf(?string $token): bool
    {
        $name = (string) Config::get('CSRF_TOKEN_NAME', '_csrf_token');
        $sessionToken = self::get($name);
        return is_string($token) && is_string($sessionToken) && hash_equals($sessionToken, $token);
    }
}
