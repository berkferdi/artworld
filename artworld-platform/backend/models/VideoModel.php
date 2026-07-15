<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class VideoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ["v.status = 'published'"];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['featured'])) {
            $where[] = 'v.is_featured = 1';
        }
        if (!empty($filters['search'])) {
            $where[] = '(v.title LIKE ? OR v.description LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sort = $filters['sort'] ?? 'latest';
        $orderBy = match ($sort) {
            'oldest' => 'v.published_at ASC',
            'popular' => '(SELECT COUNT(*) FROM video_views vv WHERE vv.video_id = v.id) DESC, v.published_at DESC',
            default => 'v.published_at DESC',
        };

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM videos v LEFT JOIN categories c ON c.id = v.category_id WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $sql = "SELECT v.*, c.name AS category_name, c.slug AS category_slug
                FROM videos v
                LEFT JOIN categories c ON c.id = v.category_id
                WHERE {$whereSql}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => array_map([$this, 'mapItem'], $stmt->fetchAll()),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / max(1, $perPage)),
            ],
        ];
    }

    public function findByIdOrSlug(string $idOrSlug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT v.*, c.name AS category_name, c.slug AS category_slug
             FROM videos v
             LEFT JOIN categories c ON c.id = v.category_id
             WHERE v.status = 'published' AND (v.slug = ? OR v.id = ?)
             LIMIT 1"
        );
        $stmt->execute([$idOrSlug, ctype_digit($idOrSlug) ? (int) $idOrSlug : 0]);
        $row = $stmt->fetch();
        return $row ? $this->mapItem($row, true) : null;
    }

    public function featured(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT v.*, c.name AS category_name, c.slug AS category_slug
             FROM videos v LEFT JOIN categories c ON c.id = v.category_id
             WHERE v.status = 'published' AND v.is_featured = 1
             ORDER BY v.published_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn ($r) => $this->mapItem($r), $stmt->fetchAll());
    }

    public function latest(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT v.*, c.name AS category_name, c.slug AS category_slug
             FROM videos v LEFT JOIN categories c ON c.id = v.category_id
             WHERE v.status = 'published'
             ORDER BY v.published_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn ($r) => $this->mapItem($r), $stmt->fetchAll());
    }

    public function related(int $videoId, ?int $categoryId, int $limit = 6): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT v.*, c.name AS category_name, c.slug AS category_slug
             FROM videos v LEFT JOIN categories c ON c.id = v.category_id
             WHERE v.status = 'published' AND v.id != ? AND (? IS NULL OR v.category_id = ?)
             ORDER BY v.published_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $videoId, PDO::PARAM_INT);
        $stmt->bindValue(2, $categoryId, $categoryId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(3, $categoryId, $categoryId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn ($r) => $this->mapItem($r), $stmt->fetchAll());
    }

    private function mapItem(array $row, bool $detail = false): array
    {
        $item = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $detail ? ($row['description'] ?? '') : mb_substr((string) ($row['description'] ?? ''), 0, 160),
            'thumbnail' => Media::url($row['thumbnail'] ?? null),
            'video_url' => Media::url($row['video_url']),
            'video_type' => $row['video_type'],
            'duration_seconds' => $row['duration_seconds'] !== null ? (int) $row['duration_seconds'] : null,
            'is_featured' => (bool) $row['is_featured'],
            'published_at' => $row['published_at'],
            'category' => [
                'name' => $row['category_name'] ?? null,
                'slug' => $row['category_slug'] ?? null,
            ],
        ];
        if ($detail) {
            $item['category_id'] = $row['category_id'] !== null ? (int) $row['category_id'] : null;
        }
        return $item;
    }
}
