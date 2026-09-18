<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Coordenada;
use App\Domain\Exception\ValidacionException;
use App\Domain\Repartidor;
use PHPUnit\Framework\TestCase;

final class RepartidorTest extends TestCase
{
    public function test_nace_no_disponible_y_sin_ubicacion(): void
    {
        // Arrange / Act
        $repartidor = new Repartidor(1);

        // Assert
        self::assertFalse($repartidor->disponible);
        self::assertNull($repartidor->ubicacion);
    }

    public function test_ponerse_disponible_registra_la_ubicacion_y_la_fecha(): void
    {
        // Arrange
        $repartidor = new Repartidor(1);
        $ubicacion = new Coordenada(-34.6, -58.4);
        $fecha = new \DateTimeImmutable('2026-09-17 09:00:00');

        // Act
        $repartidor->ponerseDisponible($ubicacion, $fecha);

        // Assert
        self::assertTrue($repartidor->disponible);
        self::assertSame($ubicacion, $repartidor->ubicacion);
        self::assertSame($fecha, $repartidor->ubicacionActualizadaEn);
    }

    public function test_ponerse_no_disponible_lo_deja_indisponible(): void
    {
        // Arrange
        $repartidor = new Repartidor(1);
        $repartidor->ponerseDisponible(new Coordenada(-34.6, -58.4), new \DateTimeImmutable());

        // Act
        $repartidor->ponerseNoDisponible();

        // Assert
        self::assertFalse($repartidor->disponible);
    }

    public function test_actualizar_ubicacion_requiere_estar_disponible(): void
    {
        // Arrange
        $repartidor = new Repartidor(1);

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $repartidor->actualizarUbicacion(new Coordenada(-34.6, -58.4), new \DateTimeImmutable());
    }

    public function test_actualizar_ubicacion_estando_disponible_reemplaza_la_ubicacion(): void
    {
        // Arrange
        $repartidor = new Repartidor(1);
        $repartidor->ponerseDisponible(new Coordenada(-34.6, -58.4), new \DateTimeImmutable('2026-09-17 09:00:00'));
        $nuevaUbicacion = new Coordenada(-34.61, -58.41);
        $fechaNueva = new \DateTimeImmutable('2026-09-17 09:30:00');

        // Act
        $repartidor->actualizarUbicacion($nuevaUbicacion, $fechaNueva);

        // Assert
        self::assertSame($nuevaUbicacion, $repartidor->ubicacion);
        self::assertSame($fechaNueva, $repartidor->ubicacionActualizadaEn);
    }
}
