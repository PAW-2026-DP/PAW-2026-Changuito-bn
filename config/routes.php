<?php

declare(strict_types=1);

use App\Http\Controller\PingController;
use App\Http\Router;

/**
 * Registro de rutas de la app. Se agregan acá a medida que llegan las
 * reglas de negocio de cada módulo; por ahora solo hay una ruta de
 * verificación (`/health`) y ejemplos comentados por rol, apuntando todos
 * al PingController de prueba hasta tener los controladores reales.
 */
return static function (Router $router): void {
    $router->get('/health', new PingController());

    // --- Cliente ---
    // TODO: reemplazar por controladores reales según reglas de negocio.
    // $router->get('/pedidos', new PingController());
    // $router->post('/pedidos', new PingController());
    // $router->get('/pedidos/{id}', new PingController());

    // --- Supermercado ---
    // TODO: reemplazar por controladores reales según reglas de negocio.
    // $router->get('/supermercados/{id}/productos', new PingController());
    // $router->put('/supermercados/{id}/productos/{productId}', new PingController());

    // --- Repartidor ---
    // TODO: reemplazar por controladores reales según reglas de negocio.
    // $router->get('/entregas', new PingController());
    // $router->patch('/entregas/{id}', new PingController());

    // --- Administrador ---
    // TODO: reemplazar por controladores reales según reglas de negocio.
    // $router->get('/admin/usuarios', new PingController());
};
