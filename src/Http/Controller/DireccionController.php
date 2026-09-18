<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Command\GuardarDireccionCommand;
use App\Application\DireccionService;
use App\Domain\Direccion;
use App\Http\Request;
use App\Http\Response;

/**
 * Direcciones del cliente autenticado. El id del cliente sale siempre del
 * token (ver UsuarioAutenticado), nunca de la URL ni del body: por eso las
 * rutas usan `me` en lugar de un id.
 */
final class DireccionController
{
    use CuerpoDeRequestTrait;

    public function __construct(private readonly DireccionService $direcciones)
    {
    }

    public function listar(Request $request): Response
    {
        $clienteId = UsuarioAutenticado::de($request)->id;

        return Response::json([
            'direcciones' => array_map(self::comoJson(...), $this->direcciones->listar($clienteId)),
        ]);
    }

    public function crear(Request $request): Response
    {
        $direccion = $this->direcciones->crear($this->comando($request));

        return Response::json(self::comoJson($direccion), 201);
    }

    public function actualizar(Request $request): Response
    {
        $direccion = $this->direcciones->actualizar($this->comando($request, $this->idDeRuta($request, 'id')));

        return Response::json(self::comoJson($direccion));
    }

    public function eliminar(Request $request): Response
    {
        $this->direcciones->eliminar(
            $this->idDeRuta($request, 'id'),
            UsuarioAutenticado::de($request)->id,
        );

        return Response::noContent();
    }

    public function marcarPredeterminada(Request $request): Response
    {
        $direccion = $this->direcciones->marcarPredeterminada(
            $this->idDeRuta($request, 'id'),
            UsuarioAutenticado::de($request)->id,
        );

        return Response::json(self::comoJson($direccion));
    }

    private function comando(Request $request, int $id = 0): GuardarDireccionCommand
    {
        return new GuardarDireccionCommand(
            UsuarioAutenticado::de($request)->id,
            $this->textoRequerido($request, 'calle'),
            $this->textoRequerido($request, 'numero'),
            $this->textoRequerido($request, 'ciudad'),
            $this->textoRequerido($request, 'cp'),
            $this->decimalRequerido($request, 'latitud'),
            $this->decimalRequerido($request, 'longitud'),
            $id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function comoJson(Direccion $direccion): array
    {
        return [
            'id' => $direccion->id,
            'calle' => $direccion->calle,
            'numero' => $direccion->numero,
            'ciudad' => $direccion->ciudad,
            'cp' => $direccion->cp,
            'latitud' => $direccion->ubicacion->latitud,
            'longitud' => $direccion->ubicacion->longitud,
            'esDefault' => $direccion->esDefault,
        ];
    }
}
