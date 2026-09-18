<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\AdminLogisticaService;
use App\Domain\CoeficienteDistancia;
use App\Domain\Dinero;
use App\Domain\Exception\ValidacionException;
use App\Domain\ParametroLogistico;
use App\Domain\TamanioEnvio;
use App\Http\Request;
use App\Http\Response;

/**
 * Ajuste de los parámetros de costo logístico y del radio de reparto.
 * Los montos viajan en centavos, igual que se guardan (ver Dinero).
 */
final class AdminLogisticaController
{
    use CuerpoDeRequestTrait;

    public function __construct(private readonly AdminLogisticaService $logistica)
    {
    }

    public function listarParametros(Request $request): Response
    {
        return Response::json([
            'parametros' => array_map(self::parametroComoJson(...), $this->logistica->listarParametros()),
        ]);
    }

    public function definirParametro(Request $request): Response
    {
        $parametro = $this->logistica->definirParametro(
            $this->tamanioDeRuta($request),
            Dinero::centavos($this->enteroRequerido($request, 'p0Centavos')),
            Dinero::centavos($this->enteroRequerido($request, 'kCentavos')),
        );

        return Response::json(self::parametroComoJson($parametro));
    }

    public function listarCoeficientes(Request $request): Response
    {
        return Response::json([
            'coeficientes' => array_map(self::coeficienteComoJson(...), $this->logistica->listarCoeficientes()),
        ]);
    }

    public function crearCoeficiente(Request $request): Response
    {
        $coeficiente = $this->logistica->crearCoeficiente(
            $this->tamanioDelCuerpo($request),
            $this->decimalRequerido($request, 'distanciaMinKm'),
            $this->decimalRequerido($request, 'distanciaMaxKm'),
            $this->decimalRequerido($request, 'coeficiente'),
        );

        return Response::json(self::coeficienteComoJson($coeficiente), 201);
    }

    public function actualizarCoeficiente(Request $request): Response
    {
        $coeficiente = $this->logistica->actualizarCoeficiente(
            $this->idDeRuta($request, 'id'),
            $this->tamanioDelCuerpo($request),
            $this->decimalRequerido($request, 'distanciaMinKm'),
            $this->decimalRequerido($request, 'distanciaMaxKm'),
            $this->decimalRequerido($request, 'coeficiente'),
        );

        return Response::json(self::coeficienteComoJson($coeficiente));
    }

    public function eliminarCoeficiente(Request $request): Response
    {
        $this->logistica->eliminarCoeficiente($this->idDeRuta($request, 'id'));

        return Response::noContent();
    }

    public function verParametrosDeReparto(Request $request): Response
    {
        return Response::json(['radioOfertaKm' => $this->logistica->radioDeOferta()->radioOfertaKm]);
    }

    public function definirParametrosDeReparto(Request $request): Response
    {
        $parametro = $this->logistica->definirRadioDeOferta($this->decimalRequerido($request, 'radioOfertaKm'));

        return Response::json(['radioOfertaKm' => $parametro->radioOfertaKm]);
    }

    private function tamanioDeRuta(Request $request): TamanioEnvio
    {
        $valor = $request->routeParam('tamanio') ?? '';

        return TamanioEnvio::tryFrom($valor) ?? throw new ValidacionException("El tamaño de envío {$valor} no existe.");
    }

    private function tamanioDelCuerpo(Request $request): TamanioEnvio
    {
        $valor = $this->textoRequerido($request, 'tamanioEnvio');

        return TamanioEnvio::tryFrom($valor) ?? throw new ValidacionException("El tamaño de envío {$valor} no existe.");
    }

    /**
     * @return array<string, mixed>
     */
    private static function parametroComoJson(ParametroLogistico $parametro): array
    {
        return [
            'tamanioEnvio' => $parametro->tamanioEnvio->value,
            'p0Centavos' => $parametro->p0->centavos,
            'kCentavos' => $parametro->k->centavos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function coeficienteComoJson(CoeficienteDistancia $coeficiente): array
    {
        return [
            'id' => $coeficiente->id,
            'tamanioEnvio' => $coeficiente->tamanioEnvio->value,
            'distanciaMinKm' => $coeficiente->distanciaMinKm,
            'distanciaMaxKm' => $coeficiente->distanciaMaxKm,
            'coeficiente' => $coeficiente->coeficiente,
        ];
    }
}
