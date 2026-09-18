<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\TokenAcceso;
use PHPUnit\Framework\TestCase;

final class TokenAccesoTest extends TestCase
{
    public function test_emitir_genera_un_token_valido_que_coincide_con_el_token_plano(): void
    {
        // Arrange
        $emitidoEn = new \DateTimeImmutable('2026-09-17 10:00:00');

        // Act
        $token = TokenAcceso::emitir(1, 10, 'token-plano-secreto', $emitidoEn, new \DateInterval('PT1H'));

        // Assert
        self::assertTrue($token->coincideCon('token-plano-secreto'));
        self::assertFalse($token->coincideCon('otro-token'));
        self::assertTrue($token->esValido(new \DateTimeImmutable('2026-09-17 10:30:00')));
    }

    public function test_no_es_valido_una_vez_expirado(): void
    {
        // Arrange
        $emitidoEn = new \DateTimeImmutable('2026-09-17 10:00:00');
        $token = TokenAcceso::emitir(1, 10, 'token-plano-secreto', $emitidoEn, new \DateInterval('PT1H'));

        // Act / Assert
        self::assertFalse($token->esValido(new \DateTimeImmutable('2026-09-17 11:30:00')));
    }

    public function test_revocar_invalida_el_token_aunque_no_haya_expirado(): void
    {
        // Arrange
        $emitidoEn = new \DateTimeImmutable('2026-09-17 10:00:00');
        $token = TokenAcceso::emitir(1, 10, 'token-plano-secreto', $emitidoEn, new \DateInterval('PT1H'));

        // Act
        $token->revocar(new \DateTimeImmutable('2026-09-17 10:15:00'));

        // Assert
        self::assertTrue($token->estaRevocado());
        self::assertFalse($token->esValido(new \DateTimeImmutable('2026-09-17 10:20:00')));
    }

    public function test_lanza_excepcion_al_revocar_un_token_ya_revocado(): void
    {
        // Arrange
        $token = TokenAcceso::emitir(1, 10, 'token', new \DateTimeImmutable(), new \DateInterval('PT1H'));
        $token->revocar(new \DateTimeImmutable());

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $token->revocar(new \DateTimeImmutable());
    }

    public function test_lanza_excepcion_si_la_expiracion_no_es_posterior_a_la_emision(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new TokenAcceso(1, 10, 'hash', new \DateTimeImmutable('2026-09-17 10:00:00'), new \DateTimeImmutable('2026-09-17 09:00:00'));
    }
}
