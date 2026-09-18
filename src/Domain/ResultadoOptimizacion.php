<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Salida del OptimizadorCompra: los escenarios evaluados, cuál conviene y
 * cuánto se ahorra respecto de comprar todo en un solo comercio, ya neto
 * del recargo logístico por dividir la compra.
 */
final class ResultadoOptimizacion
{
    /**
     * @param EscenarioCompra[] $escenarios
     */
    public function __construct(
        public readonly array $escenarios,
        public readonly EscenarioCompra $recomendado,
        public readonly Dinero $ahorroNeto,
    ) {
    }
}
