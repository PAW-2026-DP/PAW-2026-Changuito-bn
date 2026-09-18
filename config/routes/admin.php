<?php

declare(strict_types=1);

use App\Domain\Rol;
use App\Http\CallableHandler;
use App\Http\Middleware\RolMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

/**
 * Panel de administración. El grupo entero exige token válido y rol
 * administrador, así ninguna ruta de acá puede quedar abierta por olvido.
 */
return static function (Router $router, PDO $pdo): void {
    $auth = (require __DIR__ . '/../container/auth.php')($pdo);
    $modulo = (require __DIR__ . '/../container/admin.php')($pdo);

    $middlewares = [$auth['autenticacionMiddleware'], new RolMiddleware(Rol::ADMINISTRADOR)];

    $router->group('/admin', $middlewares, static function (Router $router) use ($modulo): void {
        $usuarios = $modulo['adminUsuarioController'];

        $router->get('/usuarios', new CallableHandler(
            static fn (Request $request): Response => $usuarios->listar($request),
        ));

        $router->post('/usuarios', new CallableHandler(
            static fn (Request $request): Response => $usuarios->crear($request),
        ));

        $router->post('/usuarios/{id}/activacion', new CallableHandler(
            static fn (Request $request): Response => $usuarios->activar($request),
        ));

        $router->post('/usuarios/{id}/desactivacion', new CallableHandler(
            static fn (Request $request): Response => $usuarios->desactivar($request),
        ));

        $comercios = $modulo['adminComercioController'];

        $router->get('/supermercados', new CallableHandler(
            static fn (Request $request): Response => $comercios->listarSupermercados($request),
        ));

        $router->post('/supermercados', new CallableHandler(
            static fn (Request $request): Response => $comercios->crearSupermercado($request),
        ));

        $router->get('/supermercados/{id}/sucursales', new CallableHandler(
            static fn (Request $request): Response => $comercios->listarSucursales($request),
        ));

        $router->post('/supermercados/{id}/sucursales', new CallableHandler(
            static fn (Request $request): Response => $comercios->crearSucursal($request),
        ));

        $router->put('/sucursales/{id}', new CallableHandler(
            static fn (Request $request): Response => $comercios->actualizarSucursal($request),
        ));

        $router->put('/sucursales/{id}/zona-cobertura', new CallableHandler(
            static fn (Request $request): Response => $comercios->definirZonaCobertura($request),
        ));

        $catalogo = $modulo['adminCatalogoController'];

        $router->get('/categorias', new CallableHandler(
            static fn (Request $request): Response => $catalogo->listarCategorias($request),
        ));

        $router->post('/categorias', new CallableHandler(
            static fn (Request $request): Response => $catalogo->crearCategoria($request),
        ));

        $router->get('/productos', new CallableHandler(
            static fn (Request $request): Response => $catalogo->buscarProductos($request),
        ));

        $router->post('/productos', new CallableHandler(
            static fn (Request $request): Response => $catalogo->crearProducto($request),
        ));

        $router->put('/productos/{id}', new CallableHandler(
            static fn (Request $request): Response => $catalogo->actualizarProducto($request),
        ));
    });
};
