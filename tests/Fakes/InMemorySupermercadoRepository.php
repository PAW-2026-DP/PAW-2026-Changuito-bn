<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Supermercado;
use App\Domain\SupermercadoRepositoryInterface;

final class InMemorySupermercadoRepository implements SupermercadoRepositoryInterface
{
    /** @var array<int, Supermercado> */
    private array $supermercados = [];

    public function findByUsuarioId(int $usuarioId): ?Supermercado
    {
        return $this->supermercados[$usuarioId] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->supermercados);
    }

    public function save(Supermercado $supermercado): Supermercado
    {
        $this->supermercados[$supermercado->usuarioId] = $supermercado;

        return $supermercado;
    }
}
