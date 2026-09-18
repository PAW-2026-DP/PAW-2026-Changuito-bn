<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Producto;
use App\Domain\ProductoRepositoryInterface;
use App\Infrastructure\Mapper\ProductoMapper;
use PDO;

final class PdoProductoRepository implements ProductoRepositoryInterface
{
    private const COLUMNAS = 'id, nombre, descripcion, categoria_id, tamanio_envio';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?Producto
    {
        $sentencia = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM productos WHERE id = :id');
        $sentencia->execute(['id' => $id]);

        $row = $sentencia->fetch();

        return $row === false ? null : ProductoMapper::toEntity($row);
    }

    public function buscar(?string $texto, ?int $categoriaId): array
    {
        $condiciones = [];
        $parametros = [];

        if ($texto !== null && $texto !== '') {
            $condiciones[] = 'nombre LIKE :texto';
            $parametros['texto'] = '%' . $texto . '%';
        }

        if ($categoriaId !== null) {
            $condiciones[] = 'categoria_id = :categoria_id';
            $parametros['categoria_id'] = $categoriaId;
        }

        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

        $sentencia = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM productos' . $where . ' ORDER BY nombre');
        $sentencia->execute($parametros);

        return array_map(ProductoMapper::toEntity(...), $sentencia->fetchAll());
    }

    public function save(Producto $producto): Producto
    {
        $row = ProductoMapper::toRow($producto);

        if ($producto->id === 0) {
            $sentencia = $this->pdo->prepare(
                'INSERT INTO productos (nombre, descripcion, categoria_id, tamanio_envio)
                 VALUES (:nombre, :descripcion, :categoria_id, :tamanio_envio)',
            );
            $sentencia->execute($row);

            return new Producto(
                (int) $this->pdo->lastInsertId(),
                $producto->nombre,
                $producto->descripcion,
                $producto->categoriaId,
                $producto->tamanioEnvio,
            );
        }

        $sentencia = $this->pdo->prepare(
            'UPDATE productos
                SET nombre = :nombre, descripcion = :descripcion,
                    categoria_id = :categoria_id, tamanio_envio = :tamanio_envio
              WHERE id = :id',
        );
        $sentencia->execute([...$row, 'id' => $producto->id]);

        return $producto;
    }
}
