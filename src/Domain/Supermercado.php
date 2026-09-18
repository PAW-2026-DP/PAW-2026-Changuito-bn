<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Datos comerciales de una cadena. Es una extensión 1:1 del Usuario con rol
 * SUPERMERCADO: la cuenta de acceso vive en `usuarios` y acá solo van los
 * datos que el resto de los roles no tienen.
 */
final class Supermercado
{
    public function __construct(
        public readonly int $usuarioId,
        public readonly string $razonSocial,
        public readonly string $cuit,
    ) {
        if (trim($razonSocial) === '') {
            throw new ValidacionException('La razón social no puede estar vacía.');
        }

        if (trim($cuit) === '') {
            throw new ValidacionException('El CUIT no puede estar vacío.');
        }
    }
}
