<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Tramo de distancia con un coeficiente de ajuste propio, configurable por
 * el admin por tamaño de envío (ver AdminLogisticaController).
 */
final class CoeficienteDistancia
{
    public function __construct(
        public readonly TamanioEnvio $tamanioEnvio,
        public readonly float $distanciaMinKm,
        public readonly float $distanciaMaxKm,
        public readonly float $coeficiente,
    ) {
        if ($distanciaMinKm >= $distanciaMaxKm) {
            throw new ValidacionException('La distancia mínima debe ser menor a la máxima.');
        }

        if ($coeficiente <= 0.0) {
            throw new ValidacionException('El coeficiente debe ser positivo.');
        }
    }

    public function cubreDistancia(float $distanciaKm): bool
    {
        return $distanciaKm >= $this->distanciaMinKm && $distanciaKm < $this->distanciaMaxKm;
    }
}
