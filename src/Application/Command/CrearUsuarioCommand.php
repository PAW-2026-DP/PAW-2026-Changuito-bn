<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Rol;

/**
 * Alta de una cuenta desde el panel de administración. A diferencia del
 * registro público, acá el rol es explícito: es la única vía para crear
 * supermercados, repartidores y administradores.
 */
final class CrearUsuarioCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $nombre,
        public readonly Rol $rol,
    ) {
    }
}
