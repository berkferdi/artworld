<?php

declare(strict_types=1);

namespace Web\Controllers;

final class LiveController extends BaseController
{
    public function index(): void
    {
        $live = $this->api->data('/live', [], null);
        $this->render('pages/live', [
            'title' => 'Canlı Yayın | Art World TV',
            'metaDescription' => 'Art World TV canlı yayınını izleyin',
            'live' => is_array($live) ? $live : null,
            'bodyClass' => 'page-live',
        ]);
    }
}
