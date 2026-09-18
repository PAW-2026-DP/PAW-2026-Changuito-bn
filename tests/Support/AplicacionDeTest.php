<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\Handler;
use App\Http\Pipeline;
use App\Http\Router;
use App\Infrastructure\Database\ConnectionFactory;
use PDO;

/**
 * Arma la app con las rutas y middlewares reales, pero contra la base de
 * test, para que los tests de integración de Http no escriban en la base de
 * desarrollo (ver ConnectionFactory::forTests()).
 */
final class AplicacionDeTest
{
    public static function crear(PDO $pdo): Handler
    {
        $config = dirname(__DIR__, 2) . '/config';

        $router = new Router();
        (require $config . '/routes.php')($router, $pdo);

        return new Pipeline($router, (require $config . '/container/middlewares.php')());
    }

    public static function conexion(): PDO
    {
        return ConnectionFactory::forTests();
    }
}
