<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\EstadoSolicitudAltaProducto;
use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\SolicitudAltaProducto;
use PHPUnit\Framework\TestCase;

final class SolicitudAltaProductoTest extends TestCase
{
    private function crearSolicitudPendiente(): SolicitudAltaProducto
    {
        return new SolicitudAltaProducto(
            1,
            10,
            'Yerba mate 1kg',
            'Yerba mate suave',
            3,
            new \DateTimeImmutable('2026-09-01'),
        );
    }

    public function test_nace_en_estado_pendiente(): void
    {
        // Arrange / Act
        $solicitud = $this->crearSolicitudPendiente();

        // Assert
        self::assertSame(EstadoSolicitudAltaProducto::PENDIENTE, $solicitud->estado);
        self::assertNull($solicitud->productoId);
        self::assertNull($solicitud->motivoRechazo);
    }

    public function test_aprobar_una_solicitud_pendiente_la_deja_aprobada_con_el_producto_resultante(): void
    {
        // Arrange
        $solicitud = $this->crearSolicitudPendiente();
        $fechaResolucion = new \DateTimeImmutable('2026-09-05');

        // Act
        $solicitud->aprobar(55, $fechaResolucion);

        // Assert
        self::assertSame(EstadoSolicitudAltaProducto::APROBADA, $solicitud->estado);
        self::assertSame(55, $solicitud->productoId);
        self::assertSame($fechaResolucion, $solicitud->fechaResolucion);
    }

    public function test_rechazar_una_solicitud_pendiente_la_deja_rechazada_con_motivo(): void
    {
        // Arrange
        $solicitud = $this->crearSolicitudPendiente();
        $fechaResolucion = new \DateTimeImmutable('2026-09-05');

        // Act
        $solicitud->rechazar('Ya existe un producto equivalente', $fechaResolucion);

        // Assert
        self::assertSame(EstadoSolicitudAltaProducto::RECHAZADA, $solicitud->estado);
        self::assertSame('Ya existe un producto equivalente', $solicitud->motivoRechazo);
    }

    public function test_lanza_excepcion_al_aprobar_una_solicitud_que_ya_fue_resuelta(): void
    {
        // Arrange
        $solicitud = $this->crearSolicitudPendiente();
        $solicitud->aprobar(55, new \DateTimeImmutable());

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $solicitud->aprobar(56, new \DateTimeImmutable());
    }

    public function test_lanza_excepcion_al_rechazar_una_solicitud_que_ya_fue_resuelta(): void
    {
        // Arrange
        $solicitud = $this->crearSolicitudPendiente();
        $solicitud->rechazar('motivo', new \DateTimeImmutable());

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $solicitud->rechazar('otro motivo', new \DateTimeImmutable());
    }
}
