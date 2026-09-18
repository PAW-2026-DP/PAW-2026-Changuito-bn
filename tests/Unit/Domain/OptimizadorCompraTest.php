<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\CalculadoraCostoLogistico;
use App\Domain\CatalogoOptimizacion;
use App\Domain\Coordenada;
use App\Domain\Dinero;
use App\Domain\ItemLista;
use App\Domain\OptimizadorCompra;
use App\Domain\ParametroLogistico;
use App\Domain\PoliticaCobertura;
use App\Domain\Producto;
use App\Domain\ProductoSucursal;
use App\Domain\Sucursal;
use App\Domain\TamanioEnvio;
use App\Domain\ZonaCobertura;
use PHPUnit\Framework\TestCase;

final class OptimizadorCompraTest extends TestCase
{
    private const CATEGORIA_ID = 1;

    private Coordenada $destino;

    protected function setUp(): void
    {
        $this->destino = new Coordenada(-34.6037, -58.3816);
    }

    public function test_compra_todo_en_la_unica_sucursal_con_cobertura(): void
    {
        // Arrange
        $catalogo = new CatalogoOptimizacion(
            sucursales: [$this->sucursal(1, 0.01)],
            zonas: [new ZonaCobertura(1, 0.0, 10.0)],
            publicaciones: [$this->publicacion(10, 1, 1000), $this->publicacion(11, 1, 2000)],
            productos: [$this->producto(10), $this->producto(11)],
            parametrosLogisticos: [$this->parametro(TamanioEnvio::PEQUENIO, 50000, 10000)],
        );

        // Act
        $resultado = $this->optimizador()->calcular(
            $catalogo,
            [new ItemLista(10, 2), new ItemLista(11, 1)],
            $this->destino,
        );

        // Assert
        $recomendado = $resultado->recomendado;
        self::assertSame([1], $recomendado->sucursalesInvolucradas());
        self::assertSame(4000, $recomendado->subtotalProductos->centavos);
        self::assertSame(50000, $recomendado->costoLogistico->centavos);
        self::assertSame(54000, $recomendado->total->centavos);
        self::assertSame([], $recomendado->faltantes);
    }

    public function test_descarta_las_sucursales_fuera_de_la_zona_de_cobertura(): void
    {
        // Arrange — la sucursal 2 es más barata pero está fuera del radio máximo
        $catalogo = new CatalogoOptimizacion(
            sucursales: [$this->sucursal(1, 0.01), $this->sucursal(2, 0.9)],
            zonas: [new ZonaCobertura(1, 0.0, 10.0), new ZonaCobertura(2, 0.0, 1.0)],
            publicaciones: [$this->publicacion(10, 1, 1000), $this->publicacion(10, 2, 500)],
            productos: [$this->producto(10)],
            parametrosLogisticos: [$this->parametro(TamanioEnvio::PEQUENIO, 50000, 10000)],
        );

        // Act
        $resultado = $this->optimizador()->calcular(
            $catalogo,
            [new ItemLista(10, 1)],
            $this->destino,
        );

        // Assert
        self::assertSame([1], $resultado->recomendado->sucursalesInvolucradas());
        self::assertSame(1000, $resultado->recomendado->subtotalProductos->centavos);
    }

    public function test_reporta_como_faltante_el_producto_sin_stock_en_ninguna_sucursal(): void
    {
        // Arrange
        $catalogo = new CatalogoOptimizacion(
            sucursales: [$this->sucursal(1, 0.01)],
            zonas: [new ZonaCobertura(1, 0.0, 10.0)],
            publicaciones: [$this->publicacion(10, 1, 1000), $this->publicacion(11, 1, 2000, stock: 0)],
            productos: [$this->producto(10), $this->producto(11)],
            parametrosLogisticos: [$this->parametro(TamanioEnvio::PEQUENIO, 50000, 10000)],
        );

        // Act
        $resultado = $this->optimizador()->calcular(
            $catalogo,
            [new ItemLista(10, 1), new ItemLista(11, 1)],
            $this->destino,
        );

        // Assert
        self::assertSame([11], $resultado->recomendado->faltantes);
        self::assertSame(1000, $resultado->recomendado->subtotalProductos->centavos);
    }

    public function test_recomienda_dividir_la_compra_cuando_el_ahorro_supera_el_recargo(): void
    {
        // Arrange — dividir ahorra $600 en productos y el recargo P(2)-P(1) es $100
        $catalogo = new CatalogoOptimizacion(
            sucursales: [$this->sucursal(1, 0.01), $this->sucursal(2, 0.02)],
            zonas: [new ZonaCobertura(1, 0.0, 10.0), new ZonaCobertura(2, 0.0, 10.0)],
            publicaciones: [
                $this->publicacion(10, 1, 1000),
                $this->publicacion(11, 1, 90000),
                $this->publicacion(10, 2, 90000),
                $this->publicacion(11, 2, 30000),
            ],
            productos: [$this->producto(10), $this->producto(11)],
            parametrosLogisticos: [$this->parametro(TamanioEnvio::PEQUENIO, 50000, 10000)],
        );

        // Act
        $resultado = $this->optimizador()->calcular(
            $catalogo,
            [new ItemLista(10, 1), new ItemLista(11, 1)],
            $this->destino,
        );

        // Assert
        self::assertSame([1, 2], $resultado->recomendado->sucursalesInvolucradas());
        self::assertSame(31000, $resultado->recomendado->subtotalProductos->centavos);
        self::assertSame(60000, $resultado->recomendado->costoLogistico->centavos);
        self::assertTrue($resultado->ahorroNeto->esMayorQue(Dinero::centavos(0)));
    }

