<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\AutenticacionService;
use App\Application\Command\LoginCommand;
use App\Application\Command\RegistrarClienteCommand;
use App\Domain\Usuario;
use App\Http\Request;
use App\Http\Response;
use App\Http\TokenBearer;

/**
 * Traduce las requests de acceso a comandos de aplicación. No decide nada
 * de negocio: quién puede registrarse, si la contraseña es válida o cuándo
 * vence un token lo resuelve AutenticacionService.
 */
final class AuthController
{
    use CuerpoDeRequestTrait;

    public function __construct(private readonly AutenticacionService $autenticacion)
    {
    }

    public function registrar(Request $request): Response
    {
        $usuario = $this->autenticacion->registrarCliente(new RegistrarClienteCommand(
            $this->textoRequerido($request, 'email'),
            $this->textoRequerido($request, 'password'),
            $this->textoRequerido($request, 'nombre'),
        ));

        return Response::json(self::comoJson($usuario), 201);
    }

    public function login(Request $request): Response
    {
        $sesion = $this->autenticacion->login(new LoginCommand(
            $this->textoRequerido($request, 'email'),
            $this->textoRequerido($request, 'password'),
        ));

        return Response::json([
            'token' => $sesion->tokenPlano,
            'expiraEn' => $sesion->expiraEn->format(\DateTimeInterface::ATOM),
            'usuario' => self::comoJson($sesion->usuario),
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->autenticacion->logout(TokenBearer::extraer($request));

        return Response::noContent();
    }

    public function me(Request $request): Response
    {
        return Response::json(self::comoJson(UsuarioAutenticado::de($request)));
    }

    /**
     * @return array<string, mixed>
     */
    public static function comoJson(Usuario $usuario): array
    {
        return [
            'id' => $usuario->id,
            'email' => $usuario->email,
            'nombre' => $usuario->nombre,
            'rol' => $usuario->rol->value,
            'activo' => $usuario->activo,
        ];
    }
}
