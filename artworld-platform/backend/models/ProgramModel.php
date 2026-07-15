<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Media;
use PDO;

final class ProgramModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ["p.status = 'active'"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE ? OR p.description LIKE ? OR p.presenter LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM programs p WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $stmt = $this->pdo->prepare(
            "SELECT p.* FROM programs p WHERE {$whereSql}
             ORDER BY p.sort_order ASC, p.title ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => array_map([$this, 'mapProgram'], $stmt->fetchAll()),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / max(1, $perPage)),
            ],
        ];
    }

    public function allActive(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM programs WHERE status = 'active' ORDER BY sort_order ASC, title ASC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map([$this, 'mapProgram'], $stmt->fetchAll());
    }

    public function findByIdOrSlug(string $idOrSlug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM programs WHERE status = 'active' AND (slug = ? OR id = ?) LIMIT 1"
        );
        $stmt->execute([$idOrSlug, ctype_digit($idOrSlug) ? (int) $idOrSlug : 0]);
        $row = $stmt->fetch();
        return $row ? $this->mapProgram($row, true) : null;
    }

    public function episodes(int $programId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM program_episodes WHERE program_id = ? AND status = 'published'");
        $countStmt->execute([$programId]);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $stmt = $this->pdo->prepare(
            "SELECT * FROM program_episodes
             WHERE program_id = ? AND status = 'published'
             ORDER BY published_at DESC, episode_number DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute([$programId]);

        return [
            'items' => array_map([$this, 'mapEpisode'], $stmt->fetchAll()),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / max(1, $perPage)),
            ],
        ];
    }

    public function findEpisode(string $idOrSlug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.*, p.title AS program_title, p.slug AS program_slug
             FROM program_episodes e
             INNER JOIN programs p ON p.id = e.program_id
             WHERE e.status = 'published' AND (e.slug = ? OR e.id = ?)
             LIMIT 1"
        );
        $stmt->execute([$idOrSlug, ctype_digit($idOrSlug) ? (int) $idOrSlug : 0]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $item = $this->mapEpisode($row, true);
        $item['program'] = [
            'id' => (int) $row['program_id'],
            'title' => $row['program_title'],
            'slug' => $row['program_slug'],
        ];
        return $item;
    }

    private function mapProgram(array $row, bool $detail = false): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $detail ? ($row['description'] ?? '') : mb_substr((string) ($row['description'] ?? ''), 0, 160),
            'cover_image' => Media::url($row['cover_image'] ?? null),
            'presenter' => $row['presenter'],
            'broadcast_day' => $row['broadcast_day'],
            'broadcast_time' => $row['broadcast_time'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }

    private function mapEpisode(array $row, bool $detail = false): array
    {
        return [
            'id' => (int) $row['id'],
            'program_id' => (int) $row['program_id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $detail ? ($row['description'] ?? '') : mb_substr((string) ($row['description'] ?? ''), 0, 160),
            'thumbnail' => Media::url($row['thumbnail'] ?? null),
            'video_url' => Media::url($row['video_url']),
            'video_type' => $row['video_type'],
            'episode_number' => $row['episode_number'] !== null ? (int) $row['episode_number'] : null,
            'duration_seconds' => $row['duration_seconds'] !== null ? (int) $row['duration_seconds'] : null,
            'published_at' => $row['published_at'],
        ];
    }
}
