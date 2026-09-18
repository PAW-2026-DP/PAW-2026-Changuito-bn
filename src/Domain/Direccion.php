<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Dirección de entrega de un Cliente. Sin ella no hay cobertura,
 * optimización ni destino de entrega posibles.
 */
final class Direccion
{
    public bool $esDefault;

    public function __construct(
        public readonly int $id,
        public readonly int $clienteId,
        public readonly string $calle,
        public readonly string $numero,
        public readonly string $ciudad,
        public readonly string $cp,
        public readonly Coordenada $ubicacion,
        bool $esDefault = false,
    ) {
        $this->esDefault = $esDefault;
    }

    public function marcarComoPredeterminada(): void
    {
        $this->esDefault = true;
    }

    public function quitarPredeterminada(): void
    {
        $this->esDefault = false;
    }
}
