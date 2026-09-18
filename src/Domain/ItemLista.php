<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Cantidad deseada de un producto dentro de una ListaDeCompras.
 */
final class ItemLista
{
    public function __construct(
        public readonly int $productoId,
        public readonly int $cantidad,
    ) {
        if ($cantidad <= 0) {
            throw new ValidacionException('La cantidad debe ser mayor a cero.');
        }
    }
}
