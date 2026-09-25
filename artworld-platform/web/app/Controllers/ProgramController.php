<?php

declare(strict_types=1);

namespace Web\Controllers;

final class ProgramController extends BaseController
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $response = $this->api->get('/programs', ['page' => $page, 'per_page' => 24]);
        $programs = [];
        $meta = [];
        if (is_array($response) && ($response['success'] ?? false) === true) {
            $programs = is_array($response['data'] ?? null) ? $response['data'] : [];
            $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
        }

        $this->render('pages/programs', [
            'title' => 'Programlar | Art World TV',
            'metaDescription' => 'Art World TV programları',
            'programs' => $programs,
            'meta' => $meta,
            'bodyClass' => 'page-programs',
        ]);
    }

    public function show(string $slug): void
    {
        $program = $this->api->data('/programs/' . rawurlencode($slug), [], null);
        if (!is_array($program) || empty($program['slug'])) {
            $this->notFound('Program bulunamadı');
            return;
        }

        $this->render('pages/program-show', [
            'title' => ($program['title'] ?? 'Program') . ' | Art World TV',
            'metaDescription' => truncate((string) ($program['description'] ?? ''), 160),
            'ogImage' => (string) ($program['cover_image'] ?? ''),
            'canonical' => absolute_url(program_url((string) $program['slug'])),
            'program' => $program,
            'episodes' => is_array($program['episodes'] ?? null) ? $program['episodes'] : [],
            'bodyClass' => 'page-program',
        ]);
    }
}
