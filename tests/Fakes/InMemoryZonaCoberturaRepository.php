<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\ZonaCobertura;
use App\Domain\ZonaCoberturaRepositoryInterface;

final class InMemoryZonaCoberturaRepository implements ZonaCoberturaRepositoryInterface
{
    /** @var array<int, ZonaCobertura> */
    private array $zonas = [];

    public function findBySucursal(int $sucursalId): ?ZonaCobertura
    {
        return $this->zonas[$sucursalId] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->zonas);
    }

    public function save(ZonaCobertura $zona): ZonaCobertura
    {
        $this->zonas[$zona->sucursalId] = $zona;

        return $zona;
    }
}
