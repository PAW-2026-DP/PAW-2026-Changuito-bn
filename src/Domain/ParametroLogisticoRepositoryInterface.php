<?php

declare(strict_types=1);

namespace App\Domain;

interface ParametroLogisticoRepositoryInterface
{
    public function findByTamanio(TamanioEnvio $tamanio): ?ParametroLogistico;

    /**
     * @return ParametroLogistico[]
     */
    public function findAll(): array;

    public function save(ParametroLogistico $parametro): ParametroLogistico;
}
