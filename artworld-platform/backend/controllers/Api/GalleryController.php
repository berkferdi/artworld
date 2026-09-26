<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\GalleryModel;

final class GalleryController
{
    public function index(): void
    {
        $result = (new GalleryModel())->list([
            'search' => Request::string('search'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Galeri listesi', 200, $result['meta']);
    }

    public function show(string $slug): void
    {
        $item = (new GalleryModel())->findBySlug($slug);
        if (!$item) {
            Response::notFound('Galeri bulunamadı');
        }
        Response::success($item, 'Galeri detayı');
    }
}
