<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\SecretBox;

/**
 * Resolves File Server configuration from DB settings (admin UI) with .env fallback.
 */
final class FileServerConfigService
{
    public const KEYS = [
        'file_server_enabled',
        'file_server_mode',
        'file_server_host',
        'file_server_port',
        'file_server_user',
        'file_server_password',
        'file_server_ssh_key',
        'file_server_base_path',
        'file_server_public_base_url',
        'file_server_upload_url',
        'file_server_upload_token',
        'file_server_timeout',
        'file_server_last_check_at',
        'file_server_last_check_ok',
        'file_server_last_check_detail',
    ];

    public function __construct(private readonly SettingsService $settings = new SettingsService())
    {
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        try {
            $data = [
                'enabled' => $this->bool('file_server_enabled', Config::bool('FILE_SERVER_ENABLED', true)),
                'mode' => $this->string('file_server_mode', (string) Config::get('FILE_SERVER_MODE', 'local')),
                'host' => $this->string('file_server_host', (string) Config::get('FILE_SERVER_HOST', '193.35.155.55')),
                'port' => $this->int('file_server_port', Config::int('FILE_SERVER_PORT', 22)),
                'user' => $this->string('file_server_user', (string) Config::get('FILE_SERVER_USER', '')),
                'password' => SecretBox::decrypt($this->raw('file_server_password', (string) Config::get('FILE_SERVER_PASSWORD', ''))),
                'ssh_key' => $this->string('file_server_ssh_key', (string) Config::get('FILE_SERVER_SSH_KEY', '')),
                'base_path' => $this->string('file_server_base_path', (string) Config::get('FILE_SERVER_BASE_PATH', '/media/videos')),
                'public_base_url' => $this->string('file_server_public_base_url', (string) Config::get('FILE_SERVER_PUBLIC_BASE_URL', '')),
                'upload_url' => $this->string('file_server_upload_url', (string) Config::get('FILE_SERVER_UPLOAD_URL', '')),
                'upload_token' => SecretBox::decrypt($this->raw('file_server_upload_token', (string) Config::get('FILE_SERVER_UPLOAD_TOKEN', ''))),
                'timeout' => $this->int('file_server_timeout', Config::int('FILE_SERVER_TIMEOUT', 8)),
                'last_check_at' => $this->string('file_server_last_check_at', ''),
                'last_check_ok' => $this->bool('file_server_last_check_ok', false),
                'last_check_detail' => $this->string('file_server_last_check_detail', ''),
            ];
            return $data;
        } catch (\Throwable) {
            return [
                'enabled' => Config::bool('FILE_SERVER_ENABLED', true),
                'mode' => (string) Config::get('FILE_SERVER_MODE', 'local'),
                'host' => (string) Config::get('FILE_SERVER_HOST', '193.35.155.55'),
                'port' => Config::int('FILE_SERVER_PORT', 22),
                'user' => (string) Config::get('FILE_SERVER_USER', ''),
                'password' => (string) Config::get('FILE_SERVER_PASSWORD', ''),
                'ssh_key' => (string) Config::get('FILE_SERVER_SSH_KEY', ''),
                'base_path' => (string) Config::get('FILE_SERVER_BASE_PATH', '/media/videos'),
                'public_base_url' => (string) Config::get('FILE_SERVER_PUBLIC_BASE_URL', ''),
                'upload_url' => (string) Config::get('FILE_SERVER_UPLOAD_URL', ''),
                'upload_token' => (string) Config::get('FILE_SERVER_UPLOAD_TOKEN', ''),
                'timeout' => Config::int('FILE_SERVER_TIMEOUT', 8),
                'last_check_at' => '',
                'last_check_ok' => false,
                'last_check_detail' => '',
            ];
        }
    }

    /** Safe for admin forms (secrets masked). */
    public function forAdminForm(): array
    {
        $all = $this->all();
        $all['password_set'] = $all['password'] !== '';
        $all['upload_token_set'] = $all['upload_token'] !== '';
        $all['password'] = '';
        $all['upload_token'] = '';
        return $all;
    }

    public function save(array $input, bool $updatePassword, bool $updateToken): void
    {
        $mode = (string) ($input['mode'] ?? 'local');
        if (!in_array($mode, ['local', 'sftp', 'http'], true)) {
            $mode = 'local';
        }

        $this->settings->set('file_server_enabled', !empty($input['enabled']) ? '1' : '0', 'boolean');
        $this->settings->set('file_server_mode', $mode, 'string');
        $this->settings->set('file_server_host', trim((string) ($input['host'] ?? '')), 'string');
        $this->settings->set('file_server_port', (string) max(1, (int) ($input['port'] ?? 22)), 'number');
        $this->settings->set('file_server_user', trim((string) ($input['user'] ?? '')), 'string');
        $this->settings->set('file_server_ssh_key', trim((string) ($input['ssh_key'] ?? '')), 'string');
        $this->settings->set('file_server_base_path', trim((string) ($input['base_path'] ?? '/media/videos')), 'string');
        $this->settings->set('file_server_public_base_url', rtrim(trim((string) ($input['public_base_url'] ?? '')), '/'), 'url');
        $this->settings->set('file_server_upload_url', trim((string) ($input['upload_url'] ?? '')), 'url');
        $this->settings->set('file_server_timeout', (string) max(3, min(60, (int) ($input['timeout'] ?? 8))), 'number');

        if ($updatePassword) {
            $password = (string) ($input['password'] ?? '');
            $this->settings->set('file_server_password', $password === '' ? '' : SecretBox::encrypt($password), 'string');
        }
        if ($updateToken) {
            $token = (string) ($input['upload_token'] ?? '');
            $this->settings->set('file_server_upload_token', $token === '' ? '' : SecretBox::encrypt($token), 'string');
        }
    }

    public function recordProbe(bool $ok, string $detail): void
    {
        $this->settings->set('file_server_last_check_at', gmdate('c'), 'string');
        $this->settings->set('file_server_last_check_ok', $ok ? '1' : '0', 'boolean');
        $this->settings->set('file_server_last_check_detail', mb_substr($detail, 0, 500), 'string');
    }

    private function raw(string $key, string $default): string
    {
        $v = $this->settings->get($key, null);
        return is_string($v) ? $v : $default;
    }

    private function string(string $key, string $default): string
    {
        $v = $this->settings->get($key, null);
        return is_string($v) && $v !== '' ? $v : $default;
    }

    private function bool(string $key, bool $default): bool
    {
        $v = $this->settings->get($key, null);
        if ($v === null) {
            return $default;
        }
        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    private function int(string $key, int $default): int
    {
        $v = $this->settings->get($key, null);
        if ($v === null || $v === '') {
            return $default;
        }
        return (int) $v;
    }
}
