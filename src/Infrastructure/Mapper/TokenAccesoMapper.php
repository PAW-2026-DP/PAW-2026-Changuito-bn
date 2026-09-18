<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper;

use App\Domain\TokenAcceso;

final class TokenAccesoMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public static function toEntity(array $row): TokenAcceso
    {
        $revocadoEn = $row['revocado_en'];

        return new TokenAcceso(
            (int) $row['id'],
            (int) $row['usuario_id'],
            (string) $row['token_hash'],
            new \DateTimeImmutable((string) $row['emitido_en']),
            new \DateTimeImmutable((string) $row['expira_en']),
            $revocadoEn === null ? null : new \DateTimeImmutable((string) $revocadoEn),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRow(TokenAcceso $token): array
    {
        return [
            'usuario_id' => $token->usuarioId,
            'token_hash' => $token->tokenHash,
            'emitido_en' => $token->emitidoEn->format('Y-m-d H:i:s'),
            'expira_en' => $token->expiraEn->format('Y-m-d H:i:s'),
            'revocado_en' => $token->revocadoEn()?->format('Y-m-d H:i:s'),
        ];
    }
}
