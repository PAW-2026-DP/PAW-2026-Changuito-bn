<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * El usuario autenticado no tiene permiso sobre el recurso pedido (por
 * ejemplo, un supermercado consultando la sucursal de otro).
 */
final class AccesoDenegadoException extends DomainException
{
}
