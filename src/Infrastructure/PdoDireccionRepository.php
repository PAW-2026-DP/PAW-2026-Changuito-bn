<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Direccion;
use App\Domain\DireccionRepositoryInterface;
use App\Infrastructure\Mapper\DireccionMapper;
use PDO;

final class PdoDireccionRepository implements DireccionRepositoryInterface
{
    private const COLUMNAS = 'id, cliente_id, calle, numero, ciudad, cp, latitud, longitud, es_default';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByIdDelCliente(int $id, int $clienteId): ?Direccion
    {
        $sentencia = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM direcciones WHERE id = :id AND cliente_id = :cliente_id',
        );
        $sentencia->execute(['id' => $id, 'cliente_id' => $clienteId]);

        $row = $sentencia->fetch();

        return $row === false ? null : DireccionMapper::toEntity($row);
    }

    public function findByCliente(int $clienteId): array
    {
        $sentencia = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM direcciones WHERE cliente_id = :cliente_id ORDER BY id',
        );
        $sentencia->execute(['cliente_id' => $clienteId]);

        return array_map(DireccionMapper::toEntity(...), $sentencia->fetchAll());
    }

    public function save(Direccion $direccion): Direccion
    {
        $row = DireccionMapper::toRow($direccion);

        if ($direccion->id === 0) {
            $sentencia = $this->pdo->prepare(
                'INSERT INTO direcciones (cliente_id, calle, numero, ciudad, cp, latitud, longitud, es_default)
                 VALUES (:cliente_id, :calle, :numero, :ciudad, :cp, :latitud, :longitud, :es_default)',
            );
            $sentencia->execute($row);

            return new Direccion(
                (int) $this->pdo->lastInsertId(),
                $direccion->clienteId,
                $direccion->calle,
                $direccion->numero,
                $direccion->ciudad,
                $direccion->cp,
                $direccion->ubicacion,
                $direccion->esDefault,
            );
        }

        $sentencia = $this->pdo->prepare(
            'UPDATE direcciones
                SET calle = :calle, numero = :numero, ciudad = :ciudad, cp = :cp,
                    latitud = :latitud, longitud = :longitud, es_default = :es_default
              WHERE id = :id AND cliente_id = :cliente_id',
        );
        $sentencia->execute([...$row, 'id' => $direccion->id]);

        return $direccion;
    }

    public function delete(Direccion $direccion): void
    {
        $sentencia = $this->pdo->prepare('DELETE FROM direcciones WHERE id = :id AND cliente_id = :cliente_id');
        $sentencia->execute(['id' => $direccion->id, 'cliente_id' => $direccion->clienteId]);
    }
}
