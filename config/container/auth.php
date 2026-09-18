<?php

declare(strict_types=1);

use App\Application\AutenticacionService;
use App\Http\Controller\AuthController;
use App\Http\Middleware\AutenticacionMiddleware;
use App\Infrastructure\PdoTokenAccesoRepository;
use App\Infrastructure\PdoUsuarioRepository;

/**
 * Cableado del módulo de acceso. Devuelve también el middleware de
 * autenticación porque lo reutiliza cualquier grupo de rutas protegido
 * (clientes, admin), sin volver a armar el service ni los repositorios.
 *
 * @return callable(PDO): array{
 *     autenticacionService: AutenticacionService,
 *     authController: AuthController,
 *     autenticacionMiddleware: AutenticacionMiddleware
 * }
 */
return static function (PDO $pdo): array {
    $autenticacion = new AutenticacionService(
        new PdoUsuarioRepository($pdo),
        new PdoTokenAccesoRepository($pdo),
    );

    return [
        'autenticacionService' => $autenticacion,
        'authController' => new AuthController($autenticacion),
        'autenticacionMiddleware' => new AutenticacionMiddleware($autenticacion),
    ];
};
