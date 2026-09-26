<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class BannerModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function active(string $position = 'home_hero'): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, n.slug AS news_slug, n.summary AS news_summary, c.name AS category_name
             FROM banners b
             LEFT JOIN news n ON b.target_type = 'news' AND n.id = b.target_id
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE b.status = 'active'
               AND b.position = ?
               AND (b.starts_at IS NULL OR b.starts_at <= UTC_TIMESTAMP())
               AND (b.expires_at IS NULL OR b.expires_at >= UTC_TIMESTAMP())
             ORDER BY b.sort_order ASC, b.id DESC"
        );
        $stmt->execute([$position]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'image' => Media::url($row['image']),
                'summary' => $row['news_summary'] ?? null,
                'category' => [
                    'name' => $row['category_name'] ?? null,
                    'slug' => null,
                ],
                'target_type' => $row['target_type'],
                'target_id' => $row['target_id'] !== null ? (int) $row['target_id'] : null,
                'target_url' => $row['target_url'],
                'news_slug' => $row['news_slug'] ?? null,
                'position' => $row['position'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }, $stmt->fetchAll());
    }
}
