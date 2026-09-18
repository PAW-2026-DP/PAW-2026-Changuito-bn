<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Dinero;
use App\Domain\Exception\ValidacionException;
use PHPUnit\Framework\TestCase;

final class DineroTest extends TestCase
{
    public function test_suma_dos_montos_en_centavos(): void
    {
        // Arrange
        $total = Dinero::pesos(10.50);

        // Act
        $resultado = $total->sumar(Dinero::pesos(2.25));

        // Assert
        self::assertSame(1275, $resultado->centavos);
    }

    public function test_resta_dos_montos(): void
    {
        // Arrange / Act
        $resultado = Dinero::pesos(10)->restar(Dinero::pesos(3));

        // Assert
        self::assertSame(700, $resultado->centavos);
    }

    public function test_multiplica_por_una_cantidad_entera(): void
    {
        // Arrange / Act
        $resultado = Dinero::pesos(5)->multiplicarPor(3);

        // Assert
        self::assertSame(1500, $resultado->centavos);
    }

    public function test_es_mayor_que_compara_dos_montos(): void
    {
        // Arrange / Act / Assert
        self::assertTrue(Dinero::pesos(10)->esMayorQue(Dinero::pesos(5)));
        self::assertFalse(Dinero::pesos(5)->esMayorQue(Dinero::pesos(10)));
    }

    public function test_dos_montos_con_los_mismos_centavos_son_iguales(): void
    {
        // Arrange / Act / Assert
        self::assertTrue(Dinero::pesos(10)->equals(Dinero::centavos(1000)));
    }

    public function test_lanza_excepcion_de_validacion_si_el_monto_es_negativo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        Dinero::centavos(-1);
    }

    public function test_lanza_excepcion_de_validacion_si_el_factor_a_multiplicar_es_negativo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        Dinero::pesos(5)->multiplicarPor(-1);
    }
}
