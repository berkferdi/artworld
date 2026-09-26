<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\ServiceModel;

final class ServiceController
{
    public function index(): void
    {
        $result = (new ServiceModel())->list([
            'type' => Request::string('type'),
        ], Request::page(), Request::perPage(50));

        Response::success($result['items'], 'Servisler', 200, $result['meta']);
    }
}
