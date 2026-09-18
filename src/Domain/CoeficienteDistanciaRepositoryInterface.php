<?php

declare(strict_types=1);

namespace App\Domain;

interface CoeficienteDistanciaRepositoryInterface
{
    public function findById(int $id): ?CoeficienteDistancia;

    /**
     * @return CoeficienteDistancia[]
     */
    public function findAll(): array;

    public function save(CoeficienteDistancia $coeficiente): CoeficienteDistancia;

    public function delete(CoeficienteDistancia $coeficiente): void;
}
