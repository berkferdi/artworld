<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class GalleryModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ["status = 'published'"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(title LIKE ? OR description LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM galleries WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $stmt = $this->pdo->prepare(
            "SELECT id, title, slug, description, cover_image, status, created_at, updated_at
             FROM galleries WHERE {$whereSql}
             ORDER BY created_at DESC, id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => array_map([$this, 'mapListItem'], $stmt->fetchAll()),
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
        $stmt = $this->pdo->prepare("SELECT * FROM galleries WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $item = $this->mapListItem($row);
        $item['description'] = $row['description'] ?? null;
        $item['images'] = $this->imagesFor((int) $row['id']);
        return $item;
    }

    public function allPublishedSlugs(): array
    {
        $stmt = $this->pdo->query("SELECT slug, updated_at FROM galleries WHERE status = 'published' ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    private function imagesFor(int $galleryId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, image, caption, sort_order FROM gallery_images
             WHERE gallery_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$galleryId]);

        return array_map(static function (array $img): array {
            return [
                'id' => (int) $img['id'],
                'image' => Media::url($img['image']),
                'caption' => $img['caption'] ?? null,
                'sort_order' => (int) $img['sort_order'],
            ];
        }, $stmt->fetchAll());
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $row['description'] ?? null,
            'cover_image' => Media::url($row['cover_image'] ?? null),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
