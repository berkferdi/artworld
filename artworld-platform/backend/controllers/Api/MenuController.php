<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\MenuModel;

final class MenuController
{
    public function index(): void
    {
        $location = Request::string('location');
        $items = (new MenuModel())->byLocation($location !== '' ? $location : null);
        Response::success($items, 'Menüler');
    }
}
