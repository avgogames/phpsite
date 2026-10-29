<?php

declare(strict_types=1);

/*
 * Shared bootstrap for the web front controller and CLI scripts.
 * Loads the autoloader, environment config and error handling.
 */

use App\Core\Config;
use App\Core\ErrorHandler;

define('BASE_PATH', __DIR__);

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    // Zero-dependency fallback so the app runs without `composer install`.
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
    require BASE_PATH . '/src/helpers.php';
}

Config::load(BASE_PATH . '/.env');

date_default_timezone_set('UTC');
ErrorHandler::register((bool) Config::get('APP_DEBUG', false));
