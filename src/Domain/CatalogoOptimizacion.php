<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Foto del catálogo con la que el OptimizadorCompra resuelve una compra:
 * qué sucursales existen, hasta dónde cubren, qué publican y con qué
 * parámetros se cobra el envío. Se arma en la capa de aplicación a partir
 * de los repositorios; el optimizador no consulta persistencia.
 */
final class CatalogoOptimizacion
{
    /** @var array<int, Sucursal> indexado por sucursalId */
    private array $sucursalesPorId = [];

    /** @var array<int, ZonaCobertura> indexado por sucursalId */
    private array $zonasPorSucursalId = [];

    /** @var array<string, ProductoSucursal> indexado por "productoId:sucursalId" */
    private array $publicacionesPorClave = [];

    /** @var array<int, Producto> indexado por productoId */
    private array $productosPorId = [];

    /** @var array<string, ParametroLogistico> indexado por el value del TamanioEnvio */
    private array $parametrosPorTamanio = [];

    /**
     * @param Sucursal[] $sucursales
     * @param ZonaCobertura[] $zonas
     * @param ProductoSucursal[] $publicaciones
     * @param Producto[] $productos
     * @param ParametroLogistico[] $parametrosLogisticos
     */
    public function __construct(
        array $sucursales,
        array $zonas,
        array $publicaciones,
        array $productos,
        array $parametrosLogisticos,
    ) {
        foreach ($sucursales as $sucursal) {
            $this->sucursalesPorId[$sucursal->id] = $sucursal;
        }

        foreach ($zonas as $zona) {
            $this->zonasPorSucursalId[$zona->sucursalId] = $zona;
        }

        foreach ($publicaciones as $publicacion) {
            $this->publicacionesPorClave[self::clave($publicacion->productoId, $publicacion->sucursalId)] = $publicacion;
        }

        foreach ($productos as $producto) {
            $this->productosPorId[$producto->id] = $producto;
        }

        foreach ($parametrosLogisticos as $parametro) {
            $this->parametrosPorTamanio[$parametro->tamanioEnvio->value] = $parametro;
        }
    }

    /** @return Sucursal[] */
    public function sucursales(): array
    {
        return array_values($this->sucursalesPorId);
    }

    /** @return array<int, ZonaCobertura> */
    public function zonasPorSucursalId(): array
    {
        return $this->zonasPorSucursalId;
    }

    public function publicacion(int $productoId, int $sucursalId): ?ProductoSucursal
    {
        return $this->publicacionesPorClave[self::clave($productoId, $sucursalId)] ?? null;
    }

    public function producto(int $productoId): ?Producto
    {
        return $this->productosPorId[$productoId] ?? null;
    }

    public function parametroLogistico(TamanioEnvio $tamanio): ?ParametroLogistico
    {
        return $this->parametrosPorTamanio[$tamanio->value] ?? null;
    }

    private static function clave(int $productoId, int $sucursalId): string
    {
        return $productoId . ':' . $sucursalId;
    }
}
