<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Motor de optimización de la compra: arma los escenarios posibles para una
 * lista y una dirección, y recomienda el que menos sale ya contando el
 * recargo por dividir la compra entre varios comercios.
 *
 * Los escenarios evaluados son el de "cada producto donde esté más barato"
 * y uno por cada sucursal con cobertura (comprar todo ahí). Alcanzan para
 * decidir: cualquier combinación intermedia cuesta más en productos que el
 * primero y más en logística que el segundo.
 */
final class OptimizadorCompra
{
    public function __construct(
        private readonly PoliticaCobertura $politicaCobertura,
        private readonly CalculadoraCostoLogistico $calculadora,
    ) {
    }

    /**
     * @param ItemLista[] $items
     */
    public function calcular(CatalogoOptimizacion $catalogo, array $items, Coordenada $destino): ResultadoOptimizacion
    {
        $sucursalesConCobertura = $this->politicaCobertura->sucursalesConCobertura(
            $destino,
            $catalogo->sucursales(),
            $catalogo->zonasPorSucursalId(),
        );

        $escenarios = [$this->armarEscenario($catalogo, $items, $sucursalesConCobertura)];

        foreach ($sucursalesConCobertura as $sucursal) {
            $escenarios[] = $this->armarEscenario($catalogo, $items, [$sucursal]);
        }

        $recomendado = $this->mejorEscenario($escenarios);
        $unicoComercio = $this->mejorEscenario(array_values(array_filter(
            $escenarios,
            static fn (EscenarioCompra $escenario): bool => $escenario->cantidadComercios() === 1,
        )));

        if ($recomendado === null) {
            throw new ValidacionException('No se pudo armar ningún escenario de compra.');
        }

        return new ResultadoOptimizacion($escenarios, $recomendado, $this->ahorro($recomendado, $unicoComercio));
    }

    /**
     * @param ItemLista[] $items
     * @param Sucursal[] $sucursalesCandidatas
     */
    private function armarEscenario(CatalogoOptimizacion $catalogo, array $items, array $sucursalesCandidatas): EscenarioCompra
    {
        $asignaciones = [];
        $faltantes = [];

        foreach ($items as $item) {
            $asignacion = $this->asignarMasBarata($catalogo, $item, $sucursalesCandidatas);

            if ($asignacion === null) {
                $faltantes[] = $item->productoId;

                continue;
            }

            $asignaciones[] = $asignacion;
        }

        return new EscenarioCompra($asignaciones, $faltantes, $this->costoLogistico($catalogo, $asignaciones));
    }

    /**
     * @param Sucursal[] $sucursalesCandidatas
     */
    private function asignarMasBarata(CatalogoOptimizacion $catalogo, ItemLista $item, array $sucursalesCandidatas): ?AsignacionItem
    {
        $mejor = null;

        foreach ($sucursalesCandidatas as $sucursal) {
            $publicacion = $catalogo->publicacion($item->productoId, $sucursal->id);

            if ($publicacion === null || $publicacion->stock < $item->cantidad) {
                continue;
            }

            if ($mejor !== null && !$mejor->precio->esMayorQue($publicacion->precio)) {
                continue;
            }

            $mejor = $publicacion;
        }

        if ($mejor === null) {
            return null;
        }

        return new AsignacionItem($item->productoId, $mejor->sucursalId, $item->cantidad, $mejor->precio);
    }

    /**
     * El envío se cobra con los parámetros del tamaño más grande de la
     * compra: el transporte que hace falta lo fija el item más voluminoso.
     *
     * @param AsignacionItem[] $asignaciones
     */
    private function costoLogistico(CatalogoOptimizacion $catalogo, array $asignaciones): Dinero
    {
        if ($asignaciones === []) {
            return Dinero::centavos(0);
        }

        $tamanio = TamanioEnvio::PEQUENIO;
        foreach ($asignaciones as $asignacion) {
            $producto = $catalogo->producto($asignacion->productoId);

            if ($producto === null) {
                throw new ValidacionException("El producto {$asignacion->productoId} no está en el catálogo.");
            }

            if ($this->volumen($producto->tamanioEnvio) > $this->volumen($tamanio)) {
                $tamanio = $producto->tamanioEnvio;
            }
        }

        $parametro = $catalogo->parametroLogistico($tamanio);

        if ($parametro === null) {
            throw new ValidacionException("Falta el parámetro logístico del tamaño {$tamanio->value}.");
        }

        $comercios = count(array_unique(array_map(
            static fn (AsignacionItem $asignacion): int => $asignacion->sucursalId,
            $asignaciones,
        )));

        return $this->calculadora->costoEnvio($parametro, $comercios);
    }

    private function volumen(TamanioEnvio $tamanio): int
    {
        return match ($tamanio) {
            TamanioEnvio::PEQUENIO => 1,
            TamanioEnvio::MEDIANO => 2,
            TamanioEnvio::GRANDE => 3,
        };
    }

    /**
     * Gana el escenario que deja menos productos sin cubrir; entre los que
     * cubren lo mismo, el más barato. Sin esta prioridad, un comercio que
     * tiene la mitad de la lista ganaría por tener un total más chico.
     *
     * @param EscenarioCompra[] $escenarios
     */
    private function mejorEscenario(array $escenarios): ?EscenarioCompra
    {
        $mejor = null;

        foreach ($escenarios as $escenario) {
            if ($mejor === null) {
                $mejor = $escenario;

                continue;
            }

            if (count($escenario->faltantes) < count($mejor->faltantes)) {
                $mejor = $escenario;

                continue;
            }

            if (count($escenario->faltantes) === count($mejor->faltantes)
                && $mejor->total->esMayorQue($escenario->total)
            ) {
                $mejor = $escenario;
            }
        }

        return $mejor;
    }

    private function ahorro(EscenarioCompra $recomendado, ?EscenarioCompra $unicoComercio): Dinero
    {
        if ($unicoComercio === null || !$unicoComercio->total->esMayorQue($recomendado->total)) {
            return Dinero::centavos(0);
        }

        return $unicoComercio->total->restar($recomendado->total);
    }
}
