<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Rol;
use App\Domain\Usuario;
use App\Domain\UsuarioRepositoryInterface;

/**
 * Implementación real de la interfaz sobre un array, para probar los
 * Services sin base de datos (ver reglas-testing.md: Fake antes que Mock).
 */
final class InMemoryUsuarioRepository implements UsuarioRepositoryInterface
{
    /** @var array<int, Usuario> */
    private array $usuarios = [];

    private int $proximoId = 1;

    /**
     * @param Usuario[] $usuarios
     */
    public function __construct(array $usuarios = [])
    {
        foreach ($usuarios as $usuario) {
            $this->save($usuario);
        }
    }

    public function findById(int $id): ?Usuario
    {
        return $this->usuarios[$id] ?? null;
    }

    public function findByEmail(string $email): ?Usuario
    {
        foreach ($this->usuarios as $usuario) {
            if ($usuario->email === $email) {
                return $usuario;
            }
        }

        return null;
    }

    public function findByRol(?Rol $rol): array
    {
        return array_values(array_filter(
            $this->usuarios,
            static fn (Usuario $usuario): bool => $rol === null || $usuario->rol === $rol,
        ));
    }

    public function save(Usuario $usuario): Usuario
    {
        if ($usuario->id === 0) {
            $usuario = new Usuario(
                $this->proximoId++,
                $usuario->email,
                $usuario->passwordHash(),
                $usuario->nombre,
                $usuario->rol,
                $usuario->fechaAlta,
                $usuario->activo,
            );
        }

        $this->usuarios[$usuario->id] = $usuario;

        return $usuario;
    }
}
