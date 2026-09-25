<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Media;

/**
 * Video storage / playback helpers.
 *
 * Modes (FILE_SERVER_MODE):
 * - local  : store under backend/uploads (default; MEDIA_URL public playback)
 * - sftp   : upload via SFTP to FILE_SERVER_HOST (requires credentials)
 * - http   : POST multipart to FILE_SERVER_UPLOAD_URL
 *
 * Discovery note (2026-09-25 cloud probe):
 * 193.35.155.55 accepts TCP :80/:443 but does not complete HTTP/TLS.
 * SSH :22 not reachable from this environment. Until remote storage is
 * reachable with credentials, use mode=local (api.artworldapi.com.tr MEDIA_URL).
 */
final class FileServerService
{
    public function mode(): string
    {
        $mode = strtolower((string) Config::get('FILE_SERVER_MODE', 'local'));
        return in_array($mode, ['local', 'sftp', 'http'], true) ? $mode : 'local';
    }

    public function isEnabled(): bool
    {
        return Config::bool('FILE_SERVER_ENABLED', true);
    }

    public function publicBaseUrl(): string
    {
        $base = rtrim((string) Config::get('FILE_SERVER_PUBLIC_BASE_URL', ''), '/');
        if ($base !== '') {
            return $base;
        }
        return rtrim((string) Config::get('MEDIA_URL', ''), '/');
    }

