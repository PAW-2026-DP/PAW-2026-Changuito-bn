<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Sucursal;
use App\Domain\SucursalRepositoryInterface;

final class InMemorySucursalRepository implements SucursalRepositoryInterface
{
    /** @var array<int, Sucursal> */
    private array $sucursales = [];

    private int $proximoId = 1;

    public function findById(int $id): ?Sucursal
    {
        return $this->sucursales[$id] ?? null;
    }

    public function findBySupermercado(int $supermercadoId): array
    {
        return array_values(array_filter(
            $this->sucursales,
            static fn (Sucursal $sucursal): bool => $sucursal->supermercadoId === $supermercadoId,
        ));
    }

    public function findActivas(): array
    {
        return array_values(array_filter(
            $this->sucursales,
            static fn (Sucursal $sucursal): bool => $sucursal->activa,
        ));
    }

    public function save(Sucursal $sucursal): Sucursal
    {
        if ($sucursal->id === 0) {
            $sucursal = new Sucursal(
                $this->proximoId++,
                $sucursal->supermercadoId,
                $sucursal->nombre,
                $sucursal->direccion,
                $sucursal->ubicacion,
                $sucursal->activa,
            );
        }

        $this->sucursales[$sucursal->id] = $sucursal;

        return $sucursal;
    }
}
