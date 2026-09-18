<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\AutenticacionService;
use App\Application\Command\LoginCommand;
use App\Application\Command\RegistrarClienteCommand;
use App\Domain\Exception\CredencialesInvalidasException;
use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use App\Domain\TokenAcceso;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryTokenAccesoRepository;
use Tests\Fakes\InMemoryUsuarioRepository;

final class AutenticacionServiceTest extends TestCase
{
    private InMemoryUsuarioRepository $usuarios;
    private InMemoryTokenAccesoRepository $tokens;
    private AutenticacionService $service;

    protected function setUp(): void
    {
        $this->usuarios = new InMemoryUsuarioRepository();
        $this->tokens = new InMemoryTokenAccesoRepository();
        $this->service = new AutenticacionService($this->usuarios, $this->tokens);
    }

    public function test_registra_un_cliente_con_la_contrasenia_hasheada(): void
    {
        // Act
        $usuario = $this->service->registrarCliente(
            new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'),
        );

        // Assert
        self::assertSame(Rol::CLIENTE, $usuario->rol);
        self::assertTrue($usuario->verificarPassword('secreta123'));
        self::assertNotSame('secreta123', $usuario->passwordHash());
        self::assertNotNull($this->usuarios->findByEmail('ana@changuito.test'));
    }

    public function test_rechaza_registrar_un_email_ya_usado(): void
    {
        // Arrange
        $this->service->registrarCliente(new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'));

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->service->registrarCliente(new RegistrarClienteCommand('ana@changuito.test', 'otra123456', 'Otra Ana'));
    }

    public function test_el_login_devuelve_un_token_que_identifica_al_usuario(): void
    {
        // Arrange
        $this->service->registrarCliente(new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'));

        // Act
        $sesion = $this->service->login(new LoginCommand('ana@changuito.test', 'secreta123'));

        // Assert
        self::assertNotSame('', $sesion->tokenPlano);
        self::assertSame('ana@changuito.test', $this->service->usuarioDelToken($sesion->tokenPlano)->email);
    }

    public function test_no_guarda_el_token_en_texto_plano(): void
    {
        // Arrange
        $this->service->registrarCliente(new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'));

        // Act
        $sesion = $this->service->login(new LoginCommand('ana@changuito.test', 'secreta123'));

        // Assert
        self::assertNull($this->tokens->findByHash($sesion->tokenPlano));
        self::assertNotNull($this->tokens->findByHash(TokenAcceso::hash($sesion->tokenPlano)));
    }

    public function test_rechaza_el_login_con_contrasenia_incorrecta(): void
    {
        // Arrange
        $this->service->registrarCliente(new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'));

        // Assert
        $this->expectException(CredencialesInvalidasException::class);

        // Act
        $this->service->login(new LoginCommand('ana@changuito.test', 'incorrecta'));
    }

    public function test_rechaza_el_login_de_un_email_inexistente(): void
    {
        // Assert
        $this->expectException(CredencialesInvalidasException::class);

        // Act
        $this->service->login(new LoginCommand('nadie@changuito.test', 'secreta123'));
    }

    public function test_rechaza_el_login_de_una_cuenta_desactivada(): void
    {
        // Arrange
        $usuario = $this->service->registrarCliente(
            new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'),
        );
        $usuario->desactivar();
        $this->usuarios->save($usuario);

        // Assert
        $this->expectException(CredencialesInvalidasException::class);

        // Act
        $this->service->login(new LoginCommand('ana@changuito.test', 'secreta123'));
    }

    public function test_el_logout_invalida_el_token(): void
    {
        // Arrange
        $this->service->registrarCliente(new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'));
        $sesion = $this->service->login(new LoginCommand('ana@changuito.test', 'secreta123'));

        // Act
        $this->service->logout($sesion->tokenPlano);

        // Assert
        $this->expectException(CredencialesInvalidasException::class);
        $this->service->usuarioDelToken($sesion->tokenPlano);
    }

    public function test_rechaza_un_token_desconocido(): void
    {
        // Assert
        $this->expectException(CredencialesInvalidasException::class);

        // Act
        $this->service->usuarioDelToken('token-que-no-existe');
    }

    public function test_rechaza_un_token_vencido(): void
    {
        // Arrange
        $usuario = $this->service->registrarCliente(
            new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'),
        );
        $vencido = new TokenAcceso(
            0,
            $usuario->id,
            TokenAcceso::hash('token-vencido'),
            new \DateTimeImmutable('2026-01-01 10:00:00'),
            new \DateTimeImmutable('2026-01-02 10:00:00'),
        );
        $this->tokens->save($vencido);

        // Assert
        $this->expectException(CredencialesInvalidasException::class);

        // Act
        $this->service->usuarioDelToken('token-vencido');
    }

    public function test_rechaza_el_token_de_una_cuenta_desactivada_despues_del_login(): void
    {
        // Arrange
        $usuario = $this->service->registrarCliente(
            new RegistrarClienteCommand('ana@changuito.test', 'secreta123', 'Ana'),
        );
        $sesion = $this->service->login(new LoginCommand('ana@changuito.test', 'secreta123'));
        $usuario->desactivar();
        $this->usuarios->save($usuario);

        // Assert
        $this->expectException(CredencialesInvalidasException::class);

        // Act
        $this->service->usuarioDelToken($sesion->tokenPlano);
    }
}
