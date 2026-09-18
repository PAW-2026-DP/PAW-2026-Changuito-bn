<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Command\GuardarProductoCommand;
use App\Domain\Categoria;
use App\Domain\CategoriaRepositoryInterface;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\Producto;
use App\Domain\ProductoRepositoryInterface;

/**
 * Mantenimiento del catálogo maestro normalizado. Es el único lugar donde
 * nacen los productos: el supermercado solo publica productos que ya
 * existen acá (ver ProductoSucursal).
 */
final class AdminCatalogoService
{
    public function __construct(
        private readonly CategoriaRepositoryInterface $categorias,
        private readonly ProductoRepositoryInterface $productos,
    ) {
    }

    /**
     * @return Categoria[]
     */
    public function listarCategorias(): array
    {
        return $this->categorias->findAll();
    }

    public function crearCategoria(string $nombre): Categoria
    {
        if ($this->categorias->findByNombre($nombre) !== null) {
            throw new ValidacionException('Ya existe una categoría con ese nombre.');
        }

        return $this->categorias->save(new Categoria(0, $nombre));
    }

    /**
     * @return Producto[]
     */
    public function buscarProductos(?string $texto, ?int $categoriaId): array
    {
        return $this->productos->buscar($texto, $categoriaId);
    }

    public function crearProducto(GuardarProductoCommand $command): Producto
    {
        $this->exigirCategoria($command->categoriaId);

        return $this->productos->save(new Producto(
            0,
            $command->nombre,
            $command->descripcion,
            $command->categoriaId,
            $command->tamanioEnvio,
        ));
    }

    public function actualizarProducto(GuardarProductoCommand $command): Producto
    {
        if ($this->productos->findById($command->id) === null) {
            throw new EntidadNoEncontradaException('El producto no existe.');
        }

        $this->exigirCategoria($command->categoriaId);

        return $this->productos->save(new Producto(
            $command->id,
            $command->nombre,
            $command->descripcion,
            $command->categoriaId,
            $command->tamanioEnvio,
        ));
    }

    private function exigirCategoria(int $categoriaId): void
    {
        if ($this->categorias->findById($categoriaId) === null) {
            throw new EntidadNoEncontradaException('La categoría no existe.');
        }
    }
}
