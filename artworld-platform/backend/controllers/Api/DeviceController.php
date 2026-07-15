<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class DeviceController
{
    public function register(): void
    {
        $token = Request::string('fcm_token');
        $platform = strtolower(Request::string('platform'));
        $deviceUuid = Request::string('device_uuid', Request::header('X-Device-UUID') ?? '');
        $appVersion = Request::string('app_version');

        $errors = [];
        if ($token === '') {
            $errors['fcm_token'] = 'FCM token zorunludur.';
        }
        if (!in_array($platform, ['android', 'ios'], true)) {
            $errors['platform'] = 'Platform android veya ios olmalıdır.';
        }

        if ($errors !== []) {
            Response::validation('Doğrulama hatası', $errors);
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "INSERT INTO device_tokens (device_uuid, fcm_token, platform, app_version, status, last_seen_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
                device_uuid = COALESCE(VALUES(device_uuid), device_uuid),
                platform = VALUES(platform),
                app_version = VALUES(app_version),
                status = 'active',
                last_seen_at = UTC_TIMESTAMP(),
                updated_at = UTC_TIMESTAMP()"
        );
        $stmt->execute([
            $deviceUuid !== '' ? $deviceUuid : null,
            $token,
            $platform,
            $appVersion !== '' ? $appVersion : null,
        ]);

        Response::success([
            'registered' => true,
            'platform' => $platform,
        ], 'Cihaz kaydedildi', 201);
    }
}
