<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Radio dentro del cual se le ofrece un pedido a un repartidor. Es una sola
 * fila de configuración global que ajusta el admin (ver
 * ElegibilidadRepartidor).
 */
final class ParametroReparto
{
    public function __construct(public readonly float $radioOfertaKm)
    {
        if ($radioOfertaKm <= 0.0) {
            throw new ValidacionException('El radio de oferta debe ser positivo.');
        }
    }
}
