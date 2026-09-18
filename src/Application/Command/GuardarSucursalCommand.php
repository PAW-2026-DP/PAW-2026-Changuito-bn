<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Alta y edición de una sucursal. `id` en 0 significa alta.
 *
 * En la edición `supermercadoId` se ignora: una sucursal no cambia de dueño,
 * así que el service conserva el que ya tenía.
 */
final class GuardarSucursalCommand
{
    public function __construct(
        public readonly int $supermercadoId,
        public readonly string $nombre,
        public readonly string $direccion,
        public readonly float $latitud,
        public readonly float $longitud,
        public readonly bool $activa = true,
        public readonly int $id = 0,
    ) {
    }
}
