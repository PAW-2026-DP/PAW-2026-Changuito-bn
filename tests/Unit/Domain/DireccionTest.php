<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Coordenada;
use App\Domain\Direccion;
use PHPUnit\Framework\TestCase;

final class DireccionTest extends TestCase
{
    private function crearDireccion(): Direccion
    {
        return new Direccion(1, 10, 'Av. Siempre Viva', '123', 'Springfield', '1900', new Coordenada(-34.6, -58.4));
    }

    public function test_nace_sin_ser_predeterminada_por_defecto(): void
    {
        // Arrange / Act
        $direccion = $this->crearDireccion();

        // Assert
        self::assertFalse($direccion->esDefault);
    }

    public function test_marcar_como_predeterminada_la_deja_como_default(): void
    {
        // Arrange
        $direccion = $this->crearDireccion();

        // Act
        $direccion->marcarComoPredeterminada();

        // Assert
        self::assertTrue($direccion->esDefault);
    }

    public function test_quitar_predeterminada_la_deja_de_marcar_como_default(): void
    {
        // Arrange
        $direccion = $this->crearDireccion();
        $direccion->marcarComoPredeterminada();

        // Act
        $direccion->quitarPredeterminada();

        // Assert
        self::assertFalse($direccion->esDefault);
    }
}
