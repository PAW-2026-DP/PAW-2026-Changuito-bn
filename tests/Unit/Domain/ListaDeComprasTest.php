<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidacionException;
use App\Domain\ListaDeCompras;
use PHPUnit\Framework\TestCase;

final class ListaDeComprasTest extends TestCase
{
    private function crearLista(): ListaDeCompras
    {
        return new ListaDeCompras(1, 10, 'Compra semanal', new \DateTimeImmutable('2026-09-01'));
    }

    public function test_nace_sin_items(): void
    {
        // Arrange / Act
        $lista = $this->crearLista();

        // Assert
        self::assertSame([], $lista->items());
    }

    public function test_definir_cantidad_agrega_un_item_nuevo(): void
    {
        // Arrange
        $lista = $this->crearLista();

        // Act
        $lista->definirCantidad(5, 3);

        // Assert
        $items = $lista->items();
        self::assertCount(1, $items);
        self::assertSame(5, $items[0]->productoId);
        self::assertSame(3, $items[0]->cantidad);
    }

    public function test_definir_cantidad_sobre_un_producto_existente_reemplaza_la_cantidad_sin_duplicar(): void
    {
        // Arrange
        $lista = $this->crearLista();
        $lista->definirCantidad(5, 3);

        // Act
        $lista->definirCantidad(5, 7);

        // Assert
        $items = $lista->items();
        self::assertCount(1, $items);
        self::assertSame(7, $items[0]->cantidad);
    }

    public function test_quitar_item_lo_elimina_de_la_lista(): void
    {
        // Arrange
        $lista = $this->crearLista();
        $lista->definirCantidad(5, 3);

        // Act
        $lista->quitarItem(5);

        // Assert
        self::assertSame([], $lista->items());
    }

    public function test_lanza_excepcion_si_la_cantidad_no_es_mayor_a_cero(): void
    {
        // Arrange
        $lista = $this->crearLista();

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $lista->definirCantidad(5, 0);
    }

    public function test_renombrar_cambia_el_nombre_de_la_lista(): void
    {
        // Arrange
        $lista = $this->crearLista();

        // Act
        $lista->renombrar('Compra del mes');

        // Assert
        self::assertSame('Compra del mes', $lista->nombre);
    }

    public function test_lanza_excepcion_si_el_nombre_esta_vacio(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new ListaDeCompras(1, 10, '', new \DateTimeImmutable());
    }
}
