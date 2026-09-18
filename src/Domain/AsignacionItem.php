<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * En qué sucursal se compra un producto de la lista, a qué precio y en qué
 * cantidad, dentro de un EscenarioCompra.
 */
final class AsignacionItem
{
    public function __construct(
        public readonly int $productoId,
        public readonly int $sucursalId,
        public readonly int $cantidad,
        public readonly Dinero $precioUnitario,
    ) {
    }

    public function subtotal(): Dinero
    {
        return $this->precioUnitario->multiplicarPor($this->cantidad);
    }
}
