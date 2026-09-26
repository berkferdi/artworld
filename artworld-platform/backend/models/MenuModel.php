<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class MenuModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function byLocation(?string $location = null): array
    {
        $where = ["status = 'active'"];
        $params = [];

        if ($location !== null && $location !== '') {
            $where[] = 'location = ?';
            $params[] = $location;
        }

        $whereSql = implode(' AND ', $where);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM menus WHERE {$whereSql} ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute($params);
        $menus = $stmt->fetchAll();

        return array_map(function (array $menu): array {
            return [
                'id' => (int) $menu['id'],
                'title' => $menu['title'],
                'location' => $menu['location'],
                'sort_order' => (int) $menu['sort_order'],
                'items' => $this->itemsFor((int) $menu['id']),
            ];
        }, $menus);
    }

    private function itemsFor(int $menuId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, parent_id, title, url, target, sort_order
             FROM menu_items
             WHERE menu_id = ? AND status = 'active'
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([$menuId]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'parent_id' => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'title' => $row['title'],
                'url' => $row['url'],
                'target' => $row['target'] ?? '_self',
                'sort_order' => (int) $row['sort_order'],
            ];
        }, $stmt->fetchAll());
    }
}
