<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\CoeficienteDistancia;
use App\Domain\Exception\ValidacionException;
use App\Domain\TamanioEnvio;
use PHPUnit\Framework\TestCase;

final class CoeficienteDistanciaTest extends TestCase
{
    public function test_una_distancia_dentro_del_rango_esta_cubierta(): void
    {
        // Arrange
        $coeficiente = new CoeficienteDistancia(TamanioEnvio::MEDIANO, 0.0, 5.0, 1.0);

        // Act / Assert
        self::assertTrue($coeficiente->cubreDistancia(2.5));
    }

    public function test_una_distancia_fuera_del_rango_no_esta_cubierta(): void
    {
        // Arrange
        $coeficiente = new CoeficienteDistancia(TamanioEnvio::MEDIANO, 0.0, 5.0, 1.0);

        // Act / Assert
        self::assertFalse($coeficiente->cubreDistancia(5.1));
    }

    public function test_lanza_excepcion_si_la_distancia_minima_no_es_menor_a_la_maxima(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new CoeficienteDistancia(TamanioEnvio::MEDIANO, 5.0, 5.0, 1.0);
    }

    public function test_lanza_excepcion_si_el_coeficiente_no_es_positivo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new CoeficienteDistancia(TamanioEnvio::MEDIANO, 0.0, 5.0, 0.0);
    }
}
