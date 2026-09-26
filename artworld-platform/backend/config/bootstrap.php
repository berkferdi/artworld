<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relative) . '.php';

    $base = dirname(__DIR__);
    $map = [
        'Core/' => $base . '/core/',
        'Controllers/' => $base . '/controllers/',
        'Models/' => $base . '/models/',
        'Middleware/' => $base . '/middleware/',
        'Services/' => $base . '/services/',
    ];

    foreach ($map as $ns => $dir) {
        if (str_starts_with($relativePath, $ns)) {
            $file = $dir . substr($relativePath, strlen($ns));
            if (is_file($file)) {
                require_once $file;
            }
            return;
        }
    }

    // Fallback for Controllers/Api nested namespaces
    $fallback = $base . '/' . strtolower(explode('/', $relativePath)[0]) . '/' . substr($relativePath, strpos($relativePath, '/') + 1);
    // Try lowercase first segment folder names used in project
    $parts = explode('/', $relativePath);
    if (count($parts) >= 2) {
        $folder = match ($parts[0]) {
            'Core' => 'core',
            'Controllers' => 'controllers',
            'Models' => 'models',
            'Middleware' => 'middleware',
            'Services' => 'services',
            default => strtolower($parts[0]),
        };
        $file = $base . '/' . $folder . '/' . implode('/', array_slice($parts, 1));
        if (is_file($file)) {
            require_once $file;
        }
    }
});

use App\Core\Config;
use App\Core\Security;

$envPath = dirname(__DIR__) . '/.env';
$examplePath = dirname(__DIR__) . '/.env.example';

if (!is_file($envPath) && is_file($examplePath)) {
    copy($examplePath, $envPath);
}

Config::load($envPath);

date_default_timezone_set((string) Config::get('TIMEZONE', 'UTC'));

error_reporting(E_ALL);
ini_set('display_errors', Config::isDebug() ? '1' : '0');
ini_set('log_errors', '1');

Security::setSecurityHeaders();
