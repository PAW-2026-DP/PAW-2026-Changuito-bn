<?php

declare(strict_types=1);

use App\Application\AdminUsuarioService;
use App\Http\Controller\AdminUsuarioController;
use App\Infrastructure\PdoUsuarioRepository;

/**
 * Cableado del panel de administración. Los sub-módulos de admin comparten
 * archivo porque comparten el mismo grupo de rutas y el mismo RolMiddleware.
 *
 * @return array{adminUsuarioController: AdminUsuarioController}
 */
return static function (PDO $pdo): array {
    $usuarios = new PdoUsuarioRepository($pdo);

    return [
        'adminUsuarioController' => new AdminUsuarioController(new AdminUsuarioService($usuarios)),
    ];
};
