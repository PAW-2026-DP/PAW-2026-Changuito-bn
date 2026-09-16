<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function test_normaliza_el_metodo_a_mayusculas(): void
    {
        // Arrange / Act
        $request = new Request('get', '/health');

        // Assert
        self::assertSame('GET', $request->method);
    }

    public function test_quita_query_string_y_barra_final_del_path(): void
    {
        // Arrange / Act
        $request = new Request('GET', '/pedidos/?page=2');

        // Assert
        self::assertSame('/pedidos', $request->path);
    }

    public function test_path_vacio_se_normaliza_a_raiz(): void
    {
        // Arrange / Act
        $request = new Request('GET', '');

        // Assert
        self::assertSame('/', $request->path);
    }

    public function test_lee_headers_sin_distinguir_mayusculas(): void
    {
        // Arrange
        $request = new Request('GET', '/', headers: ['Content-Type' => 'application/json']);

        // Act
        $value = $request->header('content-type');

        // Assert
        self::assertSame('application/json', $value);
    }

    public function test_devuelve_null_si_el_parametro_de_query_no_existe(): void
    {
        // Arrange
        $request = new Request('GET', '/', query: ['page' => '2']);

        // Act
        $value = $request->query('size');

        // Assert
        self::assertNull($value);
    }

    public function test_with_route_params_no_muta_la_request_original(): void
    {
        // Arrange
        $original = new Request('GET', '/pedidos/7');

        // Act
        $withParams = $original->withRouteParams(['id' => '7']);

        // Assert
        self::assertSame('7', $withParams->routeParam('id'));
        self::assertNull($original->routeParam('id'));
    }
}
