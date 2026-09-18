<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Rol;
use App\Domain\TokenAcceso;
use App\Domain\Usuario;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\PdoTokenAccesoRepository;
use App\Infrastructure\PdoUsuarioRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoTokenAccesoRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoTokenAccesoRepository $repositorio;
    private int $usuarioId;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::forTests();
        $this->pdo->exec('DELETE FROM tokens_acceso');
        $this->pdo->exec('DELETE FROM usuarios');

        $usuario = (new PdoUsuarioRepository($this->pdo))->save(Usuario::registrar(
            0,
            'ana@changuito.test',
            'secreta123',
            'Ana',
            Rol::CLIENTE,
            new \DateTimeImmutable('2026-09-18 10:00:00'),
        ));

        $this->usuarioId = $usuario->id;
        $this->repositorio = new PdoTokenAccesoRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM tokens_acceso');
        $this->pdo->exec('DELETE FROM usuarios');
    }

    public function test_guarda_el_token_y_lo_recupera_por_su_hash(): void
    {
        // Arrange
        $token = $this->emitir('token-plano');

        // Act
        $guardado = $this->repositorio->save($token);
        $recuperado = $this->repositorio->findByHash(TokenAcceso::hash('token-plano'));

        // Assert
        self::assertGreaterThan(0, $guardado->id);
        self::assertNotNull($recuperado);
        self::assertSame($this->usuarioId, $recuperado->usuarioId);
        self::assertTrue($recuperado->coincideCon('token-plano'));
    }

    public function test_devuelve_null_cuando_el_hash_no_existe(): void
    {
        // Act
        $recuperado = $this->repositorio->findByHash(TokenAcceso::hash('inexistente'));

        // Assert
        self::assertNull($recuperado);
    }

    public function test_persiste_la_revocacion_del_token(): void
    {
        // Arrange
        $guardado = $this->repositorio->save($this->emitir('token-plano'));
        $guardado->revocar(new \DateTimeImmutable('2026-09-18 12:00:00'));

        // Act
        $this->repositorio->save($guardado);

        // Assert
        $recuperado = $this->repositorio->findByHash(TokenAcceso::hash('token-plano'));
        self::assertNotNull($recuperado);
        self::assertTrue($recuperado->estaRevocado());
        self::assertFalse($recuperado->esValido(new \DateTimeImmutable('2026-09-18 13:00:00')));
    }

    private function emitir(string $tokenPlano): TokenAcceso
    {
        return TokenAcceso::emitir(
            0,
            $this->usuarioId,
            $tokenPlano,
            new \DateTimeImmutable('2026-09-18 10:00:00'),
            new \DateInterval('P7D'),
        );
    }
}
