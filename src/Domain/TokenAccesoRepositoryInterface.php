<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Persistencia de los tokens Bearer emitidos. La búsqueda es por hash: el
 * valor plano solo lo conoce el cliente (ver App\Domain\TokenAcceso).
 */
interface TokenAccesoRepositoryInterface
{
    public function findByHash(string $tokenHash): ?TokenAcceso;

    public function save(TokenAcceso $token): TokenAcceso;
}
