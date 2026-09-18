<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Combinación de comercios elegida por el cliente para su próxima compra.
 * Cada producto aparece a lo sumo una vez, resuelto en una única sucursal;
 * se vacía al confirmarse el pedido (ver CheckoutService).
 */
final class Carrito
{
    /** @var array<int, ItemCarrito> indexado por productoId */
    private array $items = [];

    public function __construct(
        public readonly int $id,
        public readonly int $clienteId,
        public readonly PreferenciaFaltantes $preferenciaFaltantes,
    ) {
    }

    public function definirItem(int $productoId, int $sucursalId, int $cantidad, Dinero $precioUnitario): void
    {
        $this->items[$productoId] = new ItemCarrito($productoId, $sucursalId, $cantidad, $precioUnitario);
    }

    public function quitarItem(int $productoId): void
    {
        unset($this->items[$productoId]);
    }

    public function vaciar(): void
    {
        $this->items = [];
    }

    /** @return ItemCarrito[] */
    public function items(): array
    {
        return array_values($this->items);
    }
}
