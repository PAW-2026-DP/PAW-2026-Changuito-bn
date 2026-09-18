<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Exception\CredencialesInvalidasException;

/**
 * Extrae el token del header `Authorization: Bearer <token>`. Lo usan tanto
 * el middleware de autenticación como el logout, que necesita el token
 * concreto a revocar.
 */
final class TokenBearer
{
    private const PREFIJO = 'Bearer ';

    public static function extraer(Request $request): string
    {
        $header = $request->header('Authorization');

        if ($header === null || !str_starts_with($header, self::PREFIJO)) {
            throw new CredencialesInvalidasException('Falta el token de acceso.');
        }

        $token = trim(substr($header, strlen(self::PREFIJO)));

        if ($token === '') {
            throw new CredencialesInvalidasException('Falta el token de acceso.');
        }

        return $token;
    }
}
