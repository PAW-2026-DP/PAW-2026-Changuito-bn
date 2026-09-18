<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * No se pudo identificar a quien hace la request: faltan credenciales, el
 * email o la contraseña no coinciden, o el token venció o fue revocado. Se
 * usa el mismo mensaje para todos los casos para no revelar qué emails
 * existen.
 */
final class CredencialesInvalidasException extends DomainException
{
}
