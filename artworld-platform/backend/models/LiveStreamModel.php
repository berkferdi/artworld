<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class LiveStreamModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function active(): ?array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM live_streams WHERE is_active = 1 ORDER BY updated_at DESC, id DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ? $this->map($row) : null;
    }

    private function map(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'stream_url' => Media::url($row['stream_url']),
            'stream_type' => $row['stream_type'],
            'poster_image' => Media::url($row['poster_image'] ?? null),
            'is_active' => (bool) $row['is_active'],
        ];
    }
}
