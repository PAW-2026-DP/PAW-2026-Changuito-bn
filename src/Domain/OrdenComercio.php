<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;

/**
 * Porción de un Pedido a preparar y retirar en una Sucursal puntual.
 * Preparación y retiro se registran por comercio; ver Pedido para cómo
 * estos estados derivan el estado del pedido completo.
 */
final class OrdenComercio
{
    public EstadoOrdenComercio $estado;

    /** @param ItemPedido[] $items */
    public function __construct(
        public readonly int $id,
        public readonly int $sucursalId,
        private readonly array $items,
    ) {
        if ($items === []) {
            throw new ValidacionException('Una orden de comercio debe tener al menos un item.');
        }

        $this->estado = EstadoOrdenComercio::PENDIENTE;
    }

    public function iniciarPreparacion(): void
    {
        $this->transicionar(EstadoOrdenComercio::PENDIENTE, EstadoOrdenComercio::EN_PREPARACION);
    }

    public function marcarLista(): void
    {
        $this->transicionar(EstadoOrdenComercio::EN_PREPARACION, EstadoOrdenComercio::LISTO_PARA_RETIRO);
    }

    public function registrarRetiro(): void
    {
        $this->transicionar(EstadoOrdenComercio::LISTO_PARA_RETIRO, EstadoOrdenComercio::RETIRADO);
    }

    /** @return ItemPedido[] */
    public function items(): array
    {
        return $this->items;
    }

    private function transicionar(EstadoOrdenComercio $esperado, EstadoOrdenComercio $siguiente): void
    {
        if ($this->estado !== $esperado) {
            throw new TransicionInvalidaException(
                "No se puede pasar a {$siguiente->value} desde el estado {$this->estado->value}."
            );
        }

        $this->estado = $siguiente;
    }
}
