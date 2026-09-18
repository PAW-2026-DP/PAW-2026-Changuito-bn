<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Dinero;
use App\Domain\ParametroLogistico;
use App\Domain\TamanioEnvio;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\PdoParametroLogisticoRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoParametroLogisticoRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoParametroLogisticoRepository $repositorio;

    protected function setUp(): void
    {
        $this->pdo = ConnectionFactory::forTests();
        $this->pdo->exec('DELETE FROM parametros_logisticos');
        $this->repositorio = new PdoParametroLogisticoRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM parametros_logisticos');
    }

    public function test_guarda_los_montos_en_centavos_sin_perder_precision(): void
    {
        // Act
        $this->repositorio->save(new ParametroLogistico(
            TamanioEnvio::MEDIANO,
            Dinero::centavos(80099),
            Dinero::centavos(15001),
        ));

        // Assert
        $recuperado = $this->repositorio->findByTamanio(TamanioEnvio::MEDIANO);
        self::assertNotNull($recuperado);
        self::assertSame(80099, $recuperado->p0->centavos);
        self::assertSame(15001, $recuperado->k->centavos);
    }

    public function test_devuelve_null_cuando_el_tamanio_no_tiene_parametros(): void
    {
        // Act
        $recuperado = $this->repositorio->findByTamanio(TamanioEnvio::GRANDE);

        // Assert
        self::assertNull($recuperado);
    }

    public function test_volver_a_guardar_un_tamanio_reemplaza_sus_coeficientes(): void
    {
        // Arrange
        $this->repositorio->save(new ParametroLogistico(TamanioEnvio::MEDIANO, Dinero::centavos(80000), Dinero::centavos(15000)));

        // Act
        $this->repositorio->save(new ParametroLogistico(TamanioEnvio::MEDIANO, Dinero::centavos(90000), Dinero::centavos(20000)));

        // Assert
        self::assertCount(1, $this->repositorio->findAll());
        self::assertSame(90000, $this->repositorio->findByTamanio(TamanioEnvio::MEDIANO)?->p0->centavos);
    }
}
