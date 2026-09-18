<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Decide a qué repartidores se les ofrece un pedido. Como la geolocalización
 * en tiempo real está fuera de alcance, se usa la ubicación que el propio
 * repartidor declaró al iniciar su turno.
 *
 * El radio se exige contra todos los puntos de retiro y contra la entrega:
 * un repartidor cerca de una sucursal pero lejos del resto del recorrido
 * no puede completar el pedido.
 */
final class ElegibilidadRepartidor
{
    /**
     * @param Repartidor[] $repartidores
     * @param Coordenada[] $puntosDeRetiro una por cada sucursal involucrada
     * @return Repartidor[]
     */
    public function elegibles(array $repartidores, array $puntosDeRetiro, Coordenada $destino, float $radioOfertaKm): array
    {
        if ($radioOfertaKm <= 0.0) {
            throw new ValidacionException('El radio de oferta debe ser positivo.');
        }

        return array_values(array_filter(
            $repartidores,
            fn (Repartidor $repartidor): bool => $this->alcanza($repartidor, [...$puntosDeRetiro, $destino], $radioOfertaKm),
        ));
    }

    /**
     * @param Coordenada[] $puntos
     */
    private function alcanza(Repartidor $repartidor, array $puntos, float $radioOfertaKm): bool
    {
        if (!$repartidor->disponible || $repartidor->ubicacion === null) {
            return false;
        }

        foreach ($puntos as $punto) {
            if ($repartidor->ubicacion->distanciaEnKm($punto) > $radioOfertaKm) {
                return false;
            }
        }

        return true;
    }
}
