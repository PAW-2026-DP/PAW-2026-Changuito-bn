<?php

declare(strict_types=1);

namespace App\Http\Exception;

/**
 * Ninguna ruta registrada matchea el path pedido.
 */
final class RouteNotFoundException extends \RuntimeException
{
}
