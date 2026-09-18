<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Categoria;
use App\Domain\CategoriaRepositoryInterface;

final class InMemoryCategoriaRepository implements CategoriaRepositoryInterface
{
    /** @var array<int, Categoria> */
    private array $categorias = [];

    private int $proximoId = 1;

    public function findById(int $id): ?Categoria
    {
        return $this->categorias[$id] ?? null;
    }

    public function findByNombre(string $nombre): ?Categoria
    {
        foreach ($this->categorias as $categoria) {
            if ($categoria->nombre === $nombre) {
                return $categoria;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->categorias);
    }

    public function save(Categoria $categoria): Categoria
    {
        if ($categoria->id === 0) {
            $categoria = new Categoria($this->proximoId++, $categoria->nombre);
        }

        $this->categorias[$categoria->id] = $categoria;

        return $categoria;
    }
}