    public function test_prefiere_un_solo_comercio_cuando_el_recargo_se_come_el_ahorro(): void
    {
        // Arrange — dividir ahorra solo $10 en productos y el recargo P(2)-P(1) es $100
        $catalogo = new CatalogoOptimizacion(
            sucursales: [$this->sucursal(1, 0.01), $this->sucursal(2, 0.02)],
            zonas: [new ZonaCobertura(1, 0.0, 10.0), new ZonaCobertura(2, 0.0, 10.0)],
            publicaciones: [
                $this->publicacion(10, 1, 1000),
                $this->publicacion(11, 1, 2000),
                $this->publicacion(10, 2, 1000),
                $this->publicacion(11, 2, 1000),
            ],
            productos: [$this->producto(10), $this->producto(11)],
            parametrosLogisticos: [$this->parametro(TamanioEnvio::PEQUENIO, 50000, 10000)],
        );

        // Act
        $resultado = $this->optimizador()->calcular(
            $catalogo,
            [new ItemLista(10, 1), new ItemLista(11, 1)],
            $this->destino,
        );

        // Assert
        self::assertSame([2], $resultado->recomendado->sucursalesInvolucradas());
        self::assertSame(2000, $resultado->recomendado->subtotalProductos->centavos);
        self::assertSame(50000, $resultado->recomendado->costoLogistico->centavos);
        self::assertSame(0, $resultado->ahorroNeto->centavos);
    }

    public function test_usa_el_parametro_del_tamanio_de_envio_mas_grande_de_la_compra(): void
    {
        // Arrange
        $catalogo = new CatalogoOptimizacion(
            sucursales: [$this->sucursal(1, 0.01)],
            zonas: [new ZonaCobertura(1, 0.0, 10.0)],
            publicaciones: [$this->publicacion(10, 1, 1000), $this->publicacion(12, 1, 1000)],
            productos: [$this->producto(10), $this->producto(12, TamanioEnvio::GRANDE)],
            parametrosLogisticos: [
                $this->parametro(TamanioEnvio::PEQUENIO, 50000, 10000),
                $this->parametro(TamanioEnvio::GRANDE, 120000, 30000),
            ],
        );

        // Act
        $resultado = $this->optimizador()->calcular(
            $catalogo,
            [new ItemLista(10, 1), new ItemLista(12, 1)],
            $this->destino,
        );

        // Assert
        self::assertSame(120000, $resultado->recomendado->costoLogistico->centavos);
    }

    public function test_devuelve_un_escenario_vacio_cuando_ninguna_sucursal_tiene_cobertura(): void
    {
        // Arrange
        $catalogo = new CatalogoOptimizacion(
            sucursales: [$this->sucursal(1, 0.9)],
            zonas: [new ZonaCobertura(1, 0.0, 1.0)],
            publicaciones: [$this->publicacion(10, 1, 1000)],
            productos: [$this->producto(10)],
            parametrosLogisticos: [$this->parametro(TamanioEnvio::PEQUENIO, 50000, 10000)],
        );

        // Act
        $resultado = $this->optimizador()->calcular(
            $catalogo,
            [new ItemLista(10, 1)],
            $this->destino,
        );

        // Assert
        self::assertSame([], $resultado->recomendado->sucursalesInvolucradas());
        self::assertSame([10], $resultado->recomendado->faltantes);
        self::assertSame(0, $resultado->recomendado->total->centavos);
    }

    private function optimizador(): OptimizadorCompra
    {
        return new OptimizadorCompra(new PoliticaCobertura(), new CalculadoraCostoLogistico());
    }

    private function sucursal(int $id, float $offsetGrados): Sucursal
    {
        return new Sucursal(
            $id,
            supermercadoId: 1,
            nombre: "Sucursal {$id}",
            direccion: 'Calle 1',
            ubicacion: new Coordenada(-34.6037 + $offsetGrados, -58.3816),
            activa: true,
        );
    }

    private function producto(int $id, TamanioEnvio $tamanio = TamanioEnvio::PEQUENIO): Producto
    {
        return new Producto($id, "Producto {$id}", 'Descripción', self::CATEGORIA_ID, $tamanio);
    }

    private function publicacion(int $productoId, int $sucursalId, int $centavos, int $stock = 10): ProductoSucursal
    {
        return new ProductoSucursal(
            $productoId,
            $sucursalId,
            Dinero::centavos($centavos),
            $stock,
            new \DateTimeImmutable('2026-09-18 10:00:00'),
        );
    }

    private function parametro(TamanioEnvio $tamanio, int $p0, int $k): ParametroLogistico
    {
        return new ParametroLogistico($tamanio, Dinero::centavos($p0), Dinero::centavos($k));
    }
}
