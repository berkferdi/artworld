<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Controllers\Api\BannerController;
use App\Controllers\Api\CategoryController;
use App\Controllers\Api\DeviceController;
use App\Controllers\Api\EpisodeController;
use App\Controllers\Api\HomeController;
use App\Controllers\Api\LiveController;
use App\Controllers\Api\NewsController;
use App\Controllers\Api\ProgramController;
use App\Controllers\Api\SearchController;
use App\Controllers\Api\SettingsController;
use App\Controllers\Api\VideoController;
use App\Core\Response;
use App\Core\Router;
use App\Core\Security;
use App\Middleware\RateLimitMiddleware;

Security::applyCors();

try {
    RateLimitMiddleware::handle('api');

    $router = new Router();
    $prefix = '/api/v1';

    $router->get($prefix . '/home', [HomeController::class, 'index']);

    $router->get($prefix . '/news', [NewsController::class, 'index']);
    $router->get($prefix . '/news/featured', [NewsController::class, 'featured']);
    $router->get($prefix . '/news/breaking', [NewsController::class, 'breaking']);
    $router->get($prefix . '/news/{idOrSlug}', [NewsController::class, 'show']);
    $router->post($prefix . '/news/{id}/view', [NewsController::class, 'view']);

    $router->get($prefix . '/categories', [CategoryController::class, 'index']);
    $router->get($prefix . '/categories/{slug}/news', [CategoryController::class, 'news']);

    $router->get($prefix . '/videos', [VideoController::class, 'index']);
    $router->get($prefix . '/videos/{idOrSlug}', [VideoController::class, 'show']);
    $router->post($prefix . '/videos/{id}/view', [VideoController::class, 'view']);

    $router->get($prefix . '/programs', [ProgramController::class, 'index']);
    $router->get($prefix . '/programs/{idOrSlug}', [ProgramController::class, 'show']);
    $router->get($prefix . '/programs/{idOrSlug}/episodes', [ProgramController::class, 'episodes']);

    $router->get($prefix . '/episodes/{idOrSlug}', [EpisodeController::class, 'show']);
    $router->post($prefix . '/episodes/{id}/view', [EpisodeController::class, 'view']);

    $router->get($prefix . '/live', [LiveController::class, 'index']);
    $router->get($prefix . '/banners', [BannerController::class, 'index']);
    $router->get($prefix . '/settings/public', [SettingsController::class, 'publicSettings']);
    $router->get($prefix . '/search', [SearchController::class, 'index']);

    $router->post($prefix . '/devices/register', [DeviceController::class, 'register']);

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $router->dispatch($method, $uri);
} catch (Throwable $e) {
    if (\App\Core\Config::isDebug()) {
        Response::serverError($e->getMessage());
    }
    Response::serverError();
}
