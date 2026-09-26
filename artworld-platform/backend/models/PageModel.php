<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PageModel
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
            $where[] = '(title LIKE ? OR content LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM pages WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $stmt = $this->pdo->prepare(
            "SELECT id, title, slug, meta_title, meta_description, status, created_at, updated_at
             FROM pages WHERE {$whereSql}
             ORDER BY title ASC
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
        $stmt = $this->pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ? $this->mapDetail($row) : null;
    }

    public function allPublishedSlugs(): array
    {
        $stmt = $this->pdo->query("SELECT slug, updated_at FROM pages WHERE status = 'published' ORDER BY title ASC");
        return $stmt->fetchAll();
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function mapDetail(array $row): array
    {
        $item = $this->mapListItem($row);
        $item['content'] = $row['content'] ?? '';
        $item['status'] = $row['status'];
        $item['created_at'] = $row['created_at'] ?? null;
        return $item;
    }
}
