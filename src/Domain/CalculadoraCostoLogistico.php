<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Costo de envío al dividir una compra entre varios comercios:
 * P(n) = P0 + k·(n-1)^2.5. El exponente 2.5 desincentiva dividir la compra
 * en demasiados puntos de origen sin reglas adicionales por distancia.
 */
final class CalculadoraCostoLogistico
{
    public function costoEnvio(ParametroLogistico $parametro, int $cantidadComercios): Dinero
    {
        if ($cantidadComercios < 1) {
            throw new ValidacionException('La cantidad de comercios debe ser al menos 1.');
        }

        $factor = ($cantidadComercios - 1) ** 2.5;

        return $parametro->p0->sumar($parametro->k->multiplicarPorFactor($factor));
    }

    public function recargo(Dinero $costoElegido, Dinero $costoRecomendado): Dinero
    {
        return $costoElegido->restar($costoRecomendado);
    }
}
