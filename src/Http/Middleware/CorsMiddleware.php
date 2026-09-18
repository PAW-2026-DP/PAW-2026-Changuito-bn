<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Handler;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

/**
 * Habilita CORS para los orígenes configurados (los frontends de cliente,
 * backoffice y riders). Responde el preflight `OPTIONS` sin llegar al
 * Router, y agrega los headers correspondientes al resto de las respuestas
 * cuando el origen de la request está permitido.
 */
final class CorsMiddleware implements Middleware
{
    private const ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
    private const ALLOWED_HEADERS = 'Content-Type, Authorization';

    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(private readonly array $allowedOrigins)
    {
    }

    public function process(Request $request, Handler $next): Response
    {
        $origin = $request->header('Origin');

        if ($request->method === 'OPTIONS') {
            return $this->withCorsHeaders(Response::noContent(), $origin);
        }

        return $this->withCorsHeaders($next->handle($request), $origin);
    }

    private function withCorsHeaders(Response $response, ?string $origin): Response
    {
        if ($origin === null || !in_array($origin, $this->allowedOrigins, true)) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', self::ALLOWED_METHODS)
            ->withHeader('Access-Control-Allow-Headers', self::ALLOWED_HEADERS);
    }
}
