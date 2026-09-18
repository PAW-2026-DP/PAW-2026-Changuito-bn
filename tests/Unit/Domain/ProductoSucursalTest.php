<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Dinero;
use App\Domain\Exception\ValidacionException;
use App\Domain\ProductoSucursal;
use PHPUnit\Framework\TestCase;

final class ProductoSucursalTest extends TestCase
{
    public function test_expone_los_datos_con_los_que_se_construyo(): void
    {
        // Arrange
        $fecha = new \DateTimeImmutable('2026-09-17 10:00:00');

        // Act
        $publicacion = new ProductoSucursal(1, 10, Dinero::pesos(500), 20, $fecha);

        // Assert
        self::assertTrue($publicacion->precio->equals(Dinero::pesos(500)));
        self::assertSame(20, $publicacion->stock);
        self::assertSame($fecha, $publicacion->fechaUltimaActualizacion);
    }

    public function test_lanza_excepcion_si_el_stock_es_negativo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new ProductoSucursal(1, 10, Dinero::pesos(500), -1, new \DateTimeImmutable());
    }

    public function test_actualizar_precio_y_stock_reemplaza_ambos_valores_y_la_fecha(): void
    {
        // Arrange
        $publicacion = new ProductoSucursal(1, 10, Dinero::pesos(500), 20, new \DateTimeImmutable('2026-09-01'));
        $fechaNueva = new \DateTimeImmutable('2026-09-17');

        // Act
        $publicacion->actualizarPrecioYStock(Dinero::pesos(600), 15, $fechaNueva);

        // Assert
        self::assertTrue($publicacion->precio->equals(Dinero::pesos(600)));
        self::assertSame(15, $publicacion->stock);
        self::assertSame($fechaNueva, $publicacion->fechaUltimaActualizacion);
    }

    public function test_lanza_excepcion_al_actualizar_con_stock_negativo(): void
    {
        // Arrange
        $publicacion = new ProductoSucursal(1, 10, Dinero::pesos(500), 20, new \DateTimeImmutable());

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $publicacion->actualizarPrecioYStock(Dinero::pesos(600), -1, new \DateTimeImmutable());
    }
}
