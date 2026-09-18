<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Producto;
use App\Domain\ProductoRepositoryInterface;

final class InMemoryProductoRepository implements ProductoRepositoryInterface
{
    /** @var array<int, Producto> */
    private array $productos = [];

    private int $proximoId = 1;

    public function findById(int $id): ?Producto
    {
        return $this->productos[$id] ?? null;
    }

    public function buscar(?string $texto, ?int $categoriaId): array
    {
        return array_values(array_filter($this->productos, static function (Producto $producto) use ($texto, $categoriaId): bool {
            $coincideTexto = $texto === null || $texto === ''
                || stripos($producto->nombre, $texto) !== false;

            return $coincideTexto && ($categoriaId === null || $producto->categoriaId === $categoriaId);
        }));
    }

    public function save(Producto $producto): Producto
    {
        if ($producto->id === 0) {
            $producto = new Producto(
                $this->proximoId++,
                $producto->nombre,
                $producto->descripcion,
                $producto->categoriaId,
                $producto->tamanioEnvio,
            );
        }

        $this->productos[$producto->id] = $producto;

        return $producto;
    }
}
