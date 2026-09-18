<?php

declare(strict_types=1);

use App\Http\Controller\PingController;
use App\Http\Router;

/**
 * Registro de rutas de la app. `/health` queda fuera del versionado por ser
 * infraestructura (lo usan los healthchecks del deploy). Las rutas de
 * negocio van bajo `/api/v1`, un archivo por módulo en `config/routes/`,
 * para que cada módulo se pueda desarrollar en su propia rama sin pisar
 * este archivo (ver plans/rutas-controladores-y-ramas.md).
 *
 * Cada archivo de módulo recibe la misma conexión PDO, así todos los
 * repositorios de una request comparten una única conexión.
 */
return static function (Router $router, PDO $pdo): void {
    $router->get('/health', new PingController());

    $router->group('/api/v1', [], static function (Router $router) use ($pdo): void {
        $moduleRouteFiles = glob(__DIR__ . '/routes/*.php') ?: [];
        sort($moduleRouteFiles);

        foreach ($moduleRouteFiles as $moduleRouteFile) {
            (require $moduleRouteFile)($router, $pdo);
        }
    });
};
