<?php

declare(strict_types=1);

namespace App\Domain;

interface SucursalRepositoryInterface
{
    public function findById(int $id): ?Sucursal;

    /**
     * @return Sucursal[]
     */
    public function findBySupermercado(int $supermercadoId): array;

    /**
     * @return Sucursal[]
     */
    public function findActivas(): array;

    public function save(Sucursal $sucursal): Sucursal;
}
