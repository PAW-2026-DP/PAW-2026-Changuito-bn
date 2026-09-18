<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Usuario;

/**
 * Resultado de un login. El token plano se devuelve una única vez, acá: en
 * la base solo queda su hash.
 */
final class SesionIniciada
{
    public function __construct(
        public readonly Usuario $usuario,
        public readonly string $tokenPlano,
        public readonly \DateTimeImmutable $expiraEn,
    ) {
    }
}
