<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\EchoHandler;
use Tests\Fakes\HeaderMiddleware;

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

    public function test_aplica_el_prefijo_del_grupo_a_las_rutas_registradas_dentro(): void
    {
        // Arrange
        $router = new Router();
        $router->group('/api/v1', [], static function (Router $router): void {
            $router->get('/health', new EchoHandler());
        });

        // Act
        $response = $router->handle(new Request('GET', '/api/v1/health'));

        // Assert
        self::assertSame(200, $response->status);
    }

    public function test_las_rutas_de_un_grupo_no_responden_sin_el_prefijo(): void
    {
        // Arrange
        $router = new Router();
        $router->group('/api/v1', [], static function (Router $router): void {
            $router->get('/health', new EchoHandler());
        });

        // Act
        $response = $router->handle(new Request('GET', '/health'));

        // Assert
        self::assertSame(404, $response->status);
    }

    public function test_ejecuta_los_middlewares_del_grupo_alrededor_de_la_ruta(): void
    {
        // Arrange
        $router = new Router();
        $router->group('/api/v1', [new HeaderMiddleware('X-Group', 'api-v1')], static function (Router $router): void {
            $router->get('/health', new EchoHandler());
        });

        // Act
        $response = $router->handle(new Request('GET', '/api/v1/health'));

        // Assert
        self::assertSame('api-v1', $response->headers['X-Group']);
    }

    public function test_los_grupos_anidados_combinan_prefijo_y_middlewares(): void
    {
        // Arrange
        $router = new Router();
        $router->group('/api', [new HeaderMiddleware('X-Outer', 'api')], static function (Router $router): void {
            $router->group('/v1', [new HeaderMiddleware('X-Inner', 'v1')], static function (Router $router): void {
                $router->get('/health', new EchoHandler());
            });
        });

        // Act
        $response = $router->handle(new Request('GET', '/api/v1/health'));

        // Assert
        self::assertSame(200, $response->status);
        self::assertSame('api', $response->headers['X-Outer']);
        self::assertSame('v1', $response->headers['X-Inner']);
    }

    public function test_una_ruta_fuera_de_todo_grupo_no_lleva_middlewares(): void
    {
        // Arrange: registrar un grupo y luego una ruta suelta no debe
        // arrastrar los middlewares del grupo anterior.
        $router = new Router();
        $router->group('/api/v1', [new HeaderMiddleware('X-Group', 'api-v1')], static function (Router $router): void {
            $router->get('/health', new EchoHandler());
        });
        $router->get('/fuera-del-grupo', new EchoHandler());

        // Act
        $response = $router->handle(new Request('GET', '/fuera-del-grupo'));

        // Assert
        self::assertArrayNotHasKey('X-Group', $response->headers);
    }
}
