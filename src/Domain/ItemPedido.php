<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Línea de un OrdenComercio, con el precio unitario congelado al momento
 * de crear el Pedido: nunca se recalcula, aunque el precio publicado
 * cambie después.
 */
final class ItemPedido
{
    public function __construct(
        public readonly int $productoId,
        public readonly int $cantidad,
        public readonly Dinero $precioUnitario,
    ) {
        if ($cantidad <= 0) {
            throw new ValidacionException('La cantidad debe ser mayor a cero.');
        }
    }
}
