<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\NewsModel;
use App\Models\ProgramModel;
use App\Models\VideoModel;

final class SearchController
{
    public function index(): void
    {
        $q = Request::string('q', Request::string('search'));
        if (mb_strlen($q) < 2) {
            Response::validation('Arama için en az 2 karakter girin.', ['q' => 'En az 2 karakter']);
        }

        $limit = min(20, max(1, Request::int('per_page', 10)));

        $news = (new NewsModel())->list(['search' => $q, 'sort' => 'latest'], 1, $limit);
        $videos = (new VideoModel())->list(['search' => $q, 'sort' => 'latest'], 1, $limit);
        $programs = (new ProgramModel())->list(['search' => $q], 1, $limit);

        Response::success([
            'query' => $q,
            'news' => $news['items'],
            'videos' => $videos['items'],
            'programs' => $programs['items'],
        ], 'Arama sonuçları');
    }
}
