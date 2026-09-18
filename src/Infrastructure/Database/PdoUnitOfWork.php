<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\UnitOfWorkInterface;
use PDO;

/**
 * Implementación real de UnitOfWorkInterface: envuelve el trabajo en una
 * transacción de PDO y hace rollback si algo dentro lanza una excepción.
 */
final class PdoUnitOfWork implements UnitOfWorkInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function transactional(callable $work): mixed
    {
        $this->connection->beginTransaction();

        try {
            $result = $work();
            $this->connection->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }
}
