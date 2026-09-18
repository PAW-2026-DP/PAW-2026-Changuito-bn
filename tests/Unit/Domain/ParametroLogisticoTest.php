<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Dinero;
use App\Domain\ParametroLogistico;
use App\Domain\TamanioEnvio;
use PHPUnit\Framework\TestCase;

final class ParametroLogisticoTest extends TestCase
{
    public function test_expone_el_tamanio_p0_y_k_con_los_que_se_construyo(): void
    {
        // Arrange
        $p0 = Dinero::pesos(500);
        $k = Dinero::pesos(120);

        // Act
        $parametro = new ParametroLogistico(TamanioEnvio::PEQUENIO, $p0, $k);

        // Assert
        self::assertSame(TamanioEnvio::PEQUENIO, $parametro->tamanioEnvio);
        self::assertTrue($parametro->p0->equals($p0));
        self::assertTrue($parametro->k->equals($k));
    }
}
