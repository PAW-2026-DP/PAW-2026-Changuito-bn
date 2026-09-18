<?php

declare(strict_types=1);

namespace App\Domain;

interface SupermercadoRepositoryInterface
{
    public function findByUsuarioId(int $usuarioId): ?Supermercado;

    /**
     * @return Supermercado[]
     */
    public function findAll(): array;

    public function save(Supermercado $supermercado): Supermercado;
}
