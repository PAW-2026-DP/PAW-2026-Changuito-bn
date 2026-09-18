<?php

declare(strict_types=1);

use App\Http\CallableHandler;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

/**
 * Panel del cliente. Todo el grupo exige token válido: el id del cliente
 * sale del usuario autenticado, por eso las rutas dicen `me` y no un id.
 */
return static function (Router $router, PDO $pdo): void {
    $auth = (require __DIR__ . '/../container/auth.php')($pdo);
    $controller = (require __DIR__ . '/../container/clientes.php')($pdo)['direccionController'];

    $router->group('/clientes/me', [$auth['autenticacionMiddleware']], static function (Router $router) use ($controller): void {
        $router->get('/direcciones', new CallableHandler(
            static fn (Request $request): Response => $controller->listar($request),
        ));

        $router->post('/direcciones', new CallableHandler(
            static fn (Request $request): Response => $controller->crear($request),
        ));

        $router->put('/direcciones/{id}', new CallableHandler(
            static fn (Request $request): Response => $controller->actualizar($request),
        ));

        $router->delete('/direcciones/{id}', new CallableHandler(
            static fn (Request $request): Response => $controller->eliminar($request),
        ));

        $router->post('/direcciones/{id}/predeterminada', new CallableHandler(
            static fn (Request $request): Response => $controller->marcarPredeterminada($request),
        ));
    });
};
