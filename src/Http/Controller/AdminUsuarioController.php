<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\AdminUsuarioService;
use App\Application\Command\CrearUsuarioCommand;
use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use App\Http\Request;
use App\Http\Response;

/**
 * Alta y baja lógica de cuentas desde el panel de administración.
 */
final class AdminUsuarioController
{
    use CuerpoDeRequestTrait;

    public function __construct(private readonly AdminUsuarioService $usuarios)
    {
    }

    public function listar(Request $request): Response
    {
        $rol = $request->query('rol');

        return Response::json([
            'usuarios' => array_map(
                AuthController::comoJson(...),
                $this->usuarios->listar($rol === null ? null : $this->rol($rol)),
            ),
        ]);
    }

    public function crear(Request $request): Response
    {
        $usuario = $this->usuarios->crear(new CrearUsuarioCommand(
            $this->textoRequerido($request, 'email'),
            $this->textoRequerido($request, 'password'),
            $this->textoRequerido($request, 'nombre'),
            $this->rol($this->textoRequerido($request, 'rol')),
        ));

        return Response::json(AuthController::comoJson($usuario), 201);
    }

    public function activar(Request $request): Response
    {
        return Response::json(AuthController::comoJson(
            $this->usuarios->activar($this->idDeRuta($request, 'id')),
        ));
    }

    public function desactivar(Request $request): Response
    {
        return Response::json(AuthController::comoJson(
            $this->usuarios->desactivar($this->idDeRuta($request, 'id')),
        ));
    }

    private function rol(string $valor): Rol
    {
        return Rol::tryFrom($valor) ?? throw new ValidacionException("El rol {$valor} no existe.");
    }
}
