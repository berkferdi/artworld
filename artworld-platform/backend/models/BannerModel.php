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
            "SELECT * FROM banners
             WHERE status = 'active'
               AND position = ?
               AND (starts_at IS NULL OR starts_at <= UTC_TIMESTAMP())
               AND (expires_at IS NULL OR expires_at >= UTC_TIMESTAMP())
             ORDER BY sort_order ASC, id DESC"
        );
        $stmt->execute([$position]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'image' => Media::url($row['image']),
                'target_type' => $row['target_type'],
                'target_id' => $row['target_id'] !== null ? (int) $row['target_id'] : null,
                'target_url' => $row['target_url'],
                'position' => $row['position'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }, $stmt->fetchAll());
    }
}
