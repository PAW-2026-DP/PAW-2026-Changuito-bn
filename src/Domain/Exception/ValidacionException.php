<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Un dato de entrada viola una regla de negocio (monto negativo, cantidad
 * en cero, coordenada fuera de rango, etc.).
 */
final class ValidacionException extends DomainException
{
}
