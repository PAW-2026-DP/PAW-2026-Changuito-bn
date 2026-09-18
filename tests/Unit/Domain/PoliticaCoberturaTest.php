<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Coordenada;
use App\Domain\PoliticaCobertura;
use App\Domain\Sucursal;
use App\Domain\ZonaCobertura;
use PHPUnit\Framework\TestCase;

final class PoliticaCoberturaTest extends TestCase
{
    public function test_solo_devuelve_sucursales_activas_dentro_de_su_zona_de_cobertura(): void
    {
        // Arrange
        $destino = new Coordenada(-34.6037, -58.3816);
        $cercana = new Sucursal(1, 10, 'Cercana', 'Calle 1', new Coordenada(-34.6040, -58.3820), true);
        $lejana = new Sucursal(2, 10, 'Lejana', 'Calle 2', new Coordenada(-31.4201, -64.1888), true);
        $inactiva = new Sucursal(3, 10, 'Inactiva', 'Calle 3', new Coordenada(-34.6040, -58.3820), false);

        $zonas = [
            1 => new ZonaCobertura(1, 0.0, 5.0),
            2 => new ZonaCobertura(2, 0.0, 5.0),
            3 => new ZonaCobertura(3, 0.0, 5.0),
        ];

        $politica = new PoliticaCobertura();

        // Act
        $conCobertura = $politica->sucursalesConCobertura($destino, [$cercana, $lejana, $inactiva], $zonas);

        // Assert
        self::assertSame([$cercana], $conCobertura);
    }

    public function test_una_sucursal_sin_zona_de_cobertura_definida_queda_excluida(): void
    {
        // Arrange
        $destino = new Coordenada(-34.6037, -58.3816);
        $sucursal = new Sucursal(1, 10, 'Sin zona', 'Calle 1', new Coordenada(-34.6040, -58.3820), true);
        $politica = new PoliticaCobertura();

        // Act
        $conCobertura = $politica->sucursalesConCobertura($destino, [$sucursal], []);

        // Assert
        self::assertSame([], $conCobertura);
    }
}