    /**
     * Store an uploaded video and return public + storage metadata.
     *
     * @return array{success:bool,message?:string,path?:string,playback_url?:string,file_path?:string,mime?:string,size?:int,video_type?:string}
     */
    public function storeUploadedVideo(array $file): array
    {
        $uploader = new UploadService();
        $local = $uploader->uploadVideo($file, 'videos');
        if (!($local['success'] ?? false)) {
            return $local;
        }

        $relativePath = (string) $local['path'];
        $absoluteLocal = dirname(__DIR__) . '/' . trim((string) Config::get('UPLOAD_PATH', 'uploads'), '/') . $relativePath;
        $mime = (string) ($local['mime'] ?? 'video/mp4');
        $size = (int) ($local['size'] ?? 0);
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $videoType = $ext === 'm3u8' ? 'hls' : 'mp4';

        if ($this->mode() === 'local' || !$this->isEnabled()) {
            return [
                'success' => true,
                'path' => $relativePath,
                'file_path' => $relativePath,
                'playback_url' => Media::url($relativePath) ?? $relativePath,
                'mime' => $mime,
                'size' => $size,
                'video_type' => $videoType,
                'message' => 'Video yerel depolamaya yüklendi.',
            ];
        }

        if ($this->mode() === 'sftp') {
            $remote = $this->uploadViaSftp($absoluteLocal, $relativePath);
            if (!($remote['success'] ?? false)) {
                // Keep local file as fallback so admin content is not lost.
                return [
                    'success' => true,
                    'path' => $relativePath,
                    'file_path' => $relativePath,
                    'playback_url' => Media::url($relativePath) ?? $relativePath,
                    'mime' => $mime,
                    'size' => $size,
                    'video_type' => $videoType,
                    'message' => 'SFTP başarısız; yerel kopya kullanıldı: ' . ($remote['message'] ?? ''),
                ];
            }
            return [
                'success' => true,
                'path' => $remote['public_path'] ?? $relativePath,
                'file_path' => $remote['remote_path'] ?? $relativePath,
                'playback_url' => $remote['playback_url'] ?? (Media::url($relativePath) ?? $relativePath),
                'mime' => $mime,
                'size' => $size,
                'video_type' => $videoType,
            ];
        }

        if ($this->mode() === 'http') {
            $remote = $this->uploadViaHttp($absoluteLocal, basename($relativePath), $mime);
            if (!($remote['success'] ?? false)) {
                return [
                    'success' => true,
                    'path' => $relativePath,
                    'file_path' => $relativePath,
                    'playback_url' => Media::url($relativePath) ?? $relativePath,
                    'mime' => $mime,
                    'size' => $size,
                    'video_type' => $videoType,
                    'message' => 'HTTP upload başarısız; yerel kopya kullanıldı: ' . ($remote['message'] ?? ''),
                ];
            }
            return [
                'success' => true,
                'path' => $remote['public_path'] ?? $relativePath,
                'file_path' => $remote['remote_path'] ?? $relativePath,
                'playback_url' => $remote['playback_url'] ?? (Media::url($relativePath) ?? $relativePath),
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
        // Never delete absolute remote YouTube/external URLs
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

    /** @return array{reachable:bool,detail:string} */
    public function probe(): array
    {
        $host = (string) Config::get('FILE_SERVER_HOST', '193.35.155.55');
        $mode = $this->mode();
        if ($mode === 'local') {
            $base = dirname(__DIR__) . '/' . trim((string) Config::get('UPLOAD_PATH', 'uploads'), '/') . '/videos';
            $ok = is_dir($base) || @mkdir($base, 0755, true);
            return [
                'reachable' => (bool) $ok,
                'detail' => $ok
                    ? 'Local upload storage ready (' . $base . ')'
                    : 'Local upload storage not writable',
            ];
        }

        $url = 'http://' . $host . '/';
        $ch = curl_init($url);
        if ($ch === false) {
            return ['reachable' => false, 'detail' => 'curl_init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code > 0) {
            return ['reachable' => true, 'detail' => "HTTP {$code} from {$host}"];
        }
        return ['reachable' => false, 'detail' => $err !== '' ? $err : "No HTTP response from {$host}"];
    }

    /** @return array{success:bool,message?:string,remote_path?:string,public_path?:string,playback_url?:string} */
    private function uploadViaSftp(string $localAbsolute, string $relativePath): array
    {
        if (!function_exists('ssh2_connect')) {
            return ['success' => false, 'message' => 'php-ssh2 eklentisi yok'];
        }
        $host = (string) Config::get('FILE_SERVER_HOST', '');
        $port = Config::int('FILE_SERVER_PORT', 22);
        $user = (string) Config::get('FILE_SERVER_USER', '');
        $pass = (string) Config::get('FILE_SERVER_PASSWORD', '');
        $remoteBase = rtrim((string) Config::get('FILE_SERVER_BASE_PATH', '/var/www/artworld'), '/');
        if ($host === '' || $user === '') {
            return ['success' => false, 'message' => 'FILE_SERVER_HOST/USER tanımlı değil'];
        }
        $conn = @ssh2_connect($host, $port);
        if ($conn === false) {
            return ['success' => false, 'message' => 'SFTP bağlantısı kurulamadı'];
        }
        if ($pass !== '') {
            if (!@ssh2_auth_password($conn, $user, $pass)) {
                return ['success' => false, 'message' => 'SFTP kimlik doğrulama başarısız'];
            }
        } else {
            $key = (string) Config::get('FILE_SERVER_SSH_KEY', '');
            if ($key === '' || !@ssh2_auth_pubkey_file($conn, $user, $key . '.pub', $key)) {
                return ['success' => false, 'message' => 'SFTP anahtar kimlik doğrulama başarısız'];
            }
        }
        $sftp = @ssh2_sftp($conn);
        if ($sftp === false) {
            return ['success' => false, 'message' => 'SFTP oturumu açılamadı'];
        }
        $remotePath = $remoteBase . $relativePath;
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

        $publicPath = $relativePath;
        $playback = rtrim($this->publicBaseUrl(), '/') . $relativePath;
        return [
            'success' => true,
            'remote_path' => $remotePath,
            'public_path' => $publicPath,
            'playback_url' => $playback,
        ];
    }

    /** @return array{success:bool,message?:string,remote_path?:string,public_path?:string,playback_url?:string} */
    private function uploadViaHttp(string $localAbsolute, string $filename, string $mime): array
    {
        $endpoint = (string) Config::get('FILE_SERVER_UPLOAD_URL', '');
        $token = (string) Config::get('FILE_SERVER_UPLOAD_TOKEN', '');
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
