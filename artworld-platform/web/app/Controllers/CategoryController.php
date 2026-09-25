<?php

declare(strict_types=1);

namespace Web\Controllers;

final class CategoryController extends BaseController
{
    public function show(string $slug): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $response = $this->api->get('/categories/' . rawurlencode($slug) . '/news', [
            'page' => $page,
            'per_page' => 20,
        ]);

        if ($response === null || ($response['success'] ?? false) !== true) {
            $this->notFound('Kategori bulunamadı');
            return;
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $category = is_array($data['category'] ?? null) ? $data['category'] : ['name' => $slug, 'slug' => $slug];
        $news = is_array($data['news'] ?? null) ? $data['news'] : (is_array($data) && array_is_list($data) ? $data : []);
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];

        $this->render('pages/category', [
            'title' => ($category['name'] ?? 'Kategori') . ' | Art World',
            'metaDescription' => ($category['name'] ?? '') . ' kategorisindeki haberler',
            'canonical' => absolute_url(category_url($slug)),
            'category' => $category,
            'news' => $news,
            'meta' => $meta,
            'bodyClass' => 'page-category',
        ]);
    }
}
