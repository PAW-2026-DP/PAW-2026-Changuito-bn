<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Punto físico de un Supermercado desde el que se publica catálogo y se
 * preparan pedidos.
 */
final class Sucursal
{
    public function __construct(
        public readonly int $id,
        public readonly int $supermercadoId,
        public readonly string $nombre,
        public readonly string $direccion,
        public readonly Coordenada $ubicacion,
        public readonly bool $activa,
    ) {
    }
}
