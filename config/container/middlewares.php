<?php

declare(strict_types=1);

use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\ExceptionMiddleware;

/**
 * Middlewares globales del Pipeline, en el orden en que se ejecutan (el
 * primero envuelve a los siguientes). CORS va primero para que sus headers
 * lleguen incluso en una respuesta de error armada por ExceptionMiddleware.
 *
 * @return list<App\Http\Middleware>
 */
return static function (): array {
    $allowedOrigins = array_values(array_filter(array_map(
        static fn (string $origin): string => trim($origin),
        explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: ''),
    )));

    return [
        new CorsMiddleware($allowedOrigins),
        new ExceptionMiddleware(),
    ];
};
