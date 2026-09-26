<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\NewsModel;

final class ArchiveController
{
    public function index(): void
    {
        $date = Request::string('date');
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Response::validation('Geçersiz tarih formatı (YYYY-MM-DD).', ['date' => 'YYYY-MM-DD olmalı']);
        }

        $result = (new NewsModel())->list([
            'category' => Request::string('category'),
            'date' => $date,
            'search' => Request::string('search'),
            'sort' => Request::string('sort', 'latest'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Arşiv haberleri', 200, $result['meta']);
    }
}
