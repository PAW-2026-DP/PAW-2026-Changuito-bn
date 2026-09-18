<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\AdminComercioService;
use App\Application\Command\CrearSupermercadoCommand;
use App\Application\Command\GuardarSucursalCommand;
use App\Domain\Sucursal;
use App\Domain\Supermercado;
use App\Domain\ZonaCobertura;
use App\Http\Request;
use App\Http\Response;

/**
 * Incorporación de comercios, sus sucursales y sus zonas de cobertura.
 */
final class AdminComercioController
{
    use CuerpoDeRequestTrait;

    public function __construct(private readonly AdminComercioService $comercios)
    {
    }

    public function listarSupermercados(Request $request): Response
    {
        return Response::json([
            'supermercados' => array_map(self::supermercadoComoJson(...), $this->comercios->listarSupermercados()),
        ]);
    }

    public function crearSupermercado(Request $request): Response
    {
        $supermercado = $this->comercios->crearSupermercado(new CrearSupermercadoCommand(
            $this->textoRequerido($request, 'email'),
            $this->textoRequerido($request, 'password'),
            $this->textoRequerido($request, 'nombre'),
            $this->textoRequerido($request, 'razonSocial'),
            $this->textoRequerido($request, 'cuit'),
        ));

        return Response::json(self::supermercadoComoJson($supermercado), 201);
    }

    public function listarSucursales(Request $request): Response
    {
        return Response::json([
            'sucursales' => array_map(
                self::sucursalComoJson(...),
                $this->comercios->listarSucursales($this->idDeRuta($request, 'id')),
            ),
        ]);
    }

    public function crearSucursal(Request $request): Response
    {
        $sucursal = $this->comercios->crearSucursal(new GuardarSucursalCommand(
            $this->idDeRuta($request, 'id'),
            $this->textoRequerido($request, 'nombre'),
            $this->textoRequerido($request, 'direccion'),
            $this->decimalRequerido($request, 'latitud'),
            $this->decimalRequerido($request, 'longitud'),
            $this->booleanoOpcional($request, 'activa', true),
        ));

        return Response::json(self::sucursalComoJson($sucursal), 201);
    }

    public function actualizarSucursal(Request $request): Response
    {
        $sucursal = $this->comercios->actualizarSucursal(new GuardarSucursalCommand(
            0,
            $this->textoRequerido($request, 'nombre'),
            $this->textoRequerido($request, 'direccion'),
            $this->decimalRequerido($request, 'latitud'),
            $this->decimalRequerido($request, 'longitud'),
            $this->booleanoOpcional($request, 'activa', true),
            $this->idDeRuta($request, 'id'),
        ));

        return Response::json(self::sucursalComoJson($sucursal));
    }

    public function definirZonaCobertura(Request $request): Response
    {
        $zona = $this->comercios->definirZonaCobertura(
            $this->idDeRuta($request, 'id'),
            $this->decimalRequerido($request, 'radioMinKm'),
            $this->decimalRequerido($request, 'radioMaxKm'),
        );

        return Response::json(self::zonaComoJson($zona));
    }

    /**
     * @return array<string, mixed>
     */
    private static function supermercadoComoJson(Supermercado $supermercado): array
    {
        return [
            'id' => $supermercado->usuarioId,
            'razonSocial' => $supermercado->razonSocial,
            'cuit' => $supermercado->cuit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function sucursalComoJson(Sucursal $sucursal): array
    {
        return [
            'id' => $sucursal->id,
            'supermercadoId' => $sucursal->supermercadoId,
            'nombre' => $sucursal->nombre,
            'direccion' => $sucursal->direccion,
            'latitud' => $sucursal->ubicacion->latitud,
            'longitud' => $sucursal->ubicacion->longitud,
            'activa' => $sucursal->activa,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function zonaComoJson(ZonaCobertura $zona): array
    {
        return [
            'sucursalId' => $zona->sucursalId,
            'radioMinKm' => $zona->radioMinKm,
            'radioMaxKm' => $zona->radioMaxKm,
        ];
    }
}
