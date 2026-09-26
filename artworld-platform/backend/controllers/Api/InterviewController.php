<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\InterviewModel;

final class InterviewController
{
    public function index(): void
    {
        $result = (new InterviewModel())->list([
            'search' => Request::string('search'),
        ], Request::page(), Request::perPage());

        Response::success($result['items'], 'Röportaj listesi', 200, $result['meta']);
    }

    public function show(string $slug): void
    {
        $item = (new InterviewModel())->findBySlug($slug);
        if (!$item) {
            Response::notFound('Röportaj bulunamadı');
        }
        Response::success($item, 'Röportaj detayı');
    }
}
