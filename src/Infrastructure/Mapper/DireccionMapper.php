<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper;

use App\Domain\Coordenada;
use App\Domain\Direccion;

final class DireccionMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function toEntity(array $row): Direccion
    {
        return new Direccion(
            (int) $row['id'],
            (int) $row['cliente_id'],
            (string) $row['calle'],
            (string) $row['numero'],
            (string) $row['ciudad'],
            (string) $row['cp'],
            new Coordenada((float) $row['latitud'], (float) $row['longitud']),
            (bool) $row['es_default'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRow(Direccion $direccion): array
    {
        return [
            'cliente_id' => $direccion->clienteId,
            'calle' => $direccion->calle,
            'numero' => $direccion->numero,
            'ciudad' => $direccion->ciudad,
            'cp' => $direccion->cp,
            'latitud' => $direccion->ubicacion->latitud,
            'longitud' => $direccion->ubicacion->longitud,
            'es_default' => (int) $direccion->esDefault,
        ];
    }
}
