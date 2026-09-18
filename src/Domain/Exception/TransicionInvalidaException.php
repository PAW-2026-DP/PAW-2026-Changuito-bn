<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Se pidió una transición de estado que la entidad no permite desde su
 * estado actual (por ejemplo, cancelar un Pedido que ya está EN_CAMINO).
 */
final class TransicionInvalidaException extends DomainException
{
}
