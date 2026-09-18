<?php

declare(strict_types=1);

use App\Application\DireccionService;
use App\Http\Controller\DireccionController;
use App\Infrastructure\Database\PdoUnitOfWork;
use App\Infrastructure\PdoDireccionRepository;

/**
 * Cableado del panel del cliente.
 *
 * @return array{direccionController: DireccionController}
 */
return static function (PDO $pdo): array {
    return [
        'direccionController' => new DireccionController(new DireccionService(
            new PdoDireccionRepository($pdo),
            new PdoUnitOfWork($pdo),
        )),
    ];
};
