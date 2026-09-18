<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Coordenada;
use App\Domain\Exception\ValidacionException;
use PHPUnit\Framework\TestCase;

final class CoordenadaTest extends TestCase
{
    public function test_la_distancia_entre_un_punto_y_si_mismo_es_cero(): void
    {
        // Arrange
        $punto = new Coordenada(-34.6037, -58.3816);

        // Act
        $distancia = $punto->distanciaEnKm($punto);

        // Assert
        self::assertEqualsWithDelta(0.0, $distancia, 0.0001);
    }

    public function test_calcula_la_distancia_entre_dos_puntos_sobre_el_ecuador(): void
    {
        // Arrange: en el ecuador, 1 grado de longitud equivale a
        // ~111.19 km (2 * pi * 6371 / 360), fácil de verificar a mano.
        $origen = new Coordenada(0.0, 0.0);
        $destino = new Coordenada(0.0, 1.0);

        // Act
        $distancia = $origen->distanciaEnKm($destino);

        // Assert
        self::assertEqualsWithDelta(111.19, $distancia, 0.1);
    }

    public function test_lanza_excepcion_de_validacion_si_la_latitud_esta_fuera_de_rango(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new Coordenada(91.0, 0.0);
    }

    public function test_lanza_excepcion_de_validacion_si_la_longitud_esta_fuera_de_rango(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new Coordenada(0.0, -181.0);
    }
}
