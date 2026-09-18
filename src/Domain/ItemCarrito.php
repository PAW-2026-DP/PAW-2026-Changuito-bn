<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Producto del Carrito ya resuelto en una Sucursal concreta, con el precio
 * unitario vigente al momento de agregarlo.
 */
final class ItemCarrito
{
    public function __construct(
        public readonly int $productoId,
        public readonly int $sucursalId,
        public readonly int $cantidad,
        public readonly Dinero $precioUnitario,
    ) {
        if ($cantidad <= 0) {
            throw new ValidacionException('La cantidad debe ser mayor a cero.');
        }
    }
}
