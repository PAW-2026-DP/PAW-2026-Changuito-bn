<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Dinero;
use App\Domain\EstadoOrdenComercio;
use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\ItemPedido;
use App\Domain\OrdenComercio;
use PHPUnit\Framework\TestCase;

final class OrdenComercioTest extends TestCase
{
    private function crearOrden(): OrdenComercio
    {
        return new OrdenComercio(1, 20, [new ItemPedido(5, 2, Dinero::pesos(150))]);
    }

    public function test_nace_pendiente(): void
    {
        // Arrange / Act
        $orden = $this->crearOrden();

        // Assert
        self::assertSame(EstadoOrdenComercio::PENDIENTE, $orden->estado);
    }

    public function test_lanza_excepcion_si_no_tiene_items(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new OrdenComercio(1, 20, []);
    }

    public function test_recorre_las_transiciones_de_preparacion_hasta_retirado(): void
    {
        // Arrange
        $orden = $this->crearOrden();

        // Act / Assert
        $orden->iniciarPreparacion();
        self::assertSame(EstadoOrdenComercio::EN_PREPARACION, $orden->estado);

        $orden->marcarLista();
        self::assertSame(EstadoOrdenComercio::LISTO_PARA_RETIRO, $orden->estado);

        $orden->registrarRetiro();
        self::assertSame(EstadoOrdenComercio::RETIRADO, $orden->estado);
    }

    public function test_lanza_excepcion_al_marcar_lista_una_orden_que_no_esta_en_preparacion(): void
    {
        // Arrange
        $orden = $this->crearOrden();

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $orden->marcarLista();
    }

    public function test_lanza_excepcion_al_registrar_retiro_de_una_orden_que_no_esta_lista(): void
    {
        // Arrange
        $orden = $this->crearOrden();
        $orden->iniciarPreparacion();

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $orden->registrarRetiro();
    }
}
