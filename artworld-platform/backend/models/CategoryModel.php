<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class CategoryModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function allActive(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, slug, description, image, sort_order
             FROM categories WHERE status = 'active'
             ORDER BY sort_order ASC, name ASC"
        );
        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'image' => Media::url($row['image']),
                'sort_order' => (int) $row['sort_order'],
            ];
        }, $stmt->fetchAll());
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'image' => Media::url($row['image']),
            'sort_order' => (int) $row['sort_order'],
        ];
    }
}
