<?php

declare(strict_types=1);

namespace App\Core;

final class Media
{
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $base = rtrim((string) Config::get('MEDIA_URL', ''), '/');
        $path = '/' . ltrim($path, '/');

        return $base . $path;
    }

    public static function urls(array $items, array $fields): array
    {
        foreach ($items as &$item) {
            foreach ($fields as $field) {
                if (isset($item[$field])) {
                    $item[$field] = self::url($item[$field]);
                }
            }
        }
        unset($item);
        return $items;
    }

    public static function mapFields(array $item, array $fields): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $item)) {
                $item[$field] = self::url($item[$field]);
            }
        }
        return $item;
    }
}
