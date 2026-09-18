<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\ZonaCobertura;
use App\Domain\ZonaCoberturaRepositoryInterface;
use PDO;

final class PdoZonaCoberturaRepository implements ZonaCoberturaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findBySucursal(int $sucursalId): ?ZonaCobertura
    {
        $sentencia = $this->pdo->prepare(
            'SELECT sucursal_id, radio_min, radio_max FROM zonas_cobertura WHERE sucursal_id = :sucursal_id',
        );
        $sentencia->execute(['sucursal_id' => $sucursalId]);

        $row = $sentencia->fetch();

        return $row === false ? null : self::toEntity($row);
    }

    public function findAll(): array
    {
        $sentencia = $this->pdo->query('SELECT sucursal_id, radio_min, radio_max FROM zonas_cobertura');

        return array_map(self::toEntity(...), $sentencia->fetchAll());
    }

    public function save(ZonaCobertura $zona): ZonaCobertura
    {
        $sentencia = $this->pdo->prepare(
            'INSERT INTO zonas_cobertura (sucursal_id, radio_min, radio_max)
             VALUES (:sucursal_id, :radio_min, :radio_max)
             ON DUPLICATE KEY UPDATE radio_min = VALUES(radio_min), radio_max = VALUES(radio_max)',
        );
        $sentencia->execute([
            'sucursal_id' => $zona->sucursalId,
            'radio_min' => $zona->radioMinKm,
            'radio_max' => $zona->radioMaxKm,
        ]);

        return $zona;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function toEntity(array $row): ZonaCobertura
    {
        return new ZonaCobertura((int) $row['sucursal_id'], (float) $row['radio_min'], (float) $row['radio_max']);
    }
}
