<?php

declare(strict_types=1);

use App\Application\AdminComercioService;
use App\Application\AdminUsuarioService;
use App\Http\Controller\AdminComercioController;
use App\Http\Controller\AdminUsuarioController;
use App\Infrastructure\Database\PdoUnitOfWork;
use App\Infrastructure\PdoSucursalRepository;
use App\Infrastructure\PdoSupermercadoRepository;
use App\Infrastructure\PdoUsuarioRepository;
use App\Infrastructure\PdoZonaCoberturaRepository;

/**
 * Cableado del panel de administración. Los sub-módulos de admin comparten
 * archivo porque comparten el mismo grupo de rutas y el mismo RolMiddleware.
 *
 * @return array{
 *     adminUsuarioController: AdminUsuarioController,
 *     adminComercioController: AdminComercioController
 * }
 */
return static function (PDO $pdo): array {
    $usuarios = new PdoUsuarioRepository($pdo);

    return [
        'adminUsuarioController' => new AdminUsuarioController(new AdminUsuarioService($usuarios)),
        'adminComercioController' => new AdminComercioController(new AdminComercioService(
            $usuarios,
            new PdoSupermercadoRepository($pdo),
            new PdoSucursalRepository($pdo),
            new PdoZonaCoberturaRepository($pdo),
            new PdoUnitOfWork($pdo),
        )),
    ];
};
