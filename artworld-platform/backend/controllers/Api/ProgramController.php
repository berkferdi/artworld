<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Models\ProgramModel;

final class ProgramController
{
    public function index(): void
    {
        $result = (new ProgramModel())->list([
            'search' => Request::string('search'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Program listesi', 200, $result['meta']);
    }

    public function show(string $idOrSlug): void
    {
        $model = new ProgramModel();
        $item = $model->findByIdOrSlug($idOrSlug);
        if (!$item) {
            Response::notFound('Program bulunamadı');
        }

        $episodes = $model->episodes((int) $item['id'], 1, 50);
        $item['episodes'] = $episodes['items'];

        Response::success($item, 'Program detayı');
    }

    public function episodes(string $idOrSlug): void
    {
        $model = new ProgramModel();
        $program = $model->findByIdOrSlug($idOrSlug);
        if (!$program) {
            Response::notFound('Program bulunamadı');
        }

        $result = $model->episodes((int) $program['id'], Request::page(), Request::perPage());
        Response::success([
            'program' => $program,
            'episodes' => $result['items'],
        ], 'Program bölümleri', 200, $result['meta']);
    }
}
