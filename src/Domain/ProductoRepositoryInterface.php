<?php

declare(strict_types=1);

namespace App\Domain;

interface ProductoRepositoryInterface
{
    public function findById(int $id): ?Producto;

    /**
     * Búsqueda del catálogo maestro. `$texto` filtra por nombre y
     * `$categoriaId` por clasificación; ambos son opcionales.
     *
     * @return Producto[]
     */
    public function buscar(?string $texto, ?int $categoriaId): array;

    public function save(Producto $producto): Producto;
}
