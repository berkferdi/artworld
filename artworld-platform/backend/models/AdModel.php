<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class AdModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function active(?string $position = null): array
    {
        $where = [
            "status = 'active'",
            '(start_at IS NULL OR start_at <= UTC_TIMESTAMP())',
            '(end_at IS NULL OR end_at >= UTC_TIMESTAMP())',
        ];
        $params = [];

        if ($position !== null && $position !== '') {
            $where[] = 'position = ?';
            $params[] = $position;
        }

        $whereSql = implode(' AND ', $where);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ads WHERE {$whereSql} ORDER BY sort_order ASC, id DESC"
        );
        $stmt->execute($params);

        return array_map([$this, 'mapItem'], $stmt->fetchAll());
    }

    private function mapItem(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'position' => $row['position'],
            'type' => $row['type'],
            'content' => $row['content'] ?? null,
            'image' => Media::url($row['image'] ?? null),
            'link' => $row['link'] ?? null,
            'sort_order' => (int) $row['sort_order'],
        ];
    }
}
