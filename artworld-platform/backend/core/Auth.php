<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;

final class Auth
{
    public static function attempt(string $email, string $password, bool $remember = false): array
    {
        $pdo = Database::connection();
        $ip = Security::clientIp();

        if (self::isLockedOut($email, $ip)) {
            return ['success' => false, 'message' => 'Çok fazla başarısız deneme. Lütfen daha sonra tekrar deneyin.'];
        }

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || ($admin['status'] ?? '') !== 'active' || !password_verify($password, $admin['password'])) {
            self::recordAttempt($email, $ip);
            return ['success' => false, 'message' => 'E-posta veya şifre hatalı.'];
        }

        self::clearAttempts($email, $ip);
        Session::regenerate();
        Session::set('admin_id', (int) $admin['id']);
        Session::set('admin_name', $admin['name']);
        Session::set('admin_email', $admin['email']);
        Session::set('admin_role', $admin['role']);

        $upd = $pdo->prepare('UPDATE admins SET last_login_at = UTC_TIMESTAMP() WHERE id = ?');
        $upd->execute([(int) $admin['id']]);

        if ($remember) {
            // Extend session cookie lifetime
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + (86400 * 30),
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return ['success' => true, 'message' => 'Giriş başarılı.', 'admin' => $admin];
    }

    public static function check(): bool
    {
        return Session::get('admin_id') !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get('admin_id');
        return $id !== null ? (int) $id : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => self::id(),
            'name' => Session::get('admin_name'),
            'email' => Session::get('admin_email'),
            'role' => Session::get('admin_role'),
        ];
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin'));
            header('Location: ' . rtrim($dir, '/') . '/login.php');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        $role = (string) Session::get('admin_role', '');
        if (!in_array($role, $roles, true)) {
            Session::flash('error', 'Bu işlem için yetkiniz yok.');
            $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin'));
            header('Location: ' . rtrim($dir, '/') . '/index.php');
            exit;
        }
    }

    public static function isSuperAdmin(): bool
    {
        return Session::get('admin_role') === 'super_admin';
    }

    private static function isLockedOut(string $email, string $ip): bool
    {
        $pdo = Database::connection();
        $max = Config::int('LOGIN_MAX_ATTEMPTS', 5);
        $minutes = Config::int('LOGIN_LOCKOUT_MINUTES', 15);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS cnt FROM login_attempts
             WHERE email = ? AND ip_address = ? AND attempted_at >= (UTC_TIMESTAMP() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$email, $ip, $minutes]);
        $count = (int) ($stmt->fetch()['cnt'] ?? 0);
        return $count >= $max;
    }

    private static function recordAttempt(string $email, string $ip): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, UTC_TIMESTAMP())');
        $stmt->execute([$email, $ip]);
    }

    private static function clearAttempts(string $email, string $ip): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = ? AND ip_address = ?');
        $stmt->execute([$email, $ip]);
    }
}
