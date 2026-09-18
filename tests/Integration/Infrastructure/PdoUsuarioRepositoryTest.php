<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Rol;
use App\Domain\Usuario;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\PdoUsuarioRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoUsuarioRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoUsuarioRepository $repositorio;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::forTests();
        $this->pdo->exec('DELETE FROM usuarios');
        $this->repositorio = new PdoUsuarioRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM usuarios');
    }

    public function test_asigna_el_id_de_la_base_al_guardar_un_usuario_nuevo(): void
    {
        // Act
        $guardado = $this->repositorio->save($this->nuevoUsuario('ana@changuito.test'));

        // Assert
        self::assertGreaterThan(0, $guardado->id);
        self::assertSame('ana@changuito.test', $guardado->email);
    }

    public function test_recupera_un_usuario_por_id_con_todos_sus_datos(): void
    {
        // Arrange
        $guardado = $this->repositorio->save($this->nuevoUsuario('ana@changuito.test'));

        // Act
        $recuperado = $this->repositorio->findById($guardado->id);

        // Assert
        self::assertNotNull($recuperado);
        self::assertSame('Ana', $recuperado->nombre);
        self::assertSame(Rol::CLIENTE, $recuperado->rol);
        self::assertTrue($recuperado->activo);
        self::assertTrue($recuperado->verificarPassword('secreta123'));
    }

    public function test_recupera_un_usuario_por_email(): void
    {
        // Arrange
        $this->repositorio->save($this->nuevoUsuario('ana@changuito.test'));

        // Act
        $recuperado = $this->repositorio->findByEmail('ana@changuito.test');

        // Assert
        self::assertNotNull($recuperado);
        self::assertSame('ana@changuito.test', $recuperado->email);
    }

    public function test_devuelve_null_cuando_el_email_no_existe(): void
    {
        // Act
        $recuperado = $this->repositorio->findByEmail('nadie@changuito.test');

        // Assert
        self::assertNull($recuperado);
    }

    public function test_filtra_los_usuarios_por_rol(): void
    {
        // Arrange
        $this->repositorio->save($this->nuevoUsuario('ana@changuito.test'));
        $this->repositorio->save($this->nuevoUsuario('super@changuito.test', Rol::SUPERMERCADO));

        // Act
        $supermercados = $this->repositorio->findByRol(Rol::SUPERMERCADO);

        // Assert
        self::assertCount(1, $supermercados);
        self::assertSame('super@changuito.test', $supermercados[0]->email);
    }

    public function test_devuelve_todos_los_usuarios_cuando_no_se_filtra_por_rol(): void
    {
        // Arrange
        $this->repositorio->save($this->nuevoUsuario('ana@changuito.test'));
        $this->repositorio->save($this->nuevoUsuario('super@changuito.test', Rol::SUPERMERCADO));

        // Act
        $todos = $this->repositorio->findByRol(null);

        // Assert
        self::assertCount(2, $todos);
    }

    public function test_persiste_la_desactivacion_de_una_cuenta(): void
    {
        // Arrange
        $guardado = $this->repositorio->save($this->nuevoUsuario('ana@changuito.test'));
        $guardado->desactivar();

        // Act
        $this->repositorio->save($guardado);

        // Assert
        $recuperado = $this->repositorio->findById($guardado->id);
        self::assertNotNull($recuperado);
        self::assertFalse($recuperado->activo);
    }

    private function nuevoUsuario(string $email, Rol $rol = Rol::CLIENTE): Usuario
    {
        return Usuario::registrar(0, $email, 'secreta123', 'Ana', $rol, new \DateTimeImmutable('2026-09-18 10:00:00'));
    }
}
