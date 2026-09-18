<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\AdminLogisticaService;
use App\Domain\Dinero;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\TamanioEnvio;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryCoeficienteDistanciaRepository;
use Tests\Fakes\InMemoryParametroLogisticoRepository;
use Tests\Fakes\InMemoryParametroRepartoRepository;

final class AdminLogisticaServiceTest extends TestCase
{
    private AdminLogisticaService $service;

    protected function setUp(): void
    {
        $this->service = new AdminLogisticaService(
            new InMemoryParametroLogisticoRepository(),
            new InMemoryCoeficienteDistanciaRepository(),
            new InMemoryParametroRepartoRepository(),
        );
    }

    public function test_define_los_coeficientes_de_un_tamanio_de_envio(): void
    {
        // Act
        $parametro = $this->service->definirParametro(
            TamanioEnvio::MEDIANO,
            Dinero::centavos(80000),
            Dinero::centavos(15000),
        );

        // Assert
        self::assertSame(80000, $parametro->p0->centavos);
        self::assertCount(1, $this->service->listarParametros());
    }

    public function test_redefinir_un_tamanio_reemplaza_sus_coeficientes(): void
    {
        // Arrange
        $this->service->definirParametro(TamanioEnvio::MEDIANO, Dinero::centavos(80000), Dinero::centavos(15000));

        // Act
        $this->service->definirParametro(TamanioEnvio::MEDIANO, Dinero::centavos(90000), Dinero::centavos(20000));

        // Assert
        $parametros = $this->service->listarParametros();
        self::assertCount(1, $parametros);
        self::assertSame(90000, $parametros[0]->p0->centavos);
    }

    public function test_crea_un_tramo_de_distancia(): void
    {
        // Act
        $coeficiente = $this->service->crearCoeficiente(TamanioEnvio::GRANDE, 0.0, 5.0, 1.2);

        // Assert
        self::assertGreaterThan(0, $coeficiente->id);
        self::assertTrue($coeficiente->cubreDistancia(3.0));
    }

    public function test_rechaza_un_tramo_con_distancia_minima_mayor_a_la_maxima(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->service->crearCoeficiente(TamanioEnvio::GRANDE, 10.0, 5.0, 1.2);
    }

    public function test_actualiza_un_tramo_existente(): void
    {
        // Arrange
        $coeficiente = $this->service->crearCoeficiente(TamanioEnvio::GRANDE, 0.0, 5.0, 1.2);

        // Act
        $actualizado = $this->service->actualizarCoeficiente($coeficiente->id, TamanioEnvio::GRANDE, 0.0, 8.0, 1.5);

        // Assert
        self::assertSame(1.5, $actualizado->coeficiente);
        self::assertSame(8.0, $actualizado->distanciaMaxKm);
    }

    public function test_elimina_un_tramo(): void
    {
        // Arrange
        $coeficiente = $this->service->crearCoeficiente(TamanioEnvio::GRANDE, 0.0, 5.0, 1.2);

        // Act
        $this->service->eliminarCoeficiente($coeficiente->id);

        // Assert
        self::assertSame([], $this->service->listarCoeficientes());
    }

    public function test_falla_al_eliminar_un_tramo_inexistente(): void
    {
        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->eliminarCoeficiente(999);
    }

    public function test_ajusta_el_radio_de_oferta_a_los_repartidores(): void
    {
        // Act
        $parametro = $this->service->definirRadioDeOferta(8.5);

        // Assert
        self::assertSame(8.5, $parametro->radioOfertaKm);
        self::assertSame(8.5, $this->service->radioDeOferta()->radioOfertaKm);
    }

    public function test_rechaza_un_radio_de_oferta_no_positivo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->service->definirRadioDeOferta(0.0);
    }
}
