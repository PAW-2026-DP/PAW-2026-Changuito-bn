<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Ubicación geográfica. Encapsula el cálculo de distancia (fórmula de
 * Haversine) que usarán la política de cobertura y la elegibilidad de
 * repartidores, para no repetirlo en cada servicio de dominio.
 */
final class Coordenada
{
    private const RADIO_TIERRA_KM = 6371.0;

    public function __construct(
        public readonly float $latitud,
        public readonly float $longitud,
    ) {
        if ($latitud < -90.0 || $latitud > 90.0) {
            throw new ValidacionException('La latitud debe estar entre -90 y 90.');
        }

        if ($longitud < -180.0 || $longitud > 180.0) {
            throw new ValidacionException('La longitud debe estar entre -180 y 180.');
        }
    }

    public function distanciaEnKm(self $otra): float
    {
        $deltaLatitud = deg2rad($otra->latitud - $this->latitud);
        $deltaLongitud = deg2rad($otra->longitud - $this->longitud);

        $a = sin($deltaLatitud / 2) ** 2
            + cos(deg2rad($this->latitud)) * cos(deg2rad($otra->latitud)) * sin($deltaLongitud / 2) ** 2;

        return self::RADIO_TIERRA_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
