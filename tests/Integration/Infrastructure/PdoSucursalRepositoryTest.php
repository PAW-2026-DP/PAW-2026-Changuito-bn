<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Coordenada;
use App\Domain\Rol;
use App\Domain\Sucursal;
use App\Domain\Supermercado;
use App\Domain\Usuario;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\PdoSucursalRepository;
use App\Infrastructure\PdoSupermercadoRepository;
use App\Infrastructure\PdoUsuarioRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSucursalRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoSucursalRepository $repositorio;
    private int $supermercadoId;

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

        $this->supermercadoId = $cuenta->id;
        $this->repositorio = new PdoSucursalRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    public function test_asigna_el_id_de_la_base_al_guardar_una_sucursal_nueva(): void
    {
        // Act
        $guardada = $this->repositorio->save($this->nueva('Centro'));

        // Assert
        self::assertGreaterThan(0, $guardada->id);
        self::assertSame('Centro', $guardada->nombre);
    }

    public function test_recupera_la_sucursal_con_su_ubicacion(): void
    {
        // Arrange
        $guardada = $this->repositorio->save($this->nueva('Centro'));

        // Act
        $recuperada = $this->repositorio->findById($guardada->id);

        // Assert
        self::assertNotNull($recuperada);
        self::assertEqualsWithDelta(-34.5703, $recuperada->ubicacion->latitud, 0.000001);
        self::assertEqualsWithDelta(-59.1050, $recuperada->ubicacion->longitud, 0.000001);
    }

    public function test_lista_solo_las_sucursales_del_supermercado(): void
    {
        // Arrange
        $this->repositorio->save($this->nueva('Centro'));
        $this->repositorio->save($this->nueva('Norte'));

        // Act
        $sucursales = $this->repositorio->findBySupermercado($this->supermercadoId);

        // Assert
        self::assertCount(2, $sucursales);
    }

    public function test_findActivas_excluye_las_sucursales_dadas_de_baja(): void
    {
        // Arrange
        $this->repositorio->save($this->nueva('Centro'));
        $this->repositorio->save($this->nueva('Cerrada', activa: false));

        // Act
        $activas = $this->repositorio->findActivas();

        // Assert
        self::assertCount(1, $activas);
        self::assertSame('Centro', $activas[0]->nombre);
    }

    public function test_persiste_la_baja_de_una_sucursal(): void
    {
        // Arrange
        $guardada = $this->repositorio->save($this->nueva('Centro'));

        // Act
        $this->repositorio->save(new Sucursal(
            $guardada->id,
            $guardada->supermercadoId,
            $guardada->nombre,
            $guardada->direccion,
            $guardada->ubicacion,
            false,
        ));

        // Assert
        self::assertFalse($this->repositorio->findById($guardada->id)?->activa);
    }

    private function nueva(string $nombre, bool $activa = true): Sucursal
    {
        return new Sucursal(
            0,
            $this->supermercadoId,
            $nombre,
            'San Martín 100',
            new Coordenada(-34.5703, -59.1050),
            $activa,
        );
    }

    private function limpiar(): void
    {
        $this->pdo->exec('DELETE FROM zonas_cobertura');
        $this->pdo->exec('DELETE FROM sucursales');
        $this->pdo->exec('DELETE FROM supermercados');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
