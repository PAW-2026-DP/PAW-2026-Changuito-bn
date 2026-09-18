<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\TokenAcceso;
use App\Domain\TokenAccesoRepositoryInterface;
use App\Infrastructure\Mapper\TokenAccesoMapper;
use PDO;

final class PdoTokenAccesoRepository implements TokenAccesoRepositoryInterface
{
    private const COLUMNAS = 'id, usuario_id, token_hash, emitido_en, expira_en, revocado_en';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByHash(string $tokenHash): ?TokenAcceso
    {
        $sentencia = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . ' FROM tokens_acceso WHERE token_hash = :token_hash',
        );
        $sentencia->execute(['token_hash' => $tokenHash]);

        $row = $sentencia->fetch();

        return $row === false ? null : TokenAccesoMapper::toEntity($row);
    }

    public function save(TokenAcceso $token): TokenAcceso
    {
        $row = TokenAccesoMapper::toRow($token);

        if ($token->id === 0) {
            $sentencia = $this->pdo->prepare(
                'INSERT INTO tokens_acceso (usuario_id, token_hash, emitido_en, expira_en, revocado_en)
                 VALUES (:usuario_id, :token_hash, :emitido_en, :expira_en, :revocado_en)',
            );
            $sentencia->execute($row);

            return new TokenAcceso(
                (int) $this->pdo->lastInsertId(),
                $token->usuarioId,
                $token->tokenHash,
                $token->emitidoEn,
                $token->expiraEn,
                $token->revocadoEn(),
            );
        }

        $sentencia = $this->pdo->prepare(
            'UPDATE tokens_acceso SET revocado_en = :revocado_en WHERE id = :id',
        );
        $sentencia->execute(['revocado_en' => $row['revocado_en'], 'id' => $token->id]);

        return $token;
    }
}
