<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Entrada de AutenticacionService::registrarCliente(), armada por el
 * controlador a partir del body de la request.
 */
final class RegistrarClienteCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $nombre,
    ) {
    }
}
