<?php

declare(strict_types=1);

namespace Web\Controllers;

final class SearchController extends BaseController
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $results = [
            'query' => $q,
            'news' => [],
            'videos' => [],
            'programs' => [],
            'galleries' => [],
            'interviews' => [],
        ];

        if (mb_strlen($q) >= 2) {
            $data = $this->api->data('/search', ['q' => $q], []);
            if (is_array($data)) {
                $results = array_merge($results, $data);
                $results['query'] = $q;
            }
        }

        $this->render('pages/search', [
            'title' => ($q !== '' ? 'Arama: ' . $q : 'Arama') . ' | Art World',
            'metaDescription' => 'Art World arama sonuçları',
            'results' => $results,
            'q' => $q,
            'bodyClass' => 'page-search',
        ]);
    }
}
