<?php

declare(strict_types=1);

use App\Http\Pipeline;
use App\Http\Router;

/**
 * Arma la app: registra las rutas y la envuelve en el Pipeline de
 * middlewares (vacío por ahora — se van agregando acá sin tocar
 * controladores existentes).
 *
 * @return App\Http\Handler
 */
return (static function () {
    $router = new Router();

    (require __DIR__ . '/routes.php')($router);

    $middlewares = [
        // TODO: agregar middlewares (auth, CORS, logging) a medida que se necesiten.
    ];

    return new Pipeline($router, $middlewares);
})();
