<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Entrada de alta y edición de direcciones. `id` en 0 significa alta; con
 * id, edición de esa dirección del mismo cliente.
 */
final class GuardarDireccionCommand
{
    public function __construct(
        public readonly int $clienteId,
        public readonly string $calle,
        public readonly string $numero,
        public readonly string $ciudad,
        public readonly string $cp,
        public readonly float $latitud,
        public readonly float $longitud,
        public readonly int $id = 0,
    ) {
    }
}
