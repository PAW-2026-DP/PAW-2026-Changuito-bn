<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use App\Domain\Usuario;
use PHPUnit\Framework\TestCase;

final class UsuarioTest extends TestCase
{
    public function test_registrar_crea_un_usuario_activo_con_la_contrasenia_hasheada(): void
    {
        // Arrange / Act
        $usuario = Usuario::registrar(1, 'ana@example.com', 'secreta123', 'Ana', Rol::CLIENTE, new \DateTimeImmutable());

        // Assert
        self::assertTrue($usuario->activo);
        self::assertNotSame('secreta123', $usuario->passwordHash());
        self::assertTrue($usuario->verificarPassword('secreta123'));
        self::assertFalse($usuario->verificarPassword('otra'));
    }

    public function test_lanza_excepcion_si_el_email_esta_vacio(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        Usuario::registrar(1, '', 'secreta123', 'Ana', Rol::CLIENTE, new \DateTimeImmutable());
    }

    public function test_lanza_excepcion_si_el_nombre_esta_vacio(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        Usuario::registrar(1, 'ana@example.com', 'secreta123', '', Rol::CLIENTE, new \DateTimeImmutable());
    }

    public function test_desactivar_y_activar_cambian_el_estado_de_la_cuenta(): void
    {
        // Arrange
        $usuario = Usuario::registrar(1, 'ana@example.com', 'secreta123', 'Ana', Rol::CLIENTE, new \DateTimeImmutable());

        // Act
        $usuario->desactivar();

        // Assert
        self::assertFalse($usuario->activo);

        // Act
        $usuario->activar();

        // Assert
        self::assertTrue($usuario->activo);
    }
}
