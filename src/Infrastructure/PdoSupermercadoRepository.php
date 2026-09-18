<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Supermercado;
use App\Domain\SupermercadoRepositoryInterface;
use PDO;

final class PdoSupermercadoRepository implements SupermercadoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsuarioId(int $usuarioId): ?Supermercado
    {
        $sentencia = $this->pdo->prepare(
            'SELECT usuario_id, razon_social, cuit FROM supermercados WHERE usuario_id = :usuario_id',
        );
        $sentencia->execute(['usuario_id' => $usuarioId]);

        $row = $sentencia->fetch();

        return $row === false ? null : self::toEntity($row);
    }

    public function findAll(): array
    {
        $sentencia = $this->pdo->query('SELECT usuario_id, razon_social, cuit FROM supermercados ORDER BY usuario_id');

        return array_map(self::toEntity(...), $sentencia->fetchAll());
    }

    public function save(Supermercado $supermercado): Supermercado
    {
        $sentencia = $this->pdo->prepare(
            'INSERT INTO supermercados (usuario_id, razon_social, cuit)
             VALUES (:usuario_id, :razon_social, :cuit)
             ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), cuit = VALUES(cuit)',
        );
        $sentencia->execute([
            'usuario_id' => $supermercado->usuarioId,
            'razon_social' => $supermercado->razonSocial,
            'cuit' => $supermercado->cuit,
        ]);

        return $supermercado;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function toEntity(array $row): Supermercado
    {
        return new Supermercado((int) $row['usuario_id'], (string) $row['razon_social'], (string) $row['cuit']);
    }
}
