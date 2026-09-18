<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Exception\CredencialesInvalidasException;
use App\Domain\Usuario;
use App\Http\Middleware\AutenticacionMiddleware;
use App\Http\Request;

/**
 * Lee el usuario que dejó AutenticacionMiddleware en los atributos de la
 * Request. Falla con 401 si la ruta se registró sin ese middleware, en vez
 * de dejar que el controlador opere sobre un null.
 */
final class UsuarioAutenticado
{
    public static function de(Request $request): Usuario
    {
        $usuario = $request->attribute(AutenticacionMiddleware::ATRIBUTO_USUARIO);

        if (!$usuario instanceof Usuario) {
            throw new CredencialesInvalidasException('Falta el token de acceso.');
        }

        return $usuario;
    }
}
