<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Models\LiveStreamModel;

final class LiveController
{
    public function index(): void
    {
        $live = (new LiveStreamModel())->active();
        if (!$live) {
            Response::success(null, 'Aktif canlı yayın bulunamadı');
        }
        Response::success($live, 'Canlı yayın');
    }
}
