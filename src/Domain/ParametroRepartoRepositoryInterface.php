<?php

declare(strict_types=1);

namespace App\Domain;

interface ParametroRepartoRepositoryInterface
{
    public function obtener(): ParametroReparto;

    public function guardar(ParametroReparto $parametro): ParametroReparto;
}
