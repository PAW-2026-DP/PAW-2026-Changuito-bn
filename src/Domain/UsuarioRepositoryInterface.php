<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Persistencia de cuentas de acceso.
 *
 * `save()` devuelve el usuario ya persistido porque el id lo asigna la base:
 * un Usuario recién construido llega con id 0 y vuelve con el definitivo.
 */
interface UsuarioRepositoryInterface
{
    public function findById(int $id): ?Usuario;

    public function findByEmail(string $email): ?Usuario;

    /**
     * @return Usuario[]
     */
    public function findByRol(?Rol $rol): array;

    public function save(Usuario $usuario): Usuario;
}
