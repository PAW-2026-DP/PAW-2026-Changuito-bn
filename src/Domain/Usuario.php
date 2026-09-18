<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Cuenta de acceso a la plataforma. El rol determina qué panel y qué grupo
 * de rutas puede usar (ver RolMiddleware).
 */
final class Usuario
{
    public bool $activo;

    public function __construct(
        public readonly int $id,
        public readonly string $email,
        private string $passwordHash,
        public readonly string $nombre,
        public readonly Rol $rol,
        public readonly \DateTimeImmutable $fechaAlta,
        bool $activo = true,
    ) {
        if (trim($email) === '') {
            throw new ValidacionException('El email no puede estar vacío.');
        }

        if (trim($nombre) === '') {
            throw new ValidacionException('El nombre no puede estar vacío.');
        }

        $this->activo = $activo;
    }

    public static function registrar(
        int $id,
        string $email,
        string $passwordPlano,
        string $nombre,
        Rol $rol,
        \DateTimeImmutable $fechaAlta,
    ): self {
        return new self($id, $email, password_hash($passwordPlano, PASSWORD_DEFAULT), $nombre, $rol, $fechaAlta);
    }

    public function verificarPassword(string $passwordPlano): bool
    {
        return password_verify($passwordPlano, $this->passwordHash);
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function activar(): void
    {
        $this->activo = true;
    }

    public function desactivar(): void
    {
        $this->activo = false;
    }
}
