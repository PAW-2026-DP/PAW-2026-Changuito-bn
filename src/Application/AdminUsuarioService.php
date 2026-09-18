<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Command\CrearUsuarioCommand;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use App\Domain\Usuario;
use App\Domain\UsuarioRepositoryInterface;

/**
 * Alta y baja lógica de cuentas por parte del administrador. La baja nunca
 * borra la fila: un usuario desactivado tiene que seguir siendo trazable
 * desde los pedidos que ya hizo.
 */
final class AdminUsuarioService
{
    public function __construct(private readonly UsuarioRepositoryInterface $usuarios)
    {
    }

    /**
     * @return Usuario[]
     */
    public function listar(?Rol $rol): array
    {
        return $this->usuarios->findByRol($rol);
    }

    public function crear(CrearUsuarioCommand $command): Usuario
    {
        if ($this->usuarios->findByEmail($command->email) !== null) {
            throw new ValidacionException('Ya existe una cuenta con ese email.');
        }

        return $this->usuarios->save(Usuario::registrar(
            0,
            $command->email,
            $command->password,
            $command->nombre,
            $command->rol,
            new \DateTimeImmutable(),
        ));
    }

    public function activar(int $id): Usuario
    {
        $usuario = $this->obtener($id);
        $usuario->activar();

        return $this->usuarios->save($usuario);
    }

    public function desactivar(int $id): Usuario
    {
        $usuario = $this->obtener($id);
        $usuario->desactivar();

        return $this->usuarios->save($usuario);
    }

    private function obtener(int $id): Usuario
    {
        $usuario = $this->usuarios->findById($id);

        if ($usuario === null) {
            throw new EntidadNoEncontradaException('El usuario no existe.');
        }

        return $usuario;
    }
}
