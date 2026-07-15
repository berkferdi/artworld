<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\BannerModel;

final class BannerController
{
    public function index(): void
    {
        $position = Request::string('position', 'home_hero');
        Response::success((new BannerModel())->active($position), 'Bannerlar');
    }
}
