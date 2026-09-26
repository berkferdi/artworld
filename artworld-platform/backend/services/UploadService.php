<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Security;

final class UploadService
{
    private const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const VIDEO_MIMES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];

    public function uploadImage(array $file, string $subdir = 'images'): array
    {
        return $this->upload($file, $subdir, self::IMAGE_MIMES, Config::int('UPLOAD_MAX_IMAGE_MB', 5));
    }

    public function uploadVideo(array $file, string $subdir = 'videos'): array
    {
        return $this->upload($file, $subdir, self::VIDEO_MIMES, Config::int('UPLOAD_MAX_VIDEO_MB', 200));
    }

    private function upload(array $file, string $subdir, array $allowedMimes, int $maxMb): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => $this->uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE))];
        }

        $tmp = $file['tmp_name'] ?? '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['success' => false, 'message' => 'Geçersiz yükleme isteği.'];
        }

        $size = (int) ($file['size'] ?? 0);
        $maxBytes = $maxMb * 1024 * 1024;
        if ($size <= 0 || $size > $maxBytes) {
            return ['success' => false, 'message' => "Dosya boyutu {$maxMb} MB sınırını aşıyor."];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        if (!isset($allowedMimes[$mime])) {
            return ['success' => false, 'message' => 'Desteklenmeyen dosya türü.'];
        }

        $ext = $allowedMimes[$mime];
        $original = (string) ($file['name'] ?? 'file');
        $safeBase = Security::slugify(pathinfo($original, PATHINFO_FILENAME));
        $filename = sprintf('%s-%s.%s', date('YmdHis'), bin2hex(random_bytes(8)), $ext);

        $year = date('Y');
        $month = date('m');
        $relativeDir = trim($subdir, '/') . '/' . $year . '/' . $month;
        $basePath = dirname(__DIR__) . '/' . trim(Config::get('UPLOAD_PATH', 'uploads'), '/');
        $absoluteDir = $basePath . '/' . $relativeDir;

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            return ['success' => false, 'message' => 'Yükleme klasörü oluşturulamadı.'];
        }

        $absolutePath = $absoluteDir . '/' . $filename;
        if (!move_uploaded_file($tmp, $absolutePath)) {
            return ['success' => false, 'message' => 'Dosya kaydedilemedi.'];
        }

        @chmod($absolutePath, 0644);

        $relativePath = '/' . $relativeDir . '/' . $filename;
        $thumbnail = null;

        if (isset(self::IMAGE_MIMES[$mime])) {
            $thumbnail = $this->createThumbnail($absolutePath, $relativePath, $ext);
        }

        return [
            'success' => true,
            'path' => $relativePath,
            'thumbnail' => $thumbnail,
            'mime' => $mime,
            'size' => $size,
            'original_name' => $safeBase . '.' . $ext,
        ];
    }

    private function createThumbnail(string $sourcePath, string $relativePath, string $ext): ?string
    {
        if (!extension_loaded('gd')) {
            return $relativePath;
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return $relativePath;
        }

        [$width, $height] = $info;
        $maxWidth = 480;
        if ($width <= $maxWidth) {
            return $relativePath;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) round(($height / $width) * $newWidth);

        $src = match ($info['mime'] ?? '') {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($src === false) {
            return $relativePath;
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if ($dst === false) {
            imagedestroy($src);
            return $relativePath;
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $thumbRel = preg_replace('#^/images/#', '/thumbnails/', $relativePath) ?? $relativePath;
        $thumbAbs = dirname(__DIR__) . '/uploads' . $thumbRel;
        $thumbDir = dirname($thumbAbs);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $ok = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($dst, $thumbAbs, 82),
            'png' => imagepng($dst, $thumbAbs, 6),
            'webp' => function_exists('imagewebp') ? imagewebp($dst, $thumbAbs, 82) : false,
            default => false,
        };

        imagedestroy($src);
        imagedestroy($dst);

        return $ok ? $thumbRel : $relativePath;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu sunucu limitini aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı.',
            UPLOAD_ERR_EXTENSION => 'Yükleme bir eklenti tarafından durduruldu.',
            default => 'Dosya yükleme hatası.',
        };
    }
}
