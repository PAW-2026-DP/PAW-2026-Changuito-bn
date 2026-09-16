<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Handler;
use App\Http\Request;
use App\Http\Response;

/**
 * Controlador de prueba reutilizable por cualquier ruta prototipada, hasta
 * que existan los controladores reales según las reglas de negocio. Hace
 * eco del método, path y parámetros recibidos para validar el ruteo.
 */
final class PingController implements Handler
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'status' => 'ok',
            'method' => $request->method,
            'path' => $request->path,
            'params' => $request->routeParams,
        ]);
    }
}
