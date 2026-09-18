<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Persistencia de las direcciones de entrega.
 *
 * Las búsquedas por id van siempre acotadas al cliente dueño: así un id
 * ajeno no se distingue de uno inexistente y el service responde 404 sin
 * revelar que el recurso existe.
 */
interface DireccionRepositoryInterface
{
    public function findByIdDelCliente(int $id, int $clienteId): ?Direccion;

    /**
     * @return Direccion[]
     */
    public function findByCliente(int $clienteId): array;

    public function save(Direccion $direccion): Direccion;

    public function delete(Direccion $direccion): void;
}
