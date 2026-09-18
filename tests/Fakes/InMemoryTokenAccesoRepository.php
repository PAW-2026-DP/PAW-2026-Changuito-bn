<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\TokenAcceso;
use App\Domain\TokenAccesoRepositoryInterface;

final class InMemoryTokenAccesoRepository implements TokenAccesoRepositoryInterface
{
    /** @var array<int, TokenAcceso> */
    private array $tokens = [];

    private int $proximoId = 1;

    public function findByHash(string $tokenHash): ?TokenAcceso
    {
        foreach ($this->tokens as $token) {
            if (hash_equals($token->tokenHash, $tokenHash)) {
                return $token;
            }
        }

        return null;
    }

    public function save(TokenAcceso $token): TokenAcceso
    {
        if ($token->id === 0) {
            $token = new TokenAcceso(
                $this->proximoId++,
                $token->usuarioId,
                $token->tokenHash,
                $token->emitidoEn,
                $token->expiraEn,
            );
        }

        $this->tokens[$token->id] = $token;

        return $token;
    }
}
