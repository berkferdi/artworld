<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Models\NewsModel;

final class NewsController
{
    public function index(): void
    {
        $model = new NewsModel();
        $result = $model->list([
            'category' => Request::string('category'),
            'featured' => Request::query('featured'),
            'search' => Request::string('search'),
            'sort' => Request::string('sort', 'latest'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Haber listesi', 200, $result['meta']);
    }

    public function featured(): void
    {
        Response::success((new NewsModel())->featured(Request::int('limit', 10)), 'Öne çıkan haberler');
    }

    public function breaking(): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT n.id, n.title, n.slug, n.summary, n.cover_image, n.published_at,
                    c.name AS category_name, c.slug AS category_slug
             FROM news n
             LEFT JOIN categories c ON c.id = n.category_id
             WHERE n.status = 'published' AND n.is_breaking = 1
             ORDER BY n.published_at DESC LIMIT 20"
        );
        $rows = $stmt->fetchAll();
        $items = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'slug' => $row['slug'],
                'summary' => $row['summary'],
                'cover_image' => \App\Core\Media::url($row['cover_image']),
                'published_at' => $row['published_at'],
                'category' => [
                    'name' => $row['category_name'],
                    'slug' => $row['category_slug'],
                ],
            ];
        }, $rows);

        Response::success($items, 'Son dakika haberleri');
    }

    public function show(string $idOrSlug): void
    {
        $item = (new NewsModel())->findByIdOrSlug($idOrSlug);
        if (!$item) {
            Response::notFound('Haber bulunamadı');
        }

        $related = (new NewsModel())->related((int) $item['id'], $item['category_id'] ?? null);
        $item['related'] = $related;

        Response::success($item, 'Haber detayı');
    }

    public function view(string $id): void
    {
        if (!ctype_digit($id)) {
            Response::validation('Geçersiz haber ID');
        }

        $pdo = Database::connection();
        $check = $pdo->prepare("SELECT id FROM news WHERE id = ? AND status = 'published' LIMIT 1");
        $check->execute([(int) $id]);
        if (!$check->fetch()) {
            Response::notFound('Haber bulunamadı');
        }

        $deviceUuid = Request::string('device_uuid', Request::header('X-Device-UUID') ?? '');
        $ipHash = Security::hashIp(Security::clientIp());

        // Prevent excessive duplicate counts from same device within 30 minutes
        if ($deviceUuid !== '') {
            $dup = $pdo->prepare(
                'SELECT id FROM news_views WHERE news_id = ? AND device_uuid = ? AND created_at >= (UTC_TIMESTAMP() - INTERVAL 30 MINUTE) LIMIT 1'
            );
            $dup->execute([(int) $id, $deviceUuid]);
            if ($dup->fetch()) {
                Response::success(['counted' => false], 'Görüntülenme zaten kaydedilmiş');
            }
        }

        $ins = $pdo->prepare('INSERT INTO news_views (news_id, device_uuid, ip_hash, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP())');
        $ins->execute([(int) $id, $deviceUuid !== '' ? $deviceUuid : null, $ipHash]);

        Response::success(['counted' => true], 'Görüntülenme kaydedildi', 201);
    }
}
