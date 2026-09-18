<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Categoria;
use App\Domain\Exception\ValidacionException;
use PHPUnit\Framework\TestCase;

final class CategoriaTest extends TestCase
{
    public function test_expone_el_id_y_el_nombre_con_los_que_se_construyo(): void
    {
        // Arrange / Act
        $categoria = new Categoria(1, 'Almacén');

        // Assert
        self::assertSame(1, $categoria->id);
        self::assertSame('Almacén', $categoria->nombre);
    }

    public function test_lanza_excepcion_si_el_nombre_esta_vacio(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new Categoria(1, '');
    }
}
