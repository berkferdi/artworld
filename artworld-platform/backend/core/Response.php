<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(
        mixed $data = null,
        string $message = 'İşlem başarılı',
        int $status = 200,
        bool $success = true,
        array $errors = [],
        array $meta = []
    ): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'İşlem başarılı', int $status = 200, array $meta = []): void
    {
        self::json($data, $message, $status, true, [], $meta);
    }

    public static function error(string $message = 'Bir hata oluştu', int $status = 400, array $errors = []): void
    {
        self::json(null, $message, $status, false, $errors);
    }

    public static function notFound(string $message = 'İçerik bulunamadı'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Yetkisiz erişim'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Bu işlem için yetkiniz yok'): void
    {
        self::error($message, 403);
    }

    public static function validation(string $message = 'Doğrulama hatası', array $errors = []): void
    {
        self::error($message, 422, $errors);
    }

    public static function tooManyRequests(string $message = 'Çok fazla istek gönderildi. Lütfen daha sonra tekrar deneyin.'): void
    {
        self::error($message, 429);
    }

    public static function serverError(string $message = 'Sunucu hatası oluştu'): void
    {
        if (!Config::isDebug()) {
            $message = 'Sunucu hatası oluştu';
        }
        self::error($message, 500);
    }
}
