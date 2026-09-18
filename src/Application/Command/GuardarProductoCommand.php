<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\TamanioEnvio;

/**
 * Alta y edición de un producto del catálogo maestro. `id` en 0 es alta.
 */
final class GuardarProductoCommand
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly int $categoriaId,
        public readonly TamanioEnvio $tamanioEnvio,
        public readonly int $id = 0,
    ) {
    }
}
