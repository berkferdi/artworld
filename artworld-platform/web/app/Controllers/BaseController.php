<?php

declare(strict_types=1);

namespace Web\Controllers;

use Web\Core\ApiClient;
use Web\Core\Config;
use Web\Core\View;

abstract class BaseController
{
    protected ApiClient $api;

    public function __construct()
    {
        $this->api = new ApiClient();
    }

    /** @return array<string, mixed> */
    protected function settings(): array
    {
        $data = $this->api->data('/settings/public', [], []);
        return is_array($data) ? $data : [];
    }

    /** @return list<array<string, mixed>> */
    protected function categories(): array
    {
        $data = $this->api->data('/categories', [], []);
        return is_array($data) ? $data : [];
    }

    /** @return list<array<string, mixed>> */
    protected function menus(): array
    {
        $data = $this->api->data('/menus', ['location' => 'header'], null);
        if (is_array($data) && isset($data[0]['items']) && is_array($data[0]['items'])) {
            return $data[0]['items'];
        }
        if (is_array($data) && isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }
        return default_menu();
    }

    /** @param array<string, mixed> $extra */
    protected function layoutData(array $extra = []): array
    {
        $settings = $this->settings();
        $siteName = (string) ($settings['app_name'] ?? Config::get('SITE_NAME', 'Art World'));
        return array_merge([
            'settings' => $settings,
            'categories' => $this->categories(),
            'menuItems' => $this->menus(),
            'siteName' => $siteName,
            'siteTagline' => (string) Config::get('SITE_TAGLINE', ''),
            'title' => $siteName,
            'metaDescription' => (string) Config::get('DEFAULT_META_DESCRIPTION', ''),
            'canonical' => absolute_url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'),
            'ogImage' => (string) ($settings['app_logo'] ?? Config::get('DEFAULT_OG_IMAGE', '')),
            'ogType' => 'website',
            'jsonLd' => null,
        ], $extra);
    }

    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $this->layoutData($data));
    }

    protected function notFound(string $message = 'Sayfa bulunamadı'): void
    {
        http_response_code(404);
        $this->render('errors/404', [
            'title' => $message,
            'metaDescription' => $message,
        ]);
    }
}
