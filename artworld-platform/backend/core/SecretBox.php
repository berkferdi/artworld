<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight secret encryption for admin-stored credentials.
 * Uses APP_KEY (or falls back to a derived key from APP_URL + DB_PASSWORD).
 * Format: enc:v1:<base64(iv+ciphertext+tag)>
 */
final class SecretBox
{
    private const PREFIX = 'enc:v1:';

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }
        if (str_starts_with($plaintext, self::PREFIX)) {
            return $plaintext;
        }
        $key = self::key();
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Secret encryption failed.');
        }
        return self::PREFIX . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }
        if (!str_starts_with($stored, self::PREFIX)) {
            // Legacy plaintext — still readable, migrate on next save.
            return $stored;
        }
        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 28) {
            return '';
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }

    public static function isEncrypted(?string $stored): bool
    {
        return is_string($stored) && str_starts_with($stored, self::PREFIX);
    }

    private static function key(): string
    {
        $appKey = (string) Config::get('APP_KEY', '');
        if ($appKey !== '') {
            return hash('sha256', $appKey, true);
        }
        $material = (string) Config::get('APP_URL', 'artworld')
            . '|' . (string) Config::get('DB_PASSWORD', 'artworld');
        return hash('sha256', $material, true);
    }
}
