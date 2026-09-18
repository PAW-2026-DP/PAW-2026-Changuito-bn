<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\AutenticacionService;
use App\Http\Handler;
use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;
use App\Http\TokenBearer;

/**
 * Resuelve el usuario a partir del token Bearer y lo deja en los atributos
 * de la Request, para que los controladores no vuelvan a mirar headers. Si
 * el token falta, venció o fue revocado, la excepción de dominio termina en
 * un 401 (ver ExceptionMiddleware).
 */
final class AutenticacionMiddleware implements Middleware
{
    public const ATRIBUTO_USUARIO = 'usuarioAutenticado';

    public function __construct(private readonly AutenticacionService $autenticacion)
    {
    }

    public function process(Request $request, Handler $next): Response
    {
        $usuario = $this->autenticacion->usuarioDelToken(TokenBearer::extraer($request));

        return $next->handle($request->withAttribute(self::ATRIBUTO_USUARIO, $usuario));
    }
}
