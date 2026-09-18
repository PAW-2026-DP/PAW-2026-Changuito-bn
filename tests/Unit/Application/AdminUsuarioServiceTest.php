<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\AdminUsuarioService;
use App\Application\Command\CrearUsuarioCommand;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryUsuarioRepository;

final class AdminUsuarioServiceTest extends TestCase
{
    private InMemoryUsuarioRepository $usuarios;
    private AdminUsuarioService $service;

    protected function setUp(): void
    {
        $this->usuarios = new InMemoryUsuarioRepository();
        $this->service = new AdminUsuarioService($this->usuarios);
    }

    public function test_crea_una_cuenta_con_el_rol_indicado(): void
    {
        // Act
        $usuario = $this->service->crear($this->comando(rol: Rol::REPARTIDOR));

        // Assert
        self::assertSame(Rol::REPARTIDOR, $usuario->rol);
        self::assertTrue($usuario->activo);
        self::assertTrue($usuario->verificarPassword('secreta123'));
    }

    public function test_rechaza_crear_una_cuenta_con_email_repetido(): void
    {
        // Arrange
        $this->service->crear($this->comando());

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->service->crear($this->comando());
    }

    public function test_filtra_el_listado_por_rol(): void
    {
        // Arrange
        $this->service->crear($this->comando(email: 'rider@changuito.test', rol: Rol::REPARTIDOR));
        $this->service->crear($this->comando(email: 'super@changuito.test', rol: Rol::SUPERMERCADO));

        // Act
        $repartidores = $this->service->listar(Rol::REPARTIDOR);

        // Assert
        self::assertCount(1, $repartidores);
        self::assertSame('rider@changuito.test', $repartidores[0]->email);
    }

    public function test_desactivar_hace_baja_logica_sin_borrar_la_cuenta(): void
    {
        // Arrange
        $usuario = $this->service->crear($this->comando());

        // Act
        $desactivado = $this->service->desactivar($usuario->id);

        // Assert
        self::assertFalse($desactivado->activo);
        self::assertNotNull($this->usuarios->findById($usuario->id));
    }

    public function test_activar_vuelve_a_habilitar_la_cuenta(): void
    {
        // Arrange
        $usuario = $this->service->crear($this->comando());
        $this->service->desactivar($usuario->id);

        // Act
        $activado = $this->service->activar($usuario->id);

        // Assert
        self::assertTrue($activado->activo);
    }

    public function test_falla_al_desactivar_un_usuario_inexistente(): void
    {
        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->desactivar(999);
    }

    private function comando(string $email = 'nuevo@changuito.test', Rol $rol = Rol::CLIENTE): CrearUsuarioCommand
    {
        return new CrearUsuarioCommand($email, 'secreta123', 'Nuevo', $rol);
    }
}
