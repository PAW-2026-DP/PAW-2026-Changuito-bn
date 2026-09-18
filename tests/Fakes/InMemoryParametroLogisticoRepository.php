<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\ParametroLogistico;
use App\Domain\ParametroLogisticoRepositoryInterface;
use App\Domain\TamanioEnvio;

final class InMemoryParametroLogisticoRepository implements ParametroLogisticoRepositoryInterface
{
    /** @var array<string, ParametroLogistico> */
    private array $parametros = [];

    public function findByTamanio(TamanioEnvio $tamanio): ?ParametroLogistico
    {
        return $this->parametros[$tamanio->value] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->parametros);
    }

    public function save(ParametroLogistico $parametro): ParametroLogistico
    {
        $this->parametros[$parametro->tamanioEnvio->value] = $parametro;

        return $parametro;
    }
}
