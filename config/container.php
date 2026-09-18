<?php

declare(strict_types=1);

use App\Http\Pipeline;
use App\Http\Router;

/**
 * Arma la app: abre la conexión, registra las rutas (config/routes.php) y
 * las envuelve en el Pipeline con los middlewares globales
 * (config/container/middlewares.php). Agregar un middleware nuevo, o el
 * cableado de un módulo nuevo en config/container/, no requiere tocar esta
 * función.
 *
 * @return App\Http\Handler
 */
return (static function () {
    $router = new Router();
    $pdo = (require __DIR__ . '/container/database.php')();

    (require __DIR__ . '/routes.php')($router, $pdo);

    $middlewares = (require __DIR__ . '/container/middlewares.php')();

    return new Pipeline($router, $middlewares);
})();
