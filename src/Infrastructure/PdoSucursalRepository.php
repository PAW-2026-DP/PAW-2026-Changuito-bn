<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Sucursal;
use App\Domain\SucursalRepositoryInterface;
use App\Infrastructure\Mapper\SucursalMapper;
use PDO;

final class PdoSucursalRepository implements SucursalRepositoryInterface
{
    private const COLUMNAS = 'id, supermercado_id, nombre, direccion, latitud, longitud, activa';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?Sucursal
    {
        $sentencia = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM sucursales WHERE id = :id');
        $sentencia->execute(['id' => $id]);

        $row = $sentencia->fetch();

        return $row === false ? null : SucursalMapper::toEntity($row);
    }

    public function findBySupermercado(int $supermercadoId): array
    {
        $sentencia = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM sucursales WHERE supermercado_id = :supermercado_id ORDER BY id',
        );
        $sentencia->execute(['supermercado_id' => $supermercadoId]);

        return array_map(SucursalMapper::toEntity(...), $sentencia->fetchAll());
    }

    public function findActivas(): array
    {
        $sentencia = $this->pdo->query('SELECT ' . self::COLUMNAS . ' FROM sucursales WHERE activa = 1 ORDER BY id');

        return array_map(SucursalMapper::toEntity(...), $sentencia->fetchAll());
    }

    public function save(Sucursal $sucursal): Sucursal
    {
        $row = SucursalMapper::toRow($sucursal);

        if ($sucursal->id === 0) {
            $sentencia = $this->pdo->prepare(
                'INSERT INTO sucursales (supermercado_id, nombre, direccion, latitud, longitud, activa)
                 VALUES (:supermercado_id, :nombre, :direccion, :latitud, :longitud, :activa)',
            );
            $sentencia->execute($row);

            return new Sucursal(
                (int) $this->pdo->lastInsertId(),
                $sucursal->supermercadoId,
                $sucursal->nombre,
                $sucursal->direccion,
                $sucursal->ubicacion,
                $sucursal->activa,
            );
        }

        $sentencia = $this->pdo->prepare(
            'UPDATE sucursales
                SET supermercado_id = :supermercado_id, nombre = :nombre, direccion = :direccion,
                    latitud = :latitud, longitud = :longitud, activa = :activa
              WHERE id = :id',
        );
        $sentencia->execute([...$row, 'id' => $sucursal->id]);

        return $sucursal;
    }
}
