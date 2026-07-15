<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Services\SettingsService;

final class SettingsController
{
    public function publicSettings(): void
    {
        Response::success((new SettingsService())->publicSettings(), 'Uygulama ayarları');
    }
}
