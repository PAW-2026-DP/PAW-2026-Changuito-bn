<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Producto normalizado del catálogo maestro. Lo da de alta el admin; el
 * supermercado solo publica un producto existente en su sucursal
 * (ver ProductoSucursal) o pide el alta de uno nuevo (ver SolicitudAltaProducto).
 */
final class Producto
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly int $categoriaId,
        public readonly TamanioEnvio $tamanioEnvio,
    ) {
        if (trim($nombre) === '') {
            throw new ValidacionException('El nombre del producto no puede estar vacío.');
        }
    }
}
