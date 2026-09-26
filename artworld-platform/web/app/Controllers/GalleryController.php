<?php

declare(strict_types=1);

namespace Web\Controllers;

final class GalleryController extends BaseController
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $response = $this->api->get('/galleries', ['page' => $page, 'per_page' => 24]);
        $items = [];
        $meta = [];
        if (is_array($response) && ($response['success'] ?? false) === true) {
            $items = is_array($response['data'] ?? null) ? $response['data'] : [];
            $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
        }

        $this->render('pages/galleries', [
            'title' => 'Foto Galeri | Art World',
            'metaDescription' => 'Art World foto galerileri',
            'galleries' => $items,
            'meta' => $meta,
            'bodyClass' => 'page-galleries',
        ]);
    }

    public function show(string $slug): void
    {
        $gallery = $this->api->data('/galleries/' . rawurlencode($slug), [], null);
        if (!is_array($gallery) || empty($gallery['slug'])) {
            $this->notFound('Galeri bulunamadı');
            return;
        }

        $this->render('pages/gallery-show', [
            'title' => ($gallery['title'] ?? 'Galeri') . ' | Art World',
            'metaDescription' => truncate((string) ($gallery['description'] ?? ''), 160),
            'ogImage' => (string) ($gallery['cover_image'] ?? ''),
            'gallery' => $gallery,
            'images' => is_array($gallery['images'] ?? null) ? $gallery['images'] : [],
            'bodyClass' => 'page-gallery',
        ]);
    }
}
