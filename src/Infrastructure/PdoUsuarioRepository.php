<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Rol;
use App\Domain\Usuario;
use App\Domain\UsuarioRepositoryInterface;
use App\Infrastructure\Mapper\UsuarioMapper;
use PDO;

final class PdoUsuarioRepository implements UsuarioRepositoryInterface
{
    private const COLUMNAS = 'id, email, password_hash, nombre, rol, fecha_alta, activo';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?Usuario
    {
        $sentencia = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM usuarios WHERE id = :id');
        $sentencia->execute(['id' => $id]);

        return $this->mapearUno($sentencia->fetch());
    }

    public function findByEmail(string $email): ?Usuario
    {
        $sentencia = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM usuarios WHERE email = :email');
        $sentencia->execute(['email' => $email]);

        return $this->mapearUno($sentencia->fetch());
    }

    public function findByRol(?Rol $rol): array
    {
        if ($rol === null) {
            $sentencia = $this->pdo->query('SELECT ' . self::COLUMNAS . ' FROM usuarios ORDER BY id');
        } else {
            $sentencia = $this->pdo->prepare(
                'SELECT ' . self::COLUMNAS . ' FROM usuarios WHERE rol = :rol ORDER BY id',
            );
            $sentencia->execute(['rol' => $rol->value]);
        }

        return array_map(UsuarioMapper::toEntity(...), $sentencia->fetchAll());
    }

    public function save(Usuario $usuario): Usuario
    {
        $row = UsuarioMapper::toRow($usuario);

        if ($usuario->id === 0) {
            $sentencia = $this->pdo->prepare(
                'INSERT INTO usuarios (email, password_hash, nombre, rol, fecha_alta, activo)
                 VALUES (:email, :password_hash, :nombre, :rol, :fecha_alta, :activo)',
            );
            $sentencia->execute($row);

            return new Usuario(
                (int) $this->pdo->lastInsertId(),
                $usuario->email,
                $usuario->passwordHash(),
                $usuario->nombre,
                $usuario->rol,
                $usuario->fechaAlta,
                $usuario->activo,
            );
        }

        $sentencia = $this->pdo->prepare(
            'UPDATE usuarios
                SET email = :email, password_hash = :password_hash, nombre = :nombre,
                    rol = :rol, fecha_alta = :fecha_alta, activo = :activo
              WHERE id = :id',
        );
        $sentencia->execute([...$row, 'id' => $usuario->id]);

        return $usuario;
    }

    /**
     * @param array<string, mixed>|false $row
     */
    private function mapearUno(array|false $row): ?Usuario
    {
        return $row === false ? null : UsuarioMapper::toEntity($row);
    }
}
