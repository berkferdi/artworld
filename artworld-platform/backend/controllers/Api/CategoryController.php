<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\CategoryModel;
use App\Models\NewsModel;

final class CategoryController
{
    public function index(): void
    {
        Response::success((new CategoryModel())->allActive(), 'Kategoriler');
    }

    public function news(string $slug): void
    {
        $category = (new CategoryModel())->findBySlug($slug);
        if (!$category) {
            Response::notFound('Kategori bulunamadı');
        }

        $result = (new NewsModel())->list([
            'category' => $slug,
            'search' => Request::string('search'),
            'sort' => Request::string('sort', 'latest'),
        ], Request::page(), Request::perPage());

        Response::success([
            'category' => $category,
            'news' => $result['items'],
        ], 'Kategori haberleri', 200, $result['meta']);
    }
}
