<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidacionException;
use App\Domain\ZonaCobertura;
use PHPUnit\Framework\TestCase;

final class ZonaCoberturaTest extends TestCase
{
    public function test_una_distancia_entre_el_radio_minimo_y_maximo_esta_cubierta(): void
    {
        // Arrange
        $zona = new ZonaCobertura(1, 1.0, 10.0);

        // Act / Assert
        self::assertTrue($zona->cubre(5.0));
    }

    public function test_una_distancia_menor_al_radio_minimo_no_esta_cubierta(): void
    {
        // Arrange
        $zona = new ZonaCobertura(1, 1.0, 10.0);

        // Act / Assert
        self::assertFalse($zona->cubre(0.5));
    }

    public function test_una_distancia_mayor_al_radio_maximo_no_esta_cubierta(): void
    {
        // Arrange
        $zona = new ZonaCobertura(1, 1.0, 10.0);

        // Act / Assert
        self::assertFalse($zona->cubre(10.1));
    }

    public function test_lanza_excepcion_si_el_radio_minimo_no_es_menor_al_maximo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new ZonaCobertura(1, 10.0, 10.0);
    }

    public function test_lanza_excepcion_si_el_radio_minimo_es_negativo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new ZonaCobertura(1, -1.0, 10.0);
    }
}
