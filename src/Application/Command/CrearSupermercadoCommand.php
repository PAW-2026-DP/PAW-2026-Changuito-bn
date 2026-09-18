<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Alta de una cadena: crea a la vez la cuenta de acceso y sus datos
 * comerciales.
 */
final class CrearSupermercadoCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $nombre,
        public readonly string $razonSocial,
        public readonly string $cuit,
    ) {
    }
}
