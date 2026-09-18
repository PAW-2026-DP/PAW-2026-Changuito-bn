<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\CoeficienteDistancia;
use App\Domain\CoeficienteDistanciaRepositoryInterface;

final class InMemoryCoeficienteDistanciaRepository implements CoeficienteDistanciaRepositoryInterface
{
    /** @var array<int, CoeficienteDistancia> */
    private array $coeficientes = [];

    private int $proximoId = 1;

    public function findById(int $id): ?CoeficienteDistancia
    {
        return $this->coeficientes[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->coeficientes);
    }

    public function save(CoeficienteDistancia $coeficiente): CoeficienteDistancia
    {
        if ($coeficiente->id === 0) {
            $coeficiente = new CoeficienteDistancia(
                $this->proximoId++,
                $coeficiente->tamanioEnvio,
                $coeficiente->distanciaMinKm,
                $coeficiente->distanciaMaxKm,
                $coeficiente->coeficiente,
            );
        }

        $this->coeficientes[$coeficiente->id] = $coeficiente;

        return $coeficiente;
    }

    public function delete(CoeficienteDistancia $coeficiente): void
    {
        unset($this->coeficientes[$coeficiente->id]);
    }
}
