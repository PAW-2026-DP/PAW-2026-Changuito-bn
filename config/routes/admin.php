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
    });
};
