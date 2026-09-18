<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\AdminCatalogoService;
use App\Application\Command\GuardarProductoCommand;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\TamanioEnvio;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryCategoriaRepository;
use Tests\Fakes\InMemoryProductoRepository;

final class AdminCatalogoServiceTest extends TestCase
{
    private AdminCatalogoService $service;

    protected function setUp(): void
    {
        $this->service = new AdminCatalogoService(
            new InMemoryCategoriaRepository(),
            new InMemoryProductoRepository(),
        );
    }

    public function test_crea_una_categoria(): void
    {
        // Act
        $categoria = $this->service->crearCategoria('Almacén');

        // Assert
        self::assertGreaterThan(0, $categoria->id);
        self::assertSame('Almacén', $categoria->nombre);
    }

    public function test_rechaza_una_categoria_con_nombre_repetido(): void
    {
        // Arrange
        $this->service->crearCategoria('Almacén');

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->service->crearCategoria('Almacén');
    }

    public function test_crea_un_producto_en_una_categoria_existente(): void
    {
        // Arrange
        $categoria = $this->service->crearCategoria('Almacén');

        // Act
        $producto = $this->service->crearProducto($this->comando($categoria->id));

        // Assert
        self::assertGreaterThan(0, $producto->id);
        self::assertSame(TamanioEnvio::PEQUENIO, $producto->tamanioEnvio);
    }

    public function test_rechaza_un_producto_de_una_categoria_inexistente(): void
    {
        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->crearProducto($this->comando(999));
    }

    public function test_busca_productos_por_nombre(): void
    {
        // Arrange
        $categoria = $this->service->crearCategoria('Almacén');
        $this->service->crearProducto($this->comando($categoria->id, 'Fideos'));
        $this->service->crearProducto($this->comando($categoria->id, 'Arroz'));

        // Act
        $encontrados = $this->service->buscarProductos('fide', null);

        // Assert
        self::assertCount(1, $encontrados);
        self::assertSame('Fideos', $encontrados[0]->nombre);
    }

    public function test_actualiza_un_producto_existente(): void
    {
        // Arrange
        $categoria = $this->service->crearCategoria('Almacén');
        $producto = $this->service->crearProducto($this->comando($categoria->id));

        // Act
        $actualizado = $this->service->actualizarProducto(new GuardarProductoCommand(
            'Fideos largos',
            'Nueva descripción',
            $categoria->id,
            TamanioEnvio::MEDIANO,
            $producto->id,
        ));

        // Assert
        self::assertSame('Fideos largos', $actualizado->nombre);
        self::assertSame(TamanioEnvio::MEDIANO, $actualizado->tamanioEnvio);
    }

    public function test_rechaza_actualizar_un_producto_inexistente(): void
    {
        // Arrange
        $categoria = $this->service->crearCategoria('Almacén');

        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->actualizarProducto($this->comando($categoria->id, 'Fideos', 999));
    }

    private function comando(int $categoriaId, string $nombre = 'Fideos', int $id = 0): GuardarProductoCommand
    {
        return new GuardarProductoCommand($nombre, 'Descripción', $categoriaId, TamanioEnvio::PEQUENIO, $id);
    }
}
