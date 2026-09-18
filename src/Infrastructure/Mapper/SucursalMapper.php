<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper;

use App\Domain\Coordenada;
use App\Domain\Sucursal;

final class SucursalMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function toEntity(array $row): Sucursal
    {
        return new Sucursal(
            (int) $row['id'],
            (int) $row['supermercado_id'],
            (string) $row['nombre'],
            (string) $row['direccion'],
            new Coordenada((float) $row['latitud'], (float) $row['longitud']),
            (bool) $row['activa'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRow(Sucursal $sucursal): array
    {
        return [
            'supermercado_id' => $sucursal->supermercadoId,
            'nombre' => $sucursal->nombre,
            'direccion' => $sucursal->direccion,
            'latitud' => $sucursal->ubicacion->latitud,
            'longitud' => $sucursal->ubicacion->longitud,
            'activa' => (int) $sucursal->activa,
        ];
    }
}
