<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\EstadoPedido;
use PHPUnit\Framework\TestCase;

final class EstadoPedidoTest extends TestCase
{
    public function test_expone_los_nueve_estados_del_ciclo_de_vida(): void
    {
        // Arrange / Act
        $casos = EstadoPedido::cases();

        // Assert
        self::assertCount(9, $casos);
        self::assertContainsEquals(EstadoPedido::PENDIENTE, $casos);
        self::assertContainsEquals(EstadoPedido::CONFIRMADO, $casos);
        self::assertContainsEquals(EstadoPedido::EN_PREPARACION, $casos);
        self::assertContainsEquals(EstadoPedido::LISTO_PARA_RETIRO, $casos);
        self::assertContainsEquals(EstadoPedido::ASIGNADO, $casos);
        self::assertContainsEquals(EstadoPedido::RETIRADO, $casos);
        self::assertContainsEquals(EstadoPedido::EN_CAMINO, $casos);
        self::assertContainsEquals(EstadoPedido::ENTREGADO, $casos);
        self::assertContainsEquals(EstadoPedido::CANCELADO, $casos);
    }
}
