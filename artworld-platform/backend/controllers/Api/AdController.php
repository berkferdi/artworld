<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\AdModel;

final class AdController
{
    public function index(): void
    {
        $position = Request::string('position');
        $items = (new AdModel())->active($position !== '' ? $position : null);
        Response::success($items, 'Reklamlar');
    }
}
