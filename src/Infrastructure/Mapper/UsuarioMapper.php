<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper;

use App\Domain\Rol;
use App\Domain\Usuario;

/**
 * Traduce entre la fila de `usuarios` y la entidad. La entidad no conoce
 * nombres de columnas (ver lineamientos, sección 5.4).
 */
final class UsuarioMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function toEntity(array $row): Usuario
    {
        return new Usuario(
            (int) $row['id'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['nombre'],
            Rol::from((string) $row['rol']),
            new \DateTimeImmutable((string) $row['fecha_alta']),
            (bool) $row['activo'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRow(Usuario $usuario): array
    {
        return [
            'email' => $usuario->email,
            'password_hash' => $usuario->passwordHash(),
            'nombre' => $usuario->nombre,
            'rol' => $usuario->rol->value,
            'fecha_alta' => $usuario->fechaAlta->format('Y-m-d H:i:s'),
            'activo' => (int) $usuario->activo,
        ];
    }
}
