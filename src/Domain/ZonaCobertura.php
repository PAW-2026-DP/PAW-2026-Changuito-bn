<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Radio mínimo y máximo, respecto de la dirección de entrega del cliente,
 * dentro del cual una Sucursal compite por una compra. El mismo radio
 * determina qué repartidores son elegibles para un pedido.
 */
final class ZonaCobertura
{
    public function __construct(
        public readonly int $sucursalId,
        public readonly float $radioMinKm,
        public readonly float $radioMaxKm,
    ) {
        if ($radioMinKm < 0.0) {
            throw new ValidacionException('El radio mínimo no puede ser negativo.');
        }

        if ($radioMinKm >= $radioMaxKm) {
            throw new ValidacionException('El radio mínimo debe ser menor al máximo.');
        }
    }

    public function cubre(float $distanciaKm): bool
    {
        return $distanciaKm >= $this->radioMinKm && $distanciaKm <= $this->radioMaxKm;
    }
}
