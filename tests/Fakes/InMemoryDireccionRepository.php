<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Direccion;
use App\Domain\DireccionRepositoryInterface;

final class InMemoryDireccionRepository implements DireccionRepositoryInterface
{
    /** @var array<int, Direccion> */
    private array $direcciones = [];

    private int $proximoId = 1;

    public function findByIdDelCliente(int $id, int $clienteId): ?Direccion
    {
        $direccion = $this->direcciones[$id] ?? null;

        return $direccion !== null && $direccion->clienteId === $clienteId ? $direccion : null;
    }

    public function findByCliente(int $clienteId): array
    {
        return array_values(array_filter(
            $this->direcciones,
            static fn (Direccion $direccion): bool => $direccion->clienteId === $clienteId,
        ));
    }

    public function save(Direccion $direccion): Direccion
    {
        if ($direccion->id === 0) {
            $direccion = new Direccion(
                $this->proximoId++,
                $direccion->clienteId,
                $direccion->calle,
                $direccion->numero,
                $direccion->ciudad,
                $direccion->cp,
                $direccion->ubicacion,
                $direccion->esDefault,
            );
        }

        $this->direcciones[$direccion->id] = $direccion;

        return $direccion;
    }

    public function delete(Direccion $direccion): void
    {
        unset($this->direcciones[$direccion->id]);
    }
}
