<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class AuthorModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ["status = 'active'"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(name LIKE ? OR bio LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM authors WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $stmt = $this->pdo->prepare(
            "SELECT id, name, slug, bio, photo, status, created_at, updated_at
             FROM authors WHERE {$whereSql}
             ORDER BY name ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
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

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM authors WHERE slug = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $item = $this->mapItem($row);
        $item['recent_news'] = $this->recentNews((int) $row['id'], (string) $row['name']);
        return $item;
    }

    private function recentNews(int $authorId, string $authorName, int $limit = 10): array
    {
        $hasAuthorId = $this->newsHasAuthorIdColumn();

        if ($hasAuthorId) {
            $sql = "SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.author, n.published_at,
                           c.name AS category_name, c.slug AS category_slug
                    FROM news n
                    LEFT JOIN categories c ON c.id = n.category_id
                    WHERE n.status = 'published' AND (n.author_id = ? OR n.author = ?)
                    ORDER BY n.published_at DESC
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $authorId, PDO::PARAM_INT);
            $stmt->bindValue(2, $authorName);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        } else {
            $sql = "SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.author, n.published_at,
                           c.name AS category_name, c.slug AS category_slug
                    FROM news n
                    LEFT JOIN categories c ON c.id = n.category_id
                    WHERE n.status = 'published' AND n.author = ?
                    ORDER BY n.published_at DESC
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $authorName);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'slug' => $row['slug'],
                'summary' => $row['summary'],
                'cover_image' => Media::url($row['cover_image'] ?? null),
                'author' => $row['author'] ?? null,
                'published_at' => $row['published_at'],
                'category' => [
                    'name' => $row['category_name'] ?? null,
                    'slug' => $row['category_slug'] ?? null,
                ],
            ];
        }, $stmt->fetchAll());
    }

    private function newsHasAuthorIdColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $this->pdo->query(
                "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news' AND COLUMN_NAME = 'author_id'"
            );
            $cached = ((int) ($stmt->fetch()['c'] ?? 0)) > 0;
        } catch (\Throwable) {
            $cached = false;
        }
        return $cached;
    }

    private function mapItem(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'bio' => $row['bio'] ?? null,
            'photo' => Media::url($row['photo'] ?? null),
        ];
    }
}
