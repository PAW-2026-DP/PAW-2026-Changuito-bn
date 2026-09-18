<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\ParametroReparto;
use App\Domain\ParametroRepartoRepositoryInterface;

final class InMemoryParametroRepartoRepository implements ParametroRepartoRepositoryInterface
{
    private ParametroReparto $parametro;

    public function __construct(float $radioOfertaKm = 5.0)
    {
        $this->parametro = new ParametroReparto($radioOfertaKm);
    }

    public function obtener(): ParametroReparto
    {
        return $this->parametro;
    }

    public function guardar(ParametroReparto $parametro): ParametroReparto
    {
        return $this->parametro = $parametro;
    }
}
