<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\PageModel;

final class PageController
{
    public function index(): void
    {
        $result = (new PageModel())->list([
            'search' => Request::string('search'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Sayfa listesi', 200, $result['meta']);
    }

    public function show(string $slug): void
    {
        $item = (new PageModel())->findBySlug($slug);
        if (!$item) {
            Response::notFound('Sayfa bulunamadı');
        }
        Response::success($item, 'Sayfa detayı');
    }
}
