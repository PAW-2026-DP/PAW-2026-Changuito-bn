<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Command\GuardarDireccionCommand;
use App\Application\DireccionService;
use App\Domain\Exception\EntidadNoEncontradaException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryDireccionRepository;
use Tests\Fakes\InMemoryUnitOfWork;

final class DireccionServiceTest extends TestCase
{
    private const CLIENTE = 1;
    private const OTRO_CLIENTE = 2;

    private InMemoryDireccionRepository $direcciones;
    private DireccionService $service;

    protected function setUp(): void
    {
        $this->direcciones = new InMemoryDireccionRepository();
        $this->service = new DireccionService($this->direcciones, new InMemoryUnitOfWork());
    }

    public function test_la_primera_direccion_queda_como_predeterminada(): void
    {
        // Act
        $direccion = $this->service->crear($this->comando());

        // Assert
        self::assertTrue($direccion->esDefault);
    }

    public function test_la_segunda_direccion_no_queda_como_predeterminada(): void
    {
        // Arrange
        $this->service->crear($this->comando());

        // Act
        $segunda = $this->service->crear($this->comando(calle: 'Otra calle'));

        // Assert
        self::assertFalse($segunda->esDefault);
    }

    public function test_lista_solo_las_direcciones_del_cliente(): void
    {
        // Arrange
        $this->service->crear($this->comando());
        $this->service->crear($this->comando(clienteId: self::OTRO_CLIENTE));

        // Act
        $direcciones = $this->service->listar(self::CLIENTE);

        // Assert
        self::assertCount(1, $direcciones);
        self::assertSame(self::CLIENTE, $direcciones[0]->clienteId);
    }

    public function test_marcar_predeterminada_desmarca_la_anterior(): void
    {
        // Arrange
        $primera = $this->service->crear($this->comando());
        $segunda = $this->service->crear($this->comando(calle: 'Otra calle'));

        // Act
        $this->service->marcarPredeterminada($segunda->id, self::CLIENTE);

        // Assert
        self::assertTrue($this->direcciones->findByIdDelCliente($segunda->id, self::CLIENTE)?->esDefault);
        self::assertFalse($this->direcciones->findByIdDelCliente($primera->id, self::CLIENTE)?->esDefault);
    }

    public function test_actualiza_los_datos_conservando_la_marca_de_predeterminada(): void
    {
        // Arrange
        $direccion = $this->service->crear($this->comando());

        // Act
        $actualizada = $this->service->actualizar($this->comando(calle: 'Calle nueva', id: $direccion->id));

        // Assert
        self::assertSame('Calle nueva', $actualizada->calle);
        self::assertTrue($actualizada->esDefault);
    }

    public function test_elimina_una_direccion_del_cliente(): void
    {
        // Arrange
        $direccion = $this->service->crear($this->comando());

        // Act
        $this->service->eliminar($direccion->id, self::CLIENTE);

        // Assert
        self::assertSame([], $this->service->listar(self::CLIENTE));
    }

    public function test_trata_como_inexistente_la_direccion_de_otro_cliente(): void
    {
        // Arrange
        $ajena = $this->service->crear($this->comando(clienteId: self::OTRO_CLIENTE));

        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->eliminar($ajena->id, self::CLIENTE);
    }

    public function test_falla_al_marcar_como_predeterminada_una_direccion_inexistente(): void
    {
        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->marcarPredeterminada(999, self::CLIENTE);
    }

    private function comando(
        int $clienteId = self::CLIENTE,
        string $calle = 'Av. Siempreviva',
        int $id = 0,
    ): GuardarDireccionCommand {
        return new GuardarDireccionCommand($clienteId, $calle, '742', 'Luján', '6700', -34.57, -59.10, $id);
    }
}
