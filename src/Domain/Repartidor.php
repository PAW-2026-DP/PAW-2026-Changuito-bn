<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Datos de disponibilidad y ubicación de un repartidor. Sin disponibilidad
 * declarada no recibe ofertas de reparto (ver ElegibilidadRepartidor).
 */
final class Repartidor
{
    public bool $disponible = false;
    public ?Coordenada $ubicacion = null;
    public ?\DateTimeImmutable $ubicacionActualizadaEn = null;

    public function __construct(
        public readonly int $usuarioId,
    ) {
    }

    public function ponerseDisponible(Coordenada $ubicacion, \DateTimeImmutable $fecha): void
    {
        $this->disponible = true;
        $this->ubicacion = $ubicacion;
        $this->ubicacionActualizadaEn = $fecha;
    }

    public function ponerseNoDisponible(): void
    {
        $this->disponible = false;
    }

    public function actualizarUbicacion(Coordenada $ubicacion, \DateTimeImmutable $fecha): void
    {
        if (!$this->disponible) {
            throw new ValidacionException('El repartidor debe estar disponible para actualizar su ubicación.');
        }

        $this->ubicacion = $ubicacion;
        $this->ubicacionActualizadaEn = $fecha;
    }
}
