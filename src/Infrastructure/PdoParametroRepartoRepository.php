<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\ParametroReparto;
use App\Domain\ParametroRepartoRepositoryInterface;
use PDO;

/**
 * Fila única de configuración (id = 1), creada por la migración: por eso
 * `obtener()` siempre devuelve un valor y no hace falta un caso "no existe".
 */
final class PdoParametroRepartoRepository implements ParametroRepartoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function obtener(): ParametroReparto
    {
        $radio = $this->pdo->query('SELECT radio_oferta_km FROM parametros_reparto WHERE id = 1')->fetchColumn();

        return new ParametroReparto((float) $radio);
    }

    public function guardar(ParametroReparto $parametro): ParametroReparto
    {
        $sentencia = $this->pdo->prepare('UPDATE parametros_reparto SET radio_oferta_km = :radio WHERE id = 1');
        $sentencia->execute(['radio' => $parametro->radioOfertaKm]);

        return $parametro;
    }
}
