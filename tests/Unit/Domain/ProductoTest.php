<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidacionException;
use App\Domain\Producto;
use App\Domain\TamanioEnvio;
use PHPUnit\Framework\TestCase;

final class ProductoTest extends TestCase
{
    public function test_expone_los_datos_con_los_que_se_construyo(): void
    {
        // Arrange / Act
        $producto = new Producto(1, 'Arroz 1kg', 'Arroz largo fino', 2, TamanioEnvio::PEQUENIO);

        // Assert
        self::assertSame(1, $producto->id);
        self::assertSame('Arroz 1kg', $producto->nombre);
        self::assertSame(2, $producto->categoriaId);
        self::assertSame(TamanioEnvio::PEQUENIO, $producto->tamanioEnvio);
    }

    public function test_lanza_excepcion_si_el_nombre_esta_vacio(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new Producto(1, '', 'Descripción', 2, TamanioEnvio::PEQUENIO);
    }
}
