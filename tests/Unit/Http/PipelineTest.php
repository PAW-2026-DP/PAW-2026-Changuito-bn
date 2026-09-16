<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Pipeline;
use App\Http\Request;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\EchoHandler;
use Tests\Fakes\HeaderMiddleware;
use Tests\Fakes\ShortCircuitMiddleware;

final class PipelineTest extends TestCase
{
    public function test_sin_middlewares_delega_en_el_handler_final(): void
    {
        // Arrange
        $pipeline = new Pipeline(new EchoHandler());

        // Act
        $response = $pipeline->handle(new Request('GET', '/'));

        // Assert
        self::assertSame(200, $response->status);
    }

    public function test_ejecuta_los_middlewares_en_el_orden_registrado(): void
    {
        // Arrange
        $pipeline = new Pipeline(new EchoHandler(), [
            new HeaderMiddleware('X-Trace', 'primero'),
            new HeaderMiddleware('X-Trace', 'segundo'),
        ]);

        // Act
        $response = $pipeline->handle(new Request('GET', '/'));

        // Assert: el primero envuelve al segundo, así que escribe último
        self::assertSame('primero', $response->headers['X-Trace']);
    }

    public function test_un_middleware_puede_cortar_la_cadena(): void
    {
        // Arrange
        $pipeline = new Pipeline(new EchoHandler(), [new ShortCircuitMiddleware(401)]);

        // Act
        $response = $pipeline->handle(new Request('GET', '/'));

        // Assert
        self::assertSame(401, $response->status);
    }
}
