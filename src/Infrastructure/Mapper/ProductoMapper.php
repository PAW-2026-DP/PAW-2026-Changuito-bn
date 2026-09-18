<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper;

use App\Domain\Producto;
use App\Domain\TamanioEnvio;

final class ProductoMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function toEntity(array $row): Producto
    {
        return new Producto(
            (int) $row['id'],
            (string) $row['nombre'],
            (string) $row['descripcion'],
            (int) $row['categoria_id'],
            TamanioEnvio::from((string) $row['tamanio_envio']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRow(Producto $producto): array
    {
        return [
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'categoria_id' => $producto->categoriaId,
            'tamanio_envio' => $producto->tamanioEnvio->value,
        ];
    }
}
