<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\UnitOfWorkInterface;

/**
 * Fake para testear Services sin base de datos: ejecuta el trabajo
 * directamente, sin transacción real (no hay nada que hacer rollback en
 * repositorios en memoria).
 */
final class InMemoryUnitOfWork implements UnitOfWorkInterface
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
