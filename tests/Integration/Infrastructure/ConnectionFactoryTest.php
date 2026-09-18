<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Infrastructure\Database\ConnectionFactory;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    public function test_conecta_a_la_base_de_test_y_permite_ejecutar_una_consulta(): void
    {
        // Arrange
        $pdo = ConnectionFactory::forTests();

        // Act
        $resultado = $pdo->query('SELECT 1 AS ok')->fetch();

        // Assert
        self::assertSame('1', (string) $resultado['ok']);
    }
}
