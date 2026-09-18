<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Coordenada;
use App\Domain\Rol;
use App\Domain\Sucursal;
use App\Domain\Supermercado;
use App\Domain\Usuario;
use App\Domain\ZonaCobertura;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\PdoSucursalRepository;
use App\Infrastructure\PdoSupermercadoRepository;
use App\Infrastructure\PdoUsuarioRepository;
use App\Infrastructure\PdoZonaCoberturaRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoZonaCoberturaRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoZonaCoberturaRepository $repositorio;
    private int $sucursalId;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::forTests();
        $this->limpiar();

        $cuenta = (new PdoUsuarioRepository($this->pdo))->save(Usuario::registrar(
            0,
            'dia@changuito.test',
            'secreta123',
            'DIA',
            Rol::SUPERMERCADO,
            new \DateTimeImmutable('2026-09-18 10:00:00'),
        ));
        (new PdoSupermercadoRepository($this->pdo))->save(
            new Supermercado($cuenta->id, 'DIA Argentina SA', '30-11111111-1'),
        );
        $sucursal = (new PdoSucursalRepository($this->pdo))->save(new Sucursal(
            0,
            $cuenta->id,
            'Centro',
            'San Martín 100',
            new Coordenada(-34.5703, -59.1050),
            true,
        ));

        $this->sucursalId = $sucursal->id;
        $this->repositorio = new PdoZonaCoberturaRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    public function test_guarda_la_zona_y_la_recupera_por_sucursal(): void
    {
        // Act
        $this->repositorio->save(new ZonaCobertura($this->sucursalId, 0.5, 8.0));
        $recuperada = $this->repositorio->findBySucursal($this->sucursalId);

        // Assert
        self::assertNotNull($recuperada);
        self::assertEqualsWithDelta(0.5, $recuperada->radioMinKm, 0.001);
        self::assertEqualsWithDelta(8.0, $recuperada->radioMaxKm, 0.001);
    }

    public function test_devuelve_null_cuando_la_sucursal_no_tiene_zona(): void
    {
        // Act
        $recuperada = $this->repositorio->findBySucursal($this->sucursalId);

        // Assert
        self::assertNull($recuperada);
    }

    public function test_volver_a_guardar_reemplaza_la_zona_de_la_sucursal(): void
    {
        // Arrange
        $this->repositorio->save(new ZonaCobertura($this->sucursalId, 0.5, 8.0));

        // Act
        $this->repositorio->save(new ZonaCobertura($this->sucursalId, 1.0, 12.0));

        // Assert
        self::assertCount(1, $this->repositorio->findAll());
        self::assertEqualsWithDelta(12.0, $this->repositorio->findBySucursal($this->sucursalId)?->radioMaxKm, 0.001);
    }

    private function limpiar(): void
    {
        $this->pdo->exec('DELETE FROM zonas_cobertura');
        $this->pdo->exec('DELETE FROM sucursales');
        $this->pdo->exec('DELETE FROM supermercados');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
