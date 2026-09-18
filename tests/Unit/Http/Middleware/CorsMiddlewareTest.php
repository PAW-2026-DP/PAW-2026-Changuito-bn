<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Handler;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\EchoHandler;

final class CorsMiddlewareTest extends TestCase
{
    public function test_agrega_headers_cors_cuando_el_origen_esta_permitido(): void
    {
        // Arrange
        $middleware = new CorsMiddleware(['http://localhost:5173']);
        $request = new Request('GET', '/productos', headers: ['Origin' => 'http://localhost:5173']);

        // Act
        $response = $middleware->process($request, new EchoHandler());

        // Assert
        self::assertSame('http://localhost:5173', $response->headers['Access-Control-Allow-Origin']);
        self::assertSame('Origin', $response->headers['Vary']);
    }

    public function test_no_agrega_headers_cors_si_el_origen_no_esta_permitido(): void
    {
        // Arrange
        $middleware = new CorsMiddleware(['http://localhost:5173']);
        $request = new Request('GET', '/productos', headers: ['Origin' => 'http://otro-sitio.com']);

        // Act
        $response = $middleware->process($request, new EchoHandler());

        // Assert
        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $response->headers);
    }

    public function test_no_agrega_headers_cors_si_la_request_no_trae_origin(): void
    {
        // Arrange
        $middleware = new CorsMiddleware(['http://localhost:5173']);

        // Act
        $response = $middleware->process(new Request('GET', '/productos'), new EchoHandler());

        // Assert
        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $response->headers);
    }

    public function test_responde_204_al_preflight_sin_llegar_al_siguiente_handler(): void
    {
        // Arrange
        $middleware = new CorsMiddleware(['http://localhost:5173']);
        $request = new Request('OPTIONS', '/productos', headers: ['Origin' => 'http://localhost:5173']);
        $next = new class implements Handler {
            public bool $fueLlamado = false;

            public function handle(Request $request): Response
            {
                $this->fueLlamado = true;

                return Response::json([]);
            }
        };

        // Act
        $response = $middleware->process($request, $next);

        // Assert
        self::assertSame(204, $response->status);
        self::assertFalse($next->fueLlamado);
    }
}
