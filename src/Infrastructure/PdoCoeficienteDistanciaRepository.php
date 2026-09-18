<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\CoeficienteDistancia;
use App\Domain\CoeficienteDistanciaRepositoryInterface;
use App\Domain\TamanioEnvio;
use PDO;

final class PdoCoeficienteDistanciaRepository implements CoeficienteDistanciaRepositoryInterface
{
    private const COLUMNAS = 'id, tamanio_envio, distancia_min, distancia_max, coeficiente';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?CoeficienteDistancia
    {
        $sentencia = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM coeficientes_distancia WHERE id = :id');
        $sentencia->execute(['id' => $id]);

        $row = $sentencia->fetch();

        return $row === false ? null : self::toEntity($row);
    }

    public function findAll(): array
    {
        $sentencia = $this->pdo->query(
            'SELECT ' . self::COLUMNAS . ' FROM coeficientes_distancia ORDER BY tamanio_envio, distancia_min',
        );

        return array_map(self::toEntity(...), $sentencia->fetchAll());
    }

    public function save(CoeficienteDistancia $coeficiente): CoeficienteDistancia
    {
        $parametros = [
            'tamanio_envio' => $coeficiente->tamanioEnvio->value,
            'distancia_min' => $coeficiente->distanciaMinKm,
            'distancia_max' => $coeficiente->distanciaMaxKm,
            'coeficiente' => $coeficiente->coeficiente,
        ];

        if ($coeficiente->id === 0) {
            $sentencia = $this->pdo->prepare(
                'INSERT INTO coeficientes_distancia (tamanio_envio, distancia_min, distancia_max, coeficiente)
                 VALUES (:tamanio_envio, :distancia_min, :distancia_max, :coeficiente)',
            );
            $sentencia->execute($parametros);

            return new CoeficienteDistancia(
                (int) $this->pdo->lastInsertId(),
                $coeficiente->tamanioEnvio,
                $coeficiente->distanciaMinKm,
                $coeficiente->distanciaMaxKm,
                $coeficiente->coeficiente,
            );
        }

        $sentencia = $this->pdo->prepare(
            'UPDATE coeficientes_distancia
                SET tamanio_envio = :tamanio_envio, distancia_min = :distancia_min,
                    distancia_max = :distancia_max, coeficiente = :coeficiente
              WHERE id = :id',
        );
        $sentencia->execute([...$parametros, 'id' => $coeficiente->id]);

        return $coeficiente;
    }

    public function delete(CoeficienteDistancia $coeficiente): void
    {
        $sentencia = $this->pdo->prepare('DELETE FROM coeficientes_distancia WHERE id = :id');
        $sentencia->execute(['id' => $coeficiente->id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function toEntity(array $row): CoeficienteDistancia
    {
        return new CoeficienteDistancia(
            (int) $row['id'],
            TamanioEnvio::from((string) $row['tamanio_envio']),
            (float) $row['distancia_min'],
            (float) $row['distancia_max'],
            (float) $row['coeficiente'],
        );
    }
}
