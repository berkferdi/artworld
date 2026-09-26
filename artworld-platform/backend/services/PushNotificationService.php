<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

final class PushNotificationService
{
    public function send(array $notification, array $tokens = []): array
    {
        $enabled = Config::bool('FCM_ENABLED', false);
        $serverKey = (string) Config::get('FCM_SERVER_KEY', '');

        if (!$enabled || $serverKey === '') {
            return [
                'success' => false,
                'configured' => false,
                'message' => 'FCM yapılandırması eksik. FCM_ENABLED ve FCM_SERVER_KEY ayarlarını kontrol edin.',
                'sent' => 0,
            ];
        }

        if ($tokens === []) {
            $pdo = Database::connection();
            $rows = $pdo->query("SELECT fcm_token FROM device_tokens WHERE status = 'active'")->fetchAll();
            $tokens = array_column($rows, 'fcm_token');
        }

        if ($tokens === []) {
            return [
                'success' => false,
                'configured' => true,
                'message' => 'Kayıtlı cihaz token\'ı bulunamadı.',
                'sent' => 0,
            ];
        }

        $sent = 0;
        $errors = [];

        // FCM legacy HTTP API (batches of 1000)
        foreach (array_chunk($tokens, 1000) as $chunk) {
            $payload = [
                'registration_ids' => array_values($chunk),
                'notification' => [
                    'title' => $notification['title'] ?? '',
                    'body' => $notification['body'] ?? '',
                    'image' => $notification['image'] ?? null,
                    'sound' => 'default',
                ],
                'data' => [
                    'target_type' => $notification['target_type'] ?? 'none',
                    'target_id' => (string) ($notification['target_id'] ?? ''),
                    'target_url' => $notification['target_url'] ?? '',
                ],
            ];

            $ch = curl_init('https://fcm.googleapis.com/fcm/send');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: key=' . $serverKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => 20,
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode >= 400) {
                $errors[] = $curlError !== '' ? $curlError : 'FCM isteği başarısız (HTTP ' . $httpCode . ')';
                continue;
            }

            $decoded = json_decode($response, true);
            $sent += (int) ($decoded['success'] ?? 0);
        }

        return [
            'success' => $sent > 0,
            'configured' => true,
            'message' => $sent > 0 ? "{$sent} cihaza bildirim gönderildi." : 'Bildirim gönderilemedi.',
            'sent' => $sent,
            'errors' => $errors,
        ];
    }
}
