<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Carrito;
use App\Domain\Dinero;
use App\Domain\Exception\ValidacionException;
use App\Domain\PreferenciaFaltantes;
use PHPUnit\Framework\TestCase;

final class CarritoTest extends TestCase
{
    private function crearCarrito(): Carrito
    {
        return new Carrito(1, 10, PreferenciaFaltantes::EXCLUIR);
    }

    public function test_nace_sin_items(): void
    {
        // Arrange / Act
        $carrito = $this->crearCarrito();

        // Assert
        self::assertSame([], $carrito->items());
    }

    public function test_definir_item_agrega_un_producto_resuelto_en_una_sucursal(): void
    {
        // Arrange
        $carrito = $this->crearCarrito();

        // Act
        $carrito->definirItem(5, 20, 2, Dinero::pesos(150));

        // Assert
        $items = $carrito->items();
        self::assertCount(1, $items);
        self::assertSame(5, $items[0]->productoId);
        self::assertSame(20, $items[0]->sucursalId);
        self::assertSame(2, $items[0]->cantidad);
        self::assertTrue($items[0]->precioUnitario->equals(Dinero::pesos(150)));
    }

    public function test_definir_item_sobre_un_producto_existente_lo_reemplaza_sin_duplicar(): void
    {
        // Arrange
        $carrito = $this->crearCarrito();
        $carrito->definirItem(5, 20, 2, Dinero::pesos(150));

        // Act
        $carrito->definirItem(5, 30, 4, Dinero::pesos(160));

        // Assert
        $items = $carrito->items();
        self::assertCount(1, $items);
        self::assertSame(30, $items[0]->sucursalId);
        self::assertSame(4, $items[0]->cantidad);
    }

    public function test_quitar_item_lo_elimina_del_carrito(): void
    {
        // Arrange
        $carrito = $this->crearCarrito();
        $carrito->definirItem(5, 20, 2, Dinero::pesos(150));

        // Act
        $carrito->quitarItem(5);

        // Assert
        self::assertSame([], $carrito->items());
    }

    public function test_vaciar_elimina_todos_los_items(): void
    {
        // Arrange
        $carrito = $this->crearCarrito();
        $carrito->definirItem(5, 20, 2, Dinero::pesos(150));
        $carrito->definirItem(6, 20, 1, Dinero::pesos(90));

        // Act
        $carrito->vaciar();

        // Assert
        self::assertSame([], $carrito->items());
    }

    public function test_lanza_excepcion_si_la_cantidad_no_es_mayor_a_cero(): void
    {
        // Arrange
        $carrito = $this->crearCarrito();

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $carrito->definirItem(5, 20, 0, Dinero::pesos(150));
    }
}
