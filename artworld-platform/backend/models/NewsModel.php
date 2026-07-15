<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class NewsModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ["n.status = 'published'"];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['featured'])) {
            $where[] = 'n.is_featured = 1';
        }
        if (!empty($filters['breaking'])) {
            $where[] = 'n.is_breaking = 1';
        }
        if (!empty($filters['search'])) {
            $where[] = '(n.title LIKE ? OR n.summary LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sort = $filters['sort'] ?? 'latest';
        $orderBy = match ($sort) {
            'oldest' => 'n.published_at ASC',
            'popular' => '(SELECT COUNT(*) FROM news_views nv WHERE nv.news_id = n.id) DESC, n.published_at DESC',
            default => 'n.published_at DESC',
        };

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $sql = "SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.author, n.is_featured, n.is_breaking,
                       n.published_at, n.category_id, c.name AS category_name, c.slug AS category_slug
                FROM news n
                LEFT JOIN categories c ON c.id = n.category_id
                WHERE {$whereSql}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'items' => array_map([$this, 'mapListItem'], $rows),
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
        $sql = "SELECT n.*, c.name AS category_name, c.slug AS category_slug
                FROM news n
                LEFT JOIN categories c ON c.id = n.category_id
                WHERE n.status = 'published' AND (n.slug = ? OR n.id = ?)
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idOrSlug, ctype_digit($idOrSlug) ? (int) $idOrSlug : 0]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $imagesStmt = $this->pdo->prepare('SELECT id, image_url, caption, sort_order FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC');
        $imagesStmt->execute([(int) $row['id']]);
        $images = $imagesStmt->fetchAll();

        $item = $this->mapDetail($row);
        $item['gallery'] = array_map(static function (array $img): array {
            return [
                'id' => (int) $img['id'],
                'image_url' => Media::url($img['image_url']),
                'caption' => $img['caption'],
                'sort_order' => (int) $img['sort_order'],
            ];
        }, $images);

        return $item;
    }

    public function featured(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.author, n.is_featured, n.is_breaking,
                    n.published_at, c.name AS category_name, c.slug AS category_slug
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'published' AND n.is_featured = 1
             ORDER BY n.published_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'mapListItem'], $stmt->fetchAll());
    }

    public function latest(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.author, n.is_featured, n.is_breaking,
                    n.published_at, c.name AS category_name, c.slug AS category_slug
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'published'
             ORDER BY n.published_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'mapListItem'], $stmt->fetchAll());
    }

    public function related(int $newsId, ?int $categoryId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.author, n.is_featured, n.is_breaking,
                    n.published_at, c.name AS category_name, c.slug AS category_slug
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'published' AND n.id != ? AND (? IS NULL OR n.category_id = ?)
             ORDER BY n.published_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $newsId, PDO::PARAM_INT);
        $stmt->bindValue(2, $categoryId, $categoryId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(3, $categoryId, $categoryId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'mapListItem'], $stmt->fetchAll());
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'summary' => $row['summary'],
            'cover_image' => Media::url($row['cover_image'] ?? null),
            'author' => $row['author'] ?? null,
            'is_featured' => (bool) ($row['is_featured'] ?? false),
            'is_breaking' => (bool) ($row['is_breaking'] ?? false),
            'published_at' => $row['published_at'],
            'category' => [
                'name' => $row['category_name'] ?? null,
                'slug' => $row['category_slug'] ?? null,
            ],
        ];
    }

    private function mapDetail(array $row): array
    {
        $item = $this->mapListItem($row);
        $item['content'] = $row['content'] ?? '';
        $item['category_id'] = $row['category_id'] !== null ? (int) $row['category_id'] : null;
        $item['status'] = $row['status'];
        return $item;
    }
}
