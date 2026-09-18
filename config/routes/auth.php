<?php

declare(strict_types=1);

use App\Http\CallableHandler;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

/**
 * Rutas de acceso. El registro y el login son públicos; logout y me exigen
 * un token válido, así que cuelgan de un grupo con AutenticacionMiddleware.
 */
return static function (Router $router, PDO $pdo): void {
    $modulo = (require __DIR__ . '/../container/auth.php')($pdo);
    $controller = $modulo['authController'];

    $router->post('/auth/registro', new CallableHandler(
        static fn (Request $request): Response => $controller->registrar($request),
    ));

    $router->post('/auth/login', new CallableHandler(
        static fn (Request $request): Response => $controller->login($request),
    ));

    $router->group('', [$modulo['autenticacionMiddleware']], static function (Router $router) use ($controller): void {
        $router->post('/auth/logout', new CallableHandler(
            static fn (Request $request): Response => $controller->logout($request),
        ));

        $router->get('/auth/me', new CallableHandler(
            static fn (Request $request): Response => $controller->me($request),
        ));
    });
};
