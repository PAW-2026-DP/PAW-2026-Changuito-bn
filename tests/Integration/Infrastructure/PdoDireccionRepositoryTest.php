<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Coordenada;
use App\Domain\Direccion;
use App\Domain\Rol;
use App\Domain\Usuario;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\PdoDireccionRepository;
use App\Infrastructure\PdoUsuarioRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoDireccionRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoDireccionRepository $repositorio;
    private int $clienteId;
    private int $otroClienteId;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::forTests();
        $this->limpiar();

        $usuarios = new PdoUsuarioRepository($this->pdo);
        $this->clienteId = $usuarios->save($this->cuenta('ana@changuito.test'))->id;
        $this->otroClienteId = $usuarios->save($this->cuenta('otro@changuito.test'))->id;

        $this->repositorio = new PdoDireccionRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->limpiar();
    }

    public function test_guarda_la_direccion_y_la_recupera_con_su_ubicacion(): void
    {
        // Act
        $guardada = $this->repositorio->save($this->nueva($this->clienteId));

        // Assert
        $recuperada = $this->repositorio->findByIdDelCliente($guardada->id, $this->clienteId);
        self::assertNotNull($recuperada);
        self::assertEqualsWithDelta(-34.5703, $recuperada->ubicacion->latitud, 0.000001);
        self::assertTrue($recuperada->esDefault);
    }

    public function test_no_encuentra_la_direccion_de_otro_cliente(): void
    {
        // Arrange
        $ajena = $this->repositorio->save($this->nueva($this->otroClienteId));

        // Act
        $recuperada = $this->repositorio->findByIdDelCliente($ajena->id, $this->clienteId);

        // Assert
        self::assertNull($recuperada);
    }

    public function test_lista_solo_las_direcciones_del_cliente(): void
    {
        // Arrange
        $this->repositorio->save($this->nueva($this->clienteId));
        $this->repositorio->save($this->nueva($this->otroClienteId));

        // Act
        $direcciones = $this->repositorio->findByCliente($this->clienteId);

        // Assert
        self::assertCount(1, $direcciones);
        self::assertSame($this->clienteId, $direcciones[0]->clienteId);
    }

    public function test_elimina_una_direccion(): void
    {
        // Arrange
        $guardada = $this->repositorio->save($this->nueva($this->clienteId));

        // Act
        $this->repositorio->delete($guardada);

        // Assert
        self::assertSame([], $this->repositorio->findByCliente($this->clienteId));
    }

    private function nueva(int $clienteId): Direccion
    {
        return new Direccion(
            0,
            $clienteId,
            'San Martín',
            '100',
            'Luján',
            '6700',
            new Coordenada(-34.5703, -59.1050),
            true,
        );
    }

    private function cuenta(string $email): Usuario
    {
        return Usuario::registrar(
            0,
            $email,
            'secreta123',
            'Cuenta',
            Rol::CLIENTE,
            new \DateTimeImmutable('2026-09-18 10:00:00'),
        );
    }

    private function limpiar(): void
    {
        $this->pdo->exec('DELETE FROM direcciones');
        $this->pdo->exec('DELETE FROM usuarios');
    }
}
