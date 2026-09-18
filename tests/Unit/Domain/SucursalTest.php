<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Coordenada;
use App\Domain\Sucursal;
use PHPUnit\Framework\TestCase;

final class SucursalTest extends TestCase
{
    public function test_expone_los_datos_con_los_que_se_construyo(): void
    {
        // Arrange
        $ubicacion = new Coordenada(-34.6037, -58.3816);

        // Act
        $sucursal = new Sucursal(1, 10, 'Sucursal Centro', 'Av. Siempre Viva 123', $ubicacion, true);

        // Assert
        self::assertSame(1, $sucursal->id);
        self::assertSame(10, $sucursal->supermercadoId);
        self::assertSame('Sucursal Centro', $sucursal->nombre);
        self::assertTrue($sucursal->activa);
        self::assertSame($ubicacion, $sucursal->ubicacion);
    }
}
