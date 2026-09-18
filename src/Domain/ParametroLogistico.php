<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Coeficientes P0/k de la función de costo logístico P(n) = P0 + k·(n-1)^2.5
 * para un tamaño de envío dado. Los ajusta el admin (ver AdminLogisticaController)
 * sin tocar código.
 */
final class ParametroLogistico
{
    public function __construct(
        public readonly TamanioEnvio $tamanioEnvio,
        public readonly Dinero $p0,
        public readonly Dinero $k,
    ) {
    }
}
