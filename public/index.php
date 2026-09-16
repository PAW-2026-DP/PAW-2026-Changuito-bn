<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Http\Request;
use App\Http\Response;

// Front controller: arma la app (router + pipeline) y despacha la request.
$app = require dirname(__DIR__) . '/config/container.php';

try {
    $response = $app->handle(Request::fromGlobals());
} catch (\Throwable $exception) {
    error_log($exception->getMessage());
    $response = Response::error('Internal Server Error', 500);
}

$response->send();
