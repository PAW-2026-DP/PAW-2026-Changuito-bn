<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Decide qué sucursales compiten por una compra: solo las activas cuya
 * ZonaCobertura cubre la distancia hasta la dirección de entrega. Es el
 * mismo criterio con el que se ofrece un pedido a los repartidores.
 */
final class PoliticaCobertura
{
    /**
     * @param Sucursal[] $sucursales
     * @param array<int, ZonaCobertura> $zonasPorSucursalId
     * @return Sucursal[]
     */
    public function sucursalesConCobertura(Coordenada $destino, array $sucursales, array $zonasPorSucursalId): array
    {
        return array_values(array_filter(
            $sucursales,
            function (Sucursal $sucursal) use ($destino, $zonasPorSucursalId): bool {
                if (!$sucursal->activa) {
                    return false;
                }

                $zona = $zonasPorSucursalId[$sucursal->id] ?? null;
                if ($zona === null) {
                    return false;
                }

                return $zona->cubre($sucursal->ubicacion->distanciaEnKm($destino));
            }
        ));
    }
}
