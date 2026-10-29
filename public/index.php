<?php

declare(strict_types=1);

// Front controller: every request that is not a static file is routed through here.

use App\Core\App;
use App\Core\Request;

// Let PHP's built-in dev server serve static assets directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

App::securityHeaders();
App::handle(Request::fromGlobals())->send();
