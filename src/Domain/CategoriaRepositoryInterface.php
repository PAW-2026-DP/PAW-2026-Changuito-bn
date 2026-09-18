<?php

declare(strict_types=1);

namespace App\Domain;

interface CategoriaRepositoryInterface
{
    public function findById(int $id): ?Categoria;

    public function findByNombre(string $nombre): ?Categoria;

    /**
     * @return Categoria[]
     */
    public function findAll(): array;

    public function save(Categoria $categoria): Categoria;
}
