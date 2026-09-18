<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Dinero;
use App\Domain\Exception\ValidacionException;
use App\Domain\ItemPedido;
use PHPUnit\Framework\TestCase;

final class ItemPedidoTest extends TestCase
{
    public function test_expone_los_datos_con_los_que_se_construyo(): void
    {
        // Arrange / Act
        $item = new ItemPedido(5, 3, Dinero::pesos(150));

        // Assert
        self::assertSame(5, $item->productoId);
        self::assertSame(3, $item->cantidad);
        self::assertTrue($item->precioUnitario->equals(Dinero::pesos(150)));
    }

    public function test_lanza_excepcion_si_la_cantidad_no_es_mayor_a_cero(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new ItemPedido(5, 0, Dinero::pesos(150));
    }
}
