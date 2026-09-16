<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\EchoHandler;

final class RouterTest extends TestCase
{
    public function test_despacha_una_ruta_estatica_a_su_handler(): void
    {
        // Arrange
        $router = new Router();
        $router->get('/health', new EchoHandler());

        // Act
        $response = $router->handle(new Request('GET', '/health'));

        // Assert
        self::assertSame(200, $response->status);
    }

    public function test_extrae_parametros_de_ruta(): void
    {
        // Arrange
        $router = new Router();
        $router->get('/pedidos/{id}', new EchoHandler());

        // Act
        $response = $router->handle(new Request('GET', '/pedidos/42'));

        // Assert
        self::assertSame(['id' => '42'], json_decode($response->body, true)['params']);
    }

    public function test_responde_404_si_ninguna_ruta_coincide(): void
    {
        // Arrange
        $router = new Router();
        $router->get('/health', new EchoHandler());

        // Act
        $response = $router->handle(new Request('GET', '/no-existe'));

        // Assert
        self::assertSame(404, $response->status);
    }

    public function test_responde_405_con_header_allow_si_el_metodo_no_coincide(): void
    {
        // Arrange
        $router = new Router();
        $router->get('/health', new EchoHandler());

        // Act
        $response = $router->handle(new Request('POST', '/health'));

        // Assert
        self::assertSame(405, $response->status);
        self::assertSame('GET', $response->headers['Allow']);
    }

    public function test_acepta_un_callable_como_handler(): void
    {
        // Arrange
        $router = new Router();
        $router->post('/pedidos', static fn (Request $request): Response => Response::json([], 201));

        // Act
        $response = $router->handle(new Request('POST', '/pedidos'));

        // Assert
        self::assertSame(201, $response->status);
    }
}
