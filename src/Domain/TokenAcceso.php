<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;

/**
 * Token Bearer opaco: se guarda solo el hash, nunca el valor plano, para
 * poder revocarlo en el logout sin depender de una lista negra de JWT.
 */
final class TokenAcceso
{
    private ?\DateTimeImmutable $revocadoEn;

    public function __construct(
        public readonly int $id,
        public readonly int $usuarioId,
        public readonly string $tokenHash,
        public readonly \DateTimeImmutable $emitidoEn,
        public readonly \DateTimeImmutable $expiraEn,
        ?\DateTimeImmutable $revocadoEn = null,
    ) {
        if ($expiraEn <= $emitidoEn) {
            throw new ValidacionException('La expiración debe ser posterior a la emisión.');
        }

        $this->revocadoEn = $revocadoEn;
    }

    public static function emitir(
        int $id,
        int $usuarioId,
        string $tokenPlano,
        \DateTimeImmutable $emitidoEn,
        \DateInterval $duracion,
    ): self {
        return new self($id, $usuarioId, self::hash($tokenPlano), $emitidoEn, $emitidoEn->add($duracion));
    }

    public static function hash(string $tokenPlano): string
    {
        return hash('sha256', $tokenPlano);
    }

    public function coincideCon(string $tokenPlano): bool
    {
        return hash_equals($this->tokenHash, self::hash($tokenPlano));
    }

    public function revocar(\DateTimeImmutable $fecha): void
    {
        if ($this->revocadoEn !== null) {
            throw new TransicionInvalidaException('El token ya fue revocado.');
        }

        $this->revocadoEn = $fecha;
    }

    public function estaRevocado(): bool
    {
        return $this->revocadoEn !== null;
    }

    public function esValido(\DateTimeImmutable $ahora): bool
    {
        return $this->revocadoEn === null && $ahora < $this->expiraEn;
    }
}
