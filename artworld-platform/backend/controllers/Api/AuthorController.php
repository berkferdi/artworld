<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\AuthorModel;

final class AuthorController
{
    public function index(): void
    {
        $result = (new AuthorModel())->list([
            'search' => Request::string('search'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Yazar listesi', 200, $result['meta']);
    }

    public function show(string $slug): void
    {
        $item = (new AuthorModel())->findBySlug($slug);
        if (!$item) {
            Response::notFound('Yazar bulunamadı');
        }
        Response::success($item, 'Yazar detayı');
    }
}
