<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Models\ProgramModel;

final class EpisodeController
{
    public function show(string $idOrSlug): void
    {
        $item = (new ProgramModel())->findEpisode($idOrSlug);
        if (!$item) {
            Response::notFound('Bölüm bulunamadı');
        }
        Response::success($item, 'Bölüm detayı');
    }

    public function view(string $id): void
    {
        if (!ctype_digit($id)) {
            Response::validation('Geçersiz bölüm ID');
        }

        $pdo = Database::connection();
        $check = $pdo->prepare("SELECT id FROM program_episodes WHERE id = ? AND status = 'published' LIMIT 1");
        $check->execute([(int) $id]);
        if (!$check->fetch()) {
            Response::notFound('Bölüm bulunamadı');
        }

        $deviceUuid = Request::string('device_uuid', Request::header('X-Device-UUID') ?? '');
        $watched = max(0, Request::int('watched_seconds', 0));
        $completed = Request::int('completed', 0) ? 1 : 0;
        $ipHash = Security::hashIp(Security::clientIp());

        $ins = $pdo->prepare(
            'INSERT INTO video_views (video_id, episode_id, device_uuid, ip_hash, watched_seconds, completed, created_at)
             VALUES (NULL, ?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        );
        $ins->execute([(int) $id, $deviceUuid !== '' ? $deviceUuid : null, $ipHash, $watched, $completed]);

        Response::success(['counted' => true], 'Görüntülenme kaydedildi', 201);
    }
}
