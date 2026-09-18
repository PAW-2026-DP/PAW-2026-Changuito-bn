<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Lista de compras frecuente y reutilizable de un Cliente. Cada producto
 * aparece a lo sumo una vez: fijar la cantidad de un producto ya presente
 * reemplaza el ItemLista existente en vez de duplicarlo.
 */
final class ListaDeCompras
{
    /** @var array<int, ItemLista> indexado por productoId */
    private array $items = [];

    public function __construct(
        public readonly int $id,
        public readonly int $clienteId,
        public string $nombre,
        public readonly \DateTimeImmutable $fechaCreacion,
    ) {
        $this->validarNombre($nombre);
    }

    public function renombrar(string $nombre): void
    {
        $this->validarNombre($nombre);

        $this->nombre = $nombre;
    }

    public function definirCantidad(int $productoId, int $cantidad): void
    {
        $this->items[$productoId] = new ItemLista($productoId, $cantidad);
    }

    public function quitarItem(int $productoId): void
    {
        unset($this->items[$productoId]);
    }

    /** @return ItemLista[] */
    public function items(): array
    {
        return array_values($this->items);
    }

    private function validarNombre(string $nombre): void
    {
        if (trim($nombre) === '') {
            throw new ValidacionException('El nombre de la lista no puede estar vacío.');
        }
    }
}
