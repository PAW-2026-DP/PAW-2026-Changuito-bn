<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Command\LoginCommand;
use App\Application\Command\RegistrarClienteCommand;
use App\Domain\Exception\CredencialesInvalidasException;
use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use App\Domain\TokenAcceso;
use App\Domain\TokenAccesoRepositoryInterface;
use App\Domain\Usuario;
use App\Domain\UsuarioRepositoryInterface;

/**
 * Casos de uso de acceso a la plataforma: alta pública de clientes, login,
 * logout y resolución del usuario a partir de un token Bearer.
 *
 * El alta pública crea únicamente clientes; las cuentas de supermercado,
 * repartidor y admin las da de alta el administrador.
 */
final class AutenticacionService
{
    private const BYTES_DE_TOKEN = 32;
    private const DURACION_TOKEN = 'P7D';

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly TokenAccesoRepositoryInterface $tokens,
    ) {
    }

    public function registrarCliente(RegistrarClienteCommand $command): Usuario
    {
        if ($this->usuarios->findByEmail($command->email) !== null) {
            throw new ValidacionException('Ya existe una cuenta con ese email.');
        }

        return $this->usuarios->save(Usuario::registrar(
            0,
            $command->email,
            $command->password,
            $command->nombre,
            Rol::CLIENTE,
            new \DateTimeImmutable(),
        ));
    }

    public function login(LoginCommand $command): SesionIniciada
    {
        $usuario = $this->usuarios->findByEmail($command->email);

        if ($usuario === null || !$usuario->activo || !$usuario->verificarPassword($command->password)) {
            throw new CredencialesInvalidasException('Email o contraseña incorrectos.');
        }

        $tokenPlano = bin2hex(random_bytes(self::BYTES_DE_TOKEN));

        $token = $this->tokens->save(TokenAcceso::emitir(
            0,
            $usuario->id,
            $tokenPlano,
            new \DateTimeImmutable(),
            new \DateInterval(self::DURACION_TOKEN),
        ));

        return new SesionIniciada($usuario, $tokenPlano, $token->expiraEn);
    }

    public function logout(string $tokenPlano): void
    {
        $token = $this->tokens->findByHash(TokenAcceso::hash($tokenPlano));

        if ($token === null || !$token->esValido(new \DateTimeImmutable())) {
            throw new CredencialesInvalidasException('El token no es válido.');
        }

        $token->revocar(new \DateTimeImmutable());
        $this->tokens->save($token);
    }

    public function usuarioDelToken(string $tokenPlano): Usuario
    {
        $token = $this->tokens->findByHash(TokenAcceso::hash($tokenPlano));

        if ($token === null || !$token->esValido(new \DateTimeImmutable())) {
            throw new CredencialesInvalidasException('El token no es válido.');
        }

        $usuario = $this->usuarios->findById($token->usuarioId);

        if ($usuario === null || !$usuario->activo) {
            throw new CredencialesInvalidasException('El token no es válido.');
        }

        return $usuario;
    }
}
