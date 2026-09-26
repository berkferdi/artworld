<?php

declare(strict_types=1);

namespace Web\Controllers;

final class ArchiveController extends BaseController
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $category = trim((string) ($_GET['category'] ?? ''));
        $date = trim((string) ($_GET['date'] ?? ''));

        $query = ['page' => $page, 'per_page' => 20];
        if ($category !== '') {
            $query['category'] = $category;
        }
        if ($date !== '') {
            $query['date'] = $date;
        }

        $response = $this->api->get('/archive', $query);
        $news = [];
        $meta = [];
        if (is_array($response) && ($response['success'] ?? false) === true) {
            $data = $response['data'] ?? [];
            $news = is_array($data['news'] ?? null) ? $data['news'] : (is_array($data) ? $data : []);
            $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
        } else {
            // Fallback to news list until archive endpoint is live
            $response = $this->api->get('/news', $query);
            if (is_array($response) && ($response['success'] ?? false) === true) {
                $news = is_array($response['data'] ?? null) ? $response['data'] : [];
                $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
            }
        }

        $this->render('pages/archive', [
            'title' => 'Arşiv | Art World',
            'metaDescription' => 'Art World haber arşivi',
            'news' => $news,
            'meta' => $meta,
            'category' => $category,
            'date' => $date,
            'bodyClass' => 'page-archive',
        ]);
    }
}
