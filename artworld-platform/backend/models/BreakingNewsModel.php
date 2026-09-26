<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class BreakingNewsModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function active(): array
    {
        $stmt = $this->pdo->query(
            "SELECT b.id, b.news_id, b.title, b.target_url, b.sort_order,
                    n.slug AS news_slug
             FROM breaking_news b
             LEFT JOIN news n ON n.id = b.news_id
             WHERE b.status = 'active'
               AND (b.starts_at IS NULL OR b.starts_at <= UTC_TIMESTAMP())
               AND (b.expires_at IS NULL OR b.expires_at >= UTC_TIMESTAMP())
             ORDER BY b.sort_order ASC, b.id DESC"
        );

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'news_id' => $row['news_id'] !== null ? (int) $row['news_id'] : null,
                'news_slug' => $row['news_slug'] ?? null,
                'target_url' => $row['target_url'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }, $stmt->fetchAll());
    }
}
