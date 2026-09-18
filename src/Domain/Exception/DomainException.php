<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Base común de las excepciones de dominio del proyecto. No se lanza
 * directamente: cada regla de negocio violada usa una subclase concreta,
 * que ExceptionMiddleware traduce al status HTTP correspondiente.
 */
abstract class DomainException extends \RuntimeException
{
}
