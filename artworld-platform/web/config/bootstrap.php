<?php

declare(strict_types=1);

define('WEB_ROOT', dirname(__DIR__));
define('WEB_PUBLIC', WEB_ROOT . '/public');
define('WEB_VIEWS', WEB_ROOT . '/views');
define('WEB_STORAGE', WEB_ROOT . '/storage');

spl_autoload_register(static function (string $class): void {
    $prefix = 'Web\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = WEB_ROOT . '/app/' . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once WEB_ROOT . '/app/Helpers/functions.php';

\Web\Core\Config::load(WEB_ROOT . '/.env');

date_default_timezone_set((string) \Web\Core\Config::get('TIMEZONE', 'Europe/Istanbul'));

if (\Web\Core\Config::getBool('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
