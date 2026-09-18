<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Una forma concreta de resolver la compra: en qué sucursal se compra cada
 * producto, cuánto sale y qué queda sin cubrir. El total ya incluye el
 * costo logístico de repartir la compra entre n comercios.
 */
final class EscenarioCompra
{
    public readonly Dinero $subtotalProductos;
    public readonly Dinero $total;

    /**
     * @param AsignacionItem[] $asignaciones
     * @param int[] $faltantes ids de producto que ninguna sucursal del escenario puede cubrir
     */
    public function __construct(
        public readonly array $asignaciones,
        public readonly array $faltantes,
        public readonly Dinero $costoLogistico,
    ) {
        $subtotal = Dinero::centavos(0);
        foreach ($asignaciones as $asignacion) {
            $subtotal = $subtotal->sumar($asignacion->subtotal());
        }

        $this->subtotalProductos = $subtotal;
        $this->total = $subtotal->sumar($costoLogistico);
    }

    /** @return int[] ids de sucursal, ordenados */
    public function sucursalesInvolucradas(): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (AsignacionItem $asignacion): int => $asignacion->sucursalId,
            $this->asignaciones,
        )));

        sort($ids);

        return $ids;
    }

    public function cantidadComercios(): int
    {
        return count($this->sucursalesInvolucradas());
    }
}
