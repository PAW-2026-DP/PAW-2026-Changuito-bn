<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Dinero;
use App\Domain\ParametroLogistico;
use App\Domain\ParametroLogisticoRepositoryInterface;
use App\Domain\TamanioEnvio;
use PDO;

final class PdoParametroLogisticoRepository implements ParametroLogisticoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByTamanio(TamanioEnvio $tamanio): ?ParametroLogistico
    {
        $sentencia = $this->pdo->prepare(
            'SELECT tamanio_envio, p0, k FROM parametros_logisticos WHERE tamanio_envio = :tamanio',
        );
        $sentencia->execute(['tamanio' => $tamanio->value]);

        $row = $sentencia->fetch();

        return $row === false ? null : self::toEntity($row);
    }

    public function findAll(): array
    {
        return array_map(
            self::toEntity(...),
            $this->pdo->query('SELECT tamanio_envio, p0, k FROM parametros_logisticos')->fetchAll(),
        );
    }

    public function save(ParametroLogistico $parametro): ParametroLogistico
    {
        $sentencia = $this->pdo->prepare(
            'INSERT INTO parametros_logisticos (tamanio_envio, p0, k)
             VALUES (:tamanio_envio, :p0, :k)
             ON DUPLICATE KEY UPDATE p0 = VALUES(p0), k = VALUES(k)',
        );
        $sentencia->execute([
            'tamanio_envio' => $parametro->tamanioEnvio->value,
            'p0' => $parametro->p0->centavos,
            'k' => $parametro->k->centavos,
        ]);

        return $parametro;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function toEntity(array $row): ParametroLogistico
    {
        return new ParametroLogistico(
            TamanioEnvio::from((string) $row['tamanio_envio']),
            Dinero::centavos((int) $row['p0']),
            Dinero::centavos((int) $row['k']),
        );
    }
}
