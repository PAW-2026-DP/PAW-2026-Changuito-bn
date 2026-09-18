<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\CoeficienteDistancia;
use App\Domain\CoeficienteDistanciaRepositoryInterface;
use App\Domain\Dinero;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\ParametroLogistico;
use App\Domain\ParametroLogisticoRepositoryInterface;
use App\Domain\ParametroReparto;
use App\Domain\ParametroRepartoRepositoryInterface;
use App\Domain\TamanioEnvio;

/**
 * Ajuste de los costos de envío y del radio de oferta sin tocar código:
 * los coeficientes de P(n) = p0 + k·(n-1)^2.5 y los tramos por distancia
 * son datos, no constantes.
 */
final class AdminLogisticaService
{
    public function __construct(
        private readonly ParametroLogisticoRepositoryInterface $parametros,
        private readonly CoeficienteDistanciaRepositoryInterface $coeficientes,
        private readonly ParametroRepartoRepositoryInterface $reparto,
    ) {
    }

    /**
     * @return ParametroLogistico[]
     */
    public function listarParametros(): array
    {
        return $this->parametros->findAll();
    }

    public function definirParametro(TamanioEnvio $tamanio, Dinero $p0, Dinero $k): ParametroLogistico
    {
        return $this->parametros->save(new ParametroLogistico($tamanio, $p0, $k));
    }

    /**
     * @return CoeficienteDistancia[]
     */
    public function listarCoeficientes(): array
    {
        return $this->coeficientes->findAll();
    }

    public function crearCoeficiente(
        TamanioEnvio $tamanio,
        float $distanciaMinKm,
        float $distanciaMaxKm,
        float $coeficiente,
    ): CoeficienteDistancia {
        return $this->coeficientes->save(
            new CoeficienteDistancia(0, $tamanio, $distanciaMinKm, $distanciaMaxKm, $coeficiente),
        );
    }

    public function actualizarCoeficiente(
        int $id,
        TamanioEnvio $tamanio,
        float $distanciaMinKm,
        float $distanciaMaxKm,
        float $coeficiente,
    ): CoeficienteDistancia {
        $this->obtenerCoeficiente($id);

        return $this->coeficientes->save(
            new CoeficienteDistancia($id, $tamanio, $distanciaMinKm, $distanciaMaxKm, $coeficiente),
        );
    }

    public function eliminarCoeficiente(int $id): void
    {
        $this->coeficientes->delete($this->obtenerCoeficiente($id));
    }

    public function radioDeOferta(): ParametroReparto
    {
        return $this->reparto->obtener();
    }

    public function definirRadioDeOferta(float $radioOfertaKm): ParametroReparto
    {
        return $this->reparto->guardar(new ParametroReparto($radioOfertaKm));
    }

    private function obtenerCoeficiente(int $id): CoeficienteDistancia
    {
        $coeficiente = $this->coeficientes->findById($id);

        if ($coeficiente === null) {
            throw new EntidadNoEncontradaException('El coeficiente no existe.');
        }

        return $coeficiente;
    }
}
