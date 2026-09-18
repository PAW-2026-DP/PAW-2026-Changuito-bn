<?php

declare(strict_types=1);

namespace App\Domain;

interface ZonaCoberturaRepositoryInterface
{
    public function findBySucursal(int $sucursalId): ?ZonaCobertura;

    /**
     * @return ZonaCobertura[]
     */
    public function findAll(): array;

    public function save(ZonaCobertura $zona): ZonaCobertura;
}
