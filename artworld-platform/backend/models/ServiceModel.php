<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ServiceModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function active(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, slug, type, config, status, sort_order
             FROM services WHERE status = 'active'
             ORDER BY sort_order ASC, name ASC"
        );

        return array_map([$this, 'mapItem'], $stmt->fetchAll());
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $where = ["status = 'active'"];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = 'type = ?';
            $params[] = $filters['type'];
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM services WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $stmt = $this->pdo->prepare(
            "SELECT id, name, slug, type, config, status, sort_order
             FROM services WHERE {$whereSql}
             ORDER BY sort_order ASC, name ASC
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

    private function mapItem(array $row): array
    {
        $config = $row['config'] ?? null;
        if (is_string($config) && $config !== '') {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : $config;
        }

        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'type' => $row['type'],
            'config' => $config,
            'sort_order' => (int) $row['sort_order'],
        ];
    }
}
