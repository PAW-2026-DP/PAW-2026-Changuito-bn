<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * El recurso de dominio pedido (por id u otro criterio) no existe.
 */
final class EntidadNoEncontradaException extends DomainException
{
}
