<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Media;
use PDO;

final class SettingsService
{
    public function all(): array
    {
        $pdo = Database::connection();
        $rows = $pdo->query('SELECT setting_key, setting_value, setting_type FROM app_settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $this->cast($row['setting_value'], $row['setting_type']);
        }
        return $settings;
    }

    public function publicSettings(): array
    {
        $all = $this->all();
        $keys = [
            'app_name', 'app_logo', 'primary_color', 'secondary_color',
            'breaking_news_enabled', 'live_stream_enabled', 'maintenance_mode',
            'contact_email', 'contact_phone', 'website_url',
            'facebook_url', 'instagram_url', 'youtube_url', 'x_url', 'about_text',
        ];

        $public = [];
        foreach ($keys as $key) {
            $public[$key] = $all[$key] ?? null;
        }

        if (!empty($public['app_logo'])) {
            $public['app_logo'] = Media::url((string) $public['app_logo']);
        }

        return $public;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT setting_value, setting_type FROM app_settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) {
            return $default;
        }
        return $this->cast($row['setting_value'], $row['setting_type']);
    }

    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, setting_type, updated_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), updated_at = UTC_TIMESTAMP()'
        );
        $stmt->execute([$key, is_bool($value) ? ($value ? '1' : '0') : (string) $value, $type]);
    }

    private function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (str_contains((string) $value, '.') ? (float) $value : (int) $value) : 0,
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}
