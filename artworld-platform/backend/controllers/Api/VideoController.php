<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Models\VideoModel;

final class VideoController
{
    public function index(): void
    {
        $result = (new VideoModel())->list([
            'category' => Request::string('category'),
            'featured' => Request::query('featured'),
            'search' => Request::string('search'),
            'sort' => Request::string('sort', 'latest'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Video listesi', 200, $result['meta']);
    }

    public function show(string $idOrSlug): void
    {
        $item = (new VideoModel())->findByIdOrSlug($idOrSlug);
        if (!$item) {
            Response::notFound('Video bulunamadı');
        }
        $item['related'] = (new VideoModel())->related((int) $item['id'], $item['category_id'] ?? null);
        Response::success($item, 'Video detayı');
    }

    public function view(string $id): void
    {
        if (!ctype_digit($id)) {
            Response::validation('Geçersiz video ID');
        }

        $pdo = Database::connection();
        $check = $pdo->prepare("SELECT id FROM videos WHERE id = ? AND status = 'published' LIMIT 1");
        $check->execute([(int) $id]);
        if (!$check->fetch()) {
            Response::notFound('Video bulunamadı');
        }

        $deviceUuid = Request::string('device_uuid', Request::header('X-Device-UUID') ?? '');
        $watched = max(0, Request::int('watched_seconds', 0));
        $completed = Request::int('completed', 0) ? 1 : 0;
        $ipHash = Security::hashIp(Security::clientIp());

        if ($deviceUuid !== '') {
            $dup = $pdo->prepare(
                'SELECT id FROM video_views WHERE video_id = ? AND device_uuid = ? AND created_at >= (UTC_TIMESTAMP() - INTERVAL 30 MINUTE) LIMIT 1'
            );
            $dup->execute([(int) $id, $deviceUuid]);
            if ($row = $dup->fetch()) {
                $upd = $pdo->prepare('UPDATE video_views SET watched_seconds = GREATEST(watched_seconds, ?), completed = GREATEST(completed, ?) WHERE id = ?');
                $upd->execute([$watched, $completed, (int) $row['id']]);
                Response::success(['counted' => false, 'updated' => true], 'Görüntülenme güncellendi');
            }
        }

        $ins = $pdo->prepare(
            'INSERT INTO video_views (video_id, episode_id, device_uuid, ip_hash, watched_seconds, completed, created_at)
             VALUES (?, NULL, ?, ?, ?, ?, UTC_TIMESTAMP())'
        );
        $ins->execute([(int) $id, $deviceUuid !== '' ? $deviceUuid : null, $ipHash, $watched, $completed]);

        Response::success(['counted' => true], 'Görüntülenme kaydedildi', 201);
    }
}
