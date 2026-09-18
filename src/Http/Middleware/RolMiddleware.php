<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\AccesoDenegadoException;
use App\Domain\Exception\CredencialesInvalidasException;
use App\Domain\Rol;
use App\Domain\Usuario;
use App\Http\Handler;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

/**
 * Restringe un grupo de rutas a ciertos roles. Va siempre después de
 * AutenticacionMiddleware en la lista del grupo: necesita el usuario que
 * aquel deja en los atributos de la Request.
 */
final class RolMiddleware implements Middleware
{
    /** @var Rol[] */
    private readonly array $rolesPermitidos;

    public function __construct(Rol ...$rolesPermitidos)
    {
        $this->rolesPermitidos = $rolesPermitidos;
    }

    public function process(Request $request, Handler $next): Response
    {
        $usuario = $request->attribute(AutenticacionMiddleware::ATRIBUTO_USUARIO);

        if (!$usuario instanceof Usuario) {
            throw new CredencialesInvalidasException('Falta el token de acceso.');
        }

        if (!in_array($usuario->rol, $this->rolesPermitidos, true)) {
            throw new AccesoDenegadoException('Tu cuenta no tiene acceso a este recurso.');
        }

        return $next->handle($request);
    }
}
