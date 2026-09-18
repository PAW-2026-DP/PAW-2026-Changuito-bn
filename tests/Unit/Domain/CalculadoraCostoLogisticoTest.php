<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\CalculadoraCostoLogistico;
use App\Domain\Dinero;
use App\Domain\Exception\ValidacionException;
use App\Domain\ParametroLogistico;
use App\Domain\TamanioEnvio;
use PHPUnit\Framework\TestCase;

final class CalculadoraCostoLogisticoTest extends TestCase
{
    public function test_el_costo_con_un_solo_comercio_es_igual_a_p0(): void
    {
        // Arrange
        $parametro = new ParametroLogistico(TamanioEnvio::PEQUENIO, Dinero::pesos(500), Dinero::pesos(100));
        $calculadora = new CalculadoraCostoLogistico();

        // Act
        $costo = $calculadora->costoEnvio($parametro, 1);

        // Assert
        self::assertTrue($costo->equals(Dinero::pesos(500)));
    }

    public function test_el_costo_crece_segun_pn_igual_a_p0_mas_k_por_n_menos_uno_elevado_a_dos_y_medio(): void
    {
        // Arrange
        $parametro = new ParametroLogistico(TamanioEnvio::PEQUENIO, Dinero::pesos(500), Dinero::pesos(100));
        $calculadora = new CalculadoraCostoLogistico();

        // Act
        $costo = $calculadora->costoEnvio($parametro, 3);

        // Assert: 500 + 100 * (3-1)^2.5 = 500 + 100 * 5.656854... = 1065,6854...
        self::assertSame(106569, $costo->centavos);
    }

    public function test_lanza_excepcion_de_validacion_si_la_cantidad_de_comercios_es_menor_a_uno(): void
    {
        // Arrange
        $parametro = new ParametroLogistico(TamanioEnvio::PEQUENIO, Dinero::pesos(500), Dinero::pesos(100));
        $calculadora = new CalculadoraCostoLogistico();

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $calculadora->costoEnvio($parametro, 0);
    }

    public function test_el_recargo_es_la_diferencia_entre_el_costo_elegido_y_el_recomendado(): void
    {
        // Arrange
        $calculadora = new CalculadoraCostoLogistico();
        $recomendado = Dinero::pesos(500);
        $elegido = Dinero::pesos(1065.6854);

        // Act
        $recargo = $calculadora->recargo($elegido, $recomendado);

        // Assert
        self::assertTrue($recargo->equals(Dinero::centavos(56569)));
    }

    public function test_el_recargo_es_cero_cuando_el_costo_elegido_es_igual_al_recomendado(): void
    {
        // Arrange
        $calculadora = new CalculadoraCostoLogistico();
        $costo = Dinero::pesos(500);

        // Act
        $recargo = $calculadora->recargo($costo, $costo);

        // Assert
        self::assertTrue($recargo->equals(Dinero::centavos(0)));
    }
}
