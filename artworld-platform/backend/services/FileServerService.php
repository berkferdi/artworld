<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Media;

/**
 * Video storage / playback helpers.
 *
 * Config resolution order: Admin DB settings (FileServerConfigService) → .env
 *
 * Modes:
 * - local  : store under backend/uploads (MEDIA_URL / public base for playback)
 * - sftp   : upload via SFTP to configured host
 * - http   : POST multipart to upload URL
 */
final class FileServerService
{
    /** @var array<string, mixed>|null */
    private ?array $cfg = null;

    public function __construct(private readonly ?FileServerConfigService $configService = null)
    {
    }

    /** @return array<string, mixed> */
    private function cfg(): array
    {
        if ($this->cfg === null) {
            $svc = $this->configService ?? new FileServerConfigService();
            $this->cfg = $svc->all();
        }
        return $this->cfg;
    }

    public function mode(): string
    {
        $mode = strtolower((string) ($this->cfg()['mode'] ?? 'local'));
        return in_array($mode, ['local', 'sftp', 'http'], true) ? $mode : 'local';
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->cfg()['enabled'] ?? true);
    }

    public function publicBaseUrl(): string
    {
        $base = rtrim((string) ($this->cfg()['public_base_url'] ?? ''), '/');
        if ($base !== '') {
            return $base;
        }
        return rtrim((string) Config::get('MEDIA_URL', ''), '/');
    }

    /**
     * @return array{success:bool,message?:string,path?:string,playback_url?:string,file_path?:string,mime?:string,size?:int,video_type?:string}
     */
    public function storeUploadedVideo(array $file): array
    {
        $this->log('UPLOAD_STARTED');
        $uploader = new UploadService();
        $local = $uploader->uploadVideo($file, 'videos');
        if (!($local['success'] ?? false)) {
            $this->log('UPLOAD_FAILED local: ' . ($local['message'] ?? ''));
            return $local;
        }

        $relativePath = (string) $local['path'];
        $absoluteLocal = dirname(__DIR__) . '/' . trim((string) Config::get('UPLOAD_PATH', 'uploads'), '/') . $relativePath;
        $mime = (string) ($local['mime'] ?? 'video/mp4');
        $size = (int) ($local['size'] ?? 0);
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $videoType = $ext === 'm3u8' ? 'hls' : 'mp4';

        if ($this->mode() === 'local' || !$this->isEnabled()) {
            $this->log('UPLOAD_SUCCESS local ' . $relativePath);
            return [
                'success' => true,
                'path' => $relativePath,
                'file_path' => $relativePath,
                'playback_url' => $this->playbackUrlFor($relativePath),
                'mime' => $mime,
                'size' => $size,
                'video_type' => $videoType,
                'message' => 'Video yerel depolamaya yüklendi.',
            ];
        }

        if ($this->mode() === 'sftp') {
            $remote = $this->uploadViaSftp($absoluteLocal, $relativePath);
            if (!($remote['success'] ?? false)) {
                $this->log('UPLOAD_FAILED sftp fallback local: ' . ($remote['message'] ?? ''));
                return [
                    'success' => true,
                    'path' => $relativePath,
                    'file_path' => $relativePath,
                    'playback_url' => $this->playbackUrlFor($relativePath),
                    'mime' => $mime,
                    'size' => $size,
                    'video_type' => $videoType,
                    'message' => 'SFTP başarısız; yerel kopya kullanıldı: ' . ($remote['message'] ?? ''),
                ];
            }
            $this->log('UPLOAD_SUCCESS sftp');
            return [
                'success' => true,
                'path' => $remote['public_path'] ?? $relativePath,
                'file_path' => $remote['remote_path'] ?? $relativePath,
                'playback_url' => $remote['playback_url'] ?? $this->playbackUrlFor($relativePath),
                'mime' => $mime,
                'size' => $size,
                'video_type' => $videoType,
            ];
        }

        if ($this->mode() === 'http') {
            $remote = $this->uploadViaHttp($absoluteLocal, basename($relativePath), $mime);
            if (!($remote['success'] ?? false)) {
                $this->log('UPLOAD_FAILED http fallback local: ' . ($remote['message'] ?? ''));
                return [
                    'success' => true,
                    'path' => $relativePath,
                    'file_path' => $relativePath,
                    'playback_url' => $this->playbackUrlFor($relativePath),
                    'mime' => $mime,
                    'size' => $size,
                    'video_type' => $videoType,
                    'message' => 'HTTP upload başarısız; yerel kopya kullanıldı: ' . ($remote['message'] ?? ''),
                ];
            }
            $this->log('UPLOAD_SUCCESS http');
            return [
                'success' => true,
                'path' => $remote['public_path'] ?? $relativePath,
                'file_path' => $remote['remote_path'] ?? $relativePath,
                'playback_url' => $remote['playback_url'] ?? $this->playbackUrlFor($relativePath),
                'mime' => $mime,
                'size' => $size,
                'video_type' => $videoType,
            ];
        }

        return $local;
    }

    public function deleteStoredFile(?string $filePath): bool
    {
        if ($filePath === null || $filePath === '') {
            return false;
        }
        if (preg_match('#^https?://#i', $filePath)) {
            return false;
        }
        if (!str_starts_with($filePath, '/videos/') && !str_starts_with($filePath, 'videos/')) {
            return false;
        }

        $relative = '/' . ltrim($filePath, '/');
        $absolute = dirname(__DIR__) . '/' . trim((string) Config::get('UPLOAD_PATH', 'uploads'), '/') . $relative;
        $realBase = realpath(dirname(__DIR__) . '/' . trim((string) Config::get('UPLOAD_PATH', 'uploads'), '/'));
        $realFile = realpath($absolute);
        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase)) {
            return false;
        }
        if (is_file($realFile)) {
            return @unlink($realFile);
        }
        return false;
    }

    /**
     * Deep connection probe for admin UI.
     *
     * @return array{reachable:bool,status:string,detail:string,checks:array<string,string>}
     */
    public function probe(): array
    {
        $cfg = $this->cfg();
        $mode = $this->mode();
        $checks = [];
        $timeout = max(3, (int) ($cfg['timeout'] ?? 8));

        if ($mode === 'local') {
            $base = dirname(__DIR__) . '/' . trim((string) Config::get('UPLOAD_PATH', 'uploads'), '/') . '/videos';
            $ok = is_dir($base) || @mkdir($base, 0755, true);
            $writable = $ok && is_writable($base);
            $checks['local_dir'] = $ok ? 'OK' : 'FAIL';
            $checks['writable'] = $writable ? 'OK' : 'FAIL';
            $detail = $writable
                ? 'Local upload storage ready (' . $base . ')'
                : 'Local upload storage not writable';
            return [
                'reachable' => $writable,
                'status' => $writable ? 'online' : 'offline',
                'detail' => $detail,
                'checks' => $checks,
            ];
        }

        $host = (string) ($cfg['host'] ?? '');
        if ($host === '') {
            return [
                'reachable' => false,
                'status' => 'pending',
                'detail' => 'Host tanımlı değil',
                'checks' => ['host' => 'MISSING'],
            ];
        }

        $port = (int) ($cfg['port'] ?? ($mode === 'http' ? 443 : 22));
        $tcp = $this->tcpProbe($host, $port, $timeout);
        $checks['tcp_' . $port] = $tcp ? 'OK' : 'FAIL';

        if ($mode === 'sftp') {
            if (!$tcp) {
                $this->log('FILE_SERVER_CONNECTION_FAILED tcp ' . $host . ':' . $port);
                return [
                    'reachable' => false,
                    'status' => 'offline',
                    'detail' => "TCP {$host}:{$port} erişilemiyor (timeout/firewall)",
                    'checks' => $checks,
                ];
            }
            $auth = $this->sftpAuthProbe($host, $port, $cfg, $timeout);
            $checks['sftp_auth'] = $auth['ok'] ? 'OK' : 'FAIL';
            $checks['remote_path'] = $auth['path_ok'] ? 'OK' : ($auth['ok'] ? 'FAIL' : 'SKIP');
            return [
                'reachable' => $auth['ok'] && $auth['path_ok'],
                'status' => ($auth['ok'] && $auth['path_ok']) ? 'online' : 'offline',
                'detail' => $auth['detail'],
                'checks' => $checks,
            ];
        }

        // http mode
        $endpoint = (string) ($cfg['upload_url'] ?? '');
        $public = (string) ($cfg['public_base_url'] ?? '');
        $probeUrl = $public !== '' ? rtrim($public, '/') . '/' : ($endpoint !== '' ? $endpoint : 'http://' . $host . '/');
        $http = $this->httpProbe($probeUrl, $timeout);
        $checks['http'] = $http['ok'] ? 'HTTP ' . $http['code'] : 'FAIL';
        $detail = $http['ok']
            ? "HTTP {$http['code']} from {$probeUrl}"
            : ($http['error'] !== '' ? $http['error'] : "No HTTP response from {$probeUrl}");
        if (!$http['ok']) {
            $this->log('FILE_SERVER_CONNECTION_FAILED http ' . $detail);
        }
        return [
            'reachable' => $http['ok'],
            'status' => $http['ok'] ? 'online' : 'offline',
            'detail' => $detail,
            'checks' => $checks,
        ];
    }

    private function playbackUrlFor(string $relativePath): string
    {
        $public = $this->publicBaseUrl();
        if ($public !== '' && str_starts_with($relativePath, '/')) {
            // Prefer configured public media base when set; Media::url for local MEDIA_URL
            $configured = rtrim((string) ($this->cfg()['public_base_url'] ?? ''), '/');
            if ($configured !== '') {
                return $configured . $relativePath;
            }
        }
        return Media::url($relativePath) ?? $relativePath;
    }

    private function tcpProbe(string $host, int $port, int $timeout): bool
    {
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp === false) {
            return false;
        }
        fclose($fp);
        return true;
    }

    /** @param array<string, mixed> $cfg */
    private function sftpAuthProbe(string $host, int $port, array $cfg, int $timeout): array
    {
        if (!function_exists('ssh2_connect')) {
            return ['ok' => false, 'path_ok' => false, 'detail' => 'php-ssh2 eklentisi yüklü değil'];
        }
        $user = (string) ($cfg['user'] ?? '');
        if ($user === '') {
            return ['ok' => false, 'path_ok' => false, 'detail' => 'SFTP kullanıcı adı eksik'];
        }
        $conn = @ssh2_connect($host, $port);
        if ($conn === false) {
            return ['ok' => false, 'path_ok' => false, 'detail' => 'SFTP TCP bağlantısı kurulamadı'];
        }
        $pass = (string) ($cfg['password'] ?? '');
        $key = (string) ($cfg['ssh_key'] ?? '');
        $authed = false;
        if ($pass !== '') {
            $authed = @ssh2_auth_password($conn, $user, $pass);
        } elseif ($key !== '' && is_file($key)) {
            $authed = @ssh2_auth_pubkey_file($conn, $user, $key . '.pub', $key);
        }
        if (!$authed) {
            return ['ok' => false, 'path_ok' => false, 'detail' => 'SFTP kimlik doğrulama başarısız'];
        }
        $sftp = @ssh2_sftp($conn);
        if ($sftp === false) {
            return ['ok' => false, 'path_ok' => false, 'detail' => 'SFTP oturumu açılamadı'];
        }
        $base = rtrim((string) ($cfg['base_path'] ?? '/media/videos'), '/');
        $stat = @ssh2_sftp_stat($sftp, $base);
        if ($stat === false) {
            @ssh2_sftp_mkdir($sftp, $base, 0755, true);
            $stat = @ssh2_sftp_stat($sftp, $base);
        }
        $pathOk = $stat !== false;
        return [
            'ok' => true,
            'path_ok' => $pathOk,
            'detail' => $pathOk
                ? "SFTP OK — {$user}@{$host}:{$port} path {$base}"
                : "SFTP auth OK fakat path erişilemiyor: {$base}",
        ];
    }

    /** @return array{ok:bool,code:int,error:string} */
    private function httpProbe(string $url, int $timeout): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'code' => 0, 'error' => 'curl_init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => $code > 0 && $code < 500, 'code' => $code, 'error' => $err];
    }

    /** @return array{success:bool,message?:string,remote_path?:string,public_path?:string,playback_url?:string} */
    private function uploadViaSftp(string $localAbsolute, string $relativePath): array
    {
        if (!function_exists('ssh2_connect')) {
            return ['success' => false, 'message' => 'php-ssh2 eklentisi yok'];
        }
        $cfg = $this->cfg();
        $host = (string) ($cfg['host'] ?? '');
        $port = (int) ($cfg['port'] ?? 22);
        $user = (string) ($cfg['user'] ?? '');
        $pass = (string) ($cfg['password'] ?? '');
        $remoteBase = rtrim((string) ($cfg['base_path'] ?? '/media/videos'), '/');
        if ($host === '' || $user === '') {
            return ['success' => false, 'message' => 'FILE_SERVER host/user tanımlı değil'];
        }
        $conn = @ssh2_connect($host, $port);
        if ($conn === false) {
            return ['success' => false, 'message' => 'SFTP bağlantısı kurulamadı'];
        }
        $authed = false;
        if ($pass !== '') {
            $authed = @ssh2_auth_password($conn, $user, $pass);
        } else {
            $key = (string) ($cfg['ssh_key'] ?? '');
            if ($key !== '') {
                $authed = @ssh2_auth_pubkey_file($conn, $user, $key . '.pub', $key);
            }
        }
        if (!$authed) {
            return ['success' => false, 'message' => 'SFTP kimlik doğrulama başarısız'];
        }
        $sftp = @ssh2_sftp($conn);
        if ($sftp === false) {
            return ['success' => false, 'message' => 'SFTP oturumu açılamadı'];
        }
        // Keep year/month structure under remote base
        $remoteRel = ltrim($relativePath, '/');
        if (str_starts_with($remoteRel, 'videos/')) {
            $remoteRel = substr($remoteRel, strlen('videos/'));
        }
        $remotePath = $remoteBase . '/' . $remoteRel;
        $remoteDir = dirname($remotePath);
        @ssh2_sftp_mkdir($sftp, $remoteDir, 0755, true);
        $stream = @fopen('ssh2.sftp://' . intval($sftp) . $remotePath, 'w');
        if ($stream === false) {
            return ['success' => false, 'message' => 'Uzak dosya yazılamadı'];
        }
        $in = fopen($localAbsolute, 'r');
        if ($in === false) {
            fclose($stream);
            return ['success' => false, 'message' => 'Yerel dosya okunamadı'];
        }
        stream_copy_to_stream($in, $stream);
        fclose($in);
        fclose($stream);

        $publicPath = '/' . ltrim($remoteRel, '/');
        $playback = rtrim($this->publicBaseUrl(), '/') . '/videos/' . ltrim($remoteRel, '/');
        if (!str_contains($playback, '/videos/')) {
            $playback = rtrim($this->publicBaseUrl(), '/') . $relativePath;
        }
        return [
            'success' => true,
            'remote_path' => $remotePath,
            'public_path' => $relativePath,
            'playback_url' => $playback,
        ];
    }

    /** @return array{success:bool,message?:string,remote_path?:string,public_path?:string,playback_url?:string} */
    private function uploadViaHttp(string $localAbsolute, string $filename, string $mime): array
    {
        $cfg = $this->cfg();
        $endpoint = (string) ($cfg['upload_url'] ?? '');
        $token = (string) ($cfg['upload_token'] ?? '');
        if ($endpoint === '') {
            return ['success' => false, 'message' => 'FILE_SERVER_UPLOAD_URL tanımlı değil'];
        }
        if (!function_exists('curl_file_create')) {
            return ['success' => false, 'message' => 'curl_file_create yok'];
        }
        $cfile = curl_file_create($localAbsolute, $mime, $filename);
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return ['success' => false, 'message' => 'curl_init failed'];
        }
        $headers = ['Accept: application/json'];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => ['file' => $cfile],
            CURLOPT_TIMEOUT => 600,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            return ['success' => false, 'message' => $err !== '' ? $err : 'HTTP ' . $code];
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return ['success' => false, 'message' => 'Geçersiz upload yanıtı'];
        }
        $path = (string) ($json['path'] ?? $json['file_path'] ?? '');
        $playback = (string) ($json['playback_url'] ?? $json['url'] ?? '');
        if ($playback === '' && $path !== '') {
            $playback = rtrim($this->publicBaseUrl(), '/') . '/' . ltrim($path, '/');
        }
        return [
            'success' => true,
            'remote_path' => $path,
            'public_path' => $path,
            'playback_url' => $playback,
        ];
    }

    private function log(string $message): void
    {
        // Never log secrets
        $line = '[' . date('c') . '] ' . $message . PHP_EOL;
        $dir = dirname(__DIR__) . '/uploads';
        @file_put_contents($dir . '/file-server.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function resolveSourceType(?string $sourceType, ?string $videoType, ?string $url): string
    {
        if (in_array($sourceType, ['file_server', 'youtube', 'external'], true)) {
            return $sourceType;
        }
        if ($videoType === 'youtube' || ($url && preg_match('~youtu\.?be~i', $url))) {
            return 'youtube';
        }
        if (in_array($videoType, ['vimeo', 'external'], true)) {
            return 'external';
        }
        return 'file_server';
    }

    public static function videoTypeForSource(string $sourceType, ?string $explicitType = null): string
    {
        if ($sourceType === 'youtube') {
            return 'youtube';
        }
        if ($sourceType === 'external') {
            return in_array($explicitType, ['vimeo', 'external', 'hls'], true) ? $explicitType : 'external';
        }
        if (in_array($explicitType, ['mp4', 'hls'], true)) {
            return $explicitType;
        }
        return 'mp4';
    }
}
