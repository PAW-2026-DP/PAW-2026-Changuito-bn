<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Database\PdoUnitOfWork;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoUnitOfWorkTest extends TestCase
{
    private PDO $pdo;
    private PdoUnitOfWork $unitOfWork;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::forTests();
        $this->pdo->exec('DROP TABLE IF EXISTS uow_test_probe');
        $this->pdo->exec('CREATE TABLE uow_test_probe (id INT PRIMARY KEY)');
        $this->unitOfWork = new PdoUnitOfWork($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS uow_test_probe');
    }

    public function test_confirma_los_cambios_cuando_el_trabajo_termina_sin_errores(): void
    {
        // Act
        $this->unitOfWork->transactional(function (): void {
            $this->pdo->exec('INSERT INTO uow_test_probe (id) VALUES (1)');
        });

        // Assert
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM uow_test_probe')->fetchColumn();
        self::assertSame(1, $total);
    }

    public function test_revierte_los_cambios_si_el_trabajo_lanza_una_excepcion(): void
    {
        // Act
        try {
            $this->unitOfWork->transactional(function (): void {
                $this->pdo->exec('INSERT INTO uow_test_probe (id) VALUES (1)');

                throw new \RuntimeException('fallo simulado');
            });
        } catch (\RuntimeException) {
            // esperado: transactional() debe relanzarla después del rollback.
        }

        // Assert
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM uow_test_probe')->fetchColumn();
        self::assertSame(0, $total);
    }

    public function test_relanza_la_excepcion_original_del_trabajo(): void
    {
        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fallo simulado');

        // Act
        $this->unitOfWork->transactional(function (): void {
            throw new \RuntimeException('fallo simulado');
        });
    }
}
