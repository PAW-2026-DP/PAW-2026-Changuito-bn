<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Categoria;
use App\Domain\CategoriaRepositoryInterface;
use PDO;

final class PdoCategoriaRepository implements CategoriaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?Categoria
    {
        $sentencia = $this->pdo->prepare('SELECT id, nombre FROM categorias WHERE id = :id');
        $sentencia->execute(['id' => $id]);

        $row = $sentencia->fetch();

        return $row === false ? null : self::toEntity($row);
    }

    public function findByNombre(string $nombre): ?Categoria
    {
        $sentencia = $this->pdo->prepare('SELECT id, nombre FROM categorias WHERE nombre = :nombre');
        $sentencia->execute(['nombre' => $nombre]);

        $row = $sentencia->fetch();

        return $row === false ? null : self::toEntity($row);
    }

    public function findAll(): array
    {
        return array_map(self::toEntity(...), $this->pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll());
    }

    public function save(Categoria $categoria): Categoria
    {
        if ($categoria->id === 0) {
            $sentencia = $this->pdo->prepare('INSERT INTO categorias (nombre) VALUES (:nombre)');
            $sentencia->execute(['nombre' => $categoria->nombre]);

            return new Categoria((int) $this->pdo->lastInsertId(), $categoria->nombre);
        }

        $sentencia = $this->pdo->prepare('UPDATE categorias SET nombre = :nombre WHERE id = :id');
        $sentencia->execute(['nombre' => $categoria->nombre, 'id' => $categoria->id]);

        return $categoria;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function toEntity(array $row): Categoria
    {
        return new Categoria((int) $row['id'], (string) $row['nombre']);
    }
}
