<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Clasificación del catálogo maestro de productos.
 */
final class Categoria
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
    ) {
        if (trim($nombre) === '') {
            throw new ValidacionException('El nombre de la categoría no puede estar vacío.');
        }
    }
}
