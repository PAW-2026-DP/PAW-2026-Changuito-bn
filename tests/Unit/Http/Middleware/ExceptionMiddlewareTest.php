<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Domain\Exception\AccesoDenegadoException;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;
use App\Http\Middleware\ExceptionMiddleware;
use App\Http\Request;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\ThrowingHandler;

final class ExceptionMiddlewareTest extends TestCase
{
    private ExceptionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new ExceptionMiddleware();
    }

    public function test_traduce_entidad_no_encontrada_a_404(): void
    {
        // Arrange
        $handler = new ThrowingHandler(new EntidadNoEncontradaException('no existe'));

        // Act
        $response = $this->middleware->process(new Request('GET', '/'), $handler);

        // Assert
        self::assertSame(404, $response->status);
    }

    public function test_traduce_acceso_denegado_a_403(): void
    {
        // Arrange
        $handler = new ThrowingHandler(new AccesoDenegadoException('no autorizado'));

        // Act
        $response = $this->middleware->process(new Request('GET', '/'), $handler);

        // Assert
        self::assertSame(403, $response->status);
    }

    public function test_traduce_transicion_invalida_a_409(): void
    {
        // Arrange
        $handler = new ThrowingHandler(new TransicionInvalidaException('transición inválida'));

        // Act
        $response = $this->middleware->process(new Request('GET', '/'), $handler);

        // Assert
        self::assertSame(409, $response->status);
    }

    public function test_traduce_validacion_a_422(): void
    {
        // Arrange
        $handler = new ThrowingHandler(new ValidacionException('dato inválido'));

        // Act
        $response = $this->middleware->process(new Request('GET', '/'), $handler);

        // Assert
        self::assertSame(422, $response->status);
    }

    public function test_traduce_cualquier_otra_excepcion_a_500_sin_exponer_el_detalle(): void
    {
        // Arrange
        $handler = new ThrowingHandler(new \RuntimeException('detalle interno sensible'));

        // Act
        $response = @$this->middleware->process(new Request('GET', '/'), $handler);

        // Assert
        self::assertSame(500, $response->status);
        self::assertStringNotContainsString('detalle interno sensible', $response->body);
    }
}
