<?php

declare(strict_types=1);

namespace Web\Controllers;

final class InterviewController extends BaseController
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $response = $this->api->get('/interviews', ['page' => $page, 'per_page' => 20]);
        $items = [];
        $meta = [];
        if (is_array($response) && ($response['success'] ?? false) === true) {
            $items = is_array($response['data'] ?? null) ? $response['data'] : [];
            $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
        }

        $this->render('pages/interviews', [
            'title' => 'Röportajlar | Art World',
            'metaDescription' => 'Art World röportajları',
            'interviews' => $items,
            'meta' => $meta,
            'bodyClass' => 'page-interviews',
        ]);
    }

    public function show(string $slug): void
    {
        $interview = $this->api->data('/interviews/' . rawurlencode($slug), [], null);
        if (!is_array($interview) || empty($interview['slug'])) {
            $this->notFound('Röportaj bulunamadı');
            return;
        }

        $this->render('pages/interview-show', [
            'title' => ($interview['title'] ?? 'Röportaj') . ' | Art World',
            'metaDescription' => truncate((string) ($interview['summary'] ?? ''), 160),
            'ogImage' => (string) ($interview['cover_image'] ?? ''),
            'interview' => $interview,
            'bodyClass' => 'page-interview',
        ]);
    }
}
