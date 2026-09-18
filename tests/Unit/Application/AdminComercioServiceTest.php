<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\AdminComercioService;
use App\Application\Command\CrearSupermercadoCommand;
use App\Application\Command\GuardarSucursalCommand;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemorySucursalRepository;
use Tests\Fakes\InMemorySupermercadoRepository;
use Tests\Fakes\InMemoryUnitOfWork;
use Tests\Fakes\InMemoryUsuarioRepository;
use Tests\Fakes\InMemoryZonaCoberturaRepository;

final class AdminComercioServiceTest extends TestCase
{
    private InMemoryUsuarioRepository $usuarios;
    private InMemorySupermercadoRepository $supermercados;
    private InMemorySucursalRepository $sucursales;
    private InMemoryZonaCoberturaRepository $zonas;
    private AdminComercioService $service;

    protected function setUp(): void
    {
        $this->usuarios = new InMemoryUsuarioRepository();
        $this->supermercados = new InMemorySupermercadoRepository();
        $this->sucursales = new InMemorySucursalRepository();
        $this->zonas = new InMemoryZonaCoberturaRepository();
        $this->service = new AdminComercioService(
            $this->usuarios,
            $this->supermercados,
            $this->sucursales,
            $this->zonas,
            new InMemoryUnitOfWork(),
        );
    }

    public function test_crear_un_supermercado_crea_tambien_su_cuenta_de_acceso(): void
    {
        // Act
        $supermercado = $this->service->crearSupermercado($this->comandoSupermercado());

        // Assert
        $usuario = $this->usuarios->findByEmail('dia@changuito.test');
        self::assertNotNull($usuario);
        self::assertSame(Rol::SUPERMERCADO, $usuario->rol);
        self::assertSame($usuario->id, $supermercado->usuarioId);
        self::assertSame('DIA Argentina SA', $supermercado->razonSocial);
    }

    public function test_rechaza_un_supermercado_con_email_ya_usado(): void
    {
        // Arrange
        $this->service->crearSupermercado($this->comandoSupermercado());

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->service->crearSupermercado($this->comandoSupermercado());
    }

    public function test_crea_una_sucursal_del_supermercado(): void
    {
        // Arrange
        $supermercado = $this->service->crearSupermercado($this->comandoSupermercado());

        // Act
        $sucursal = $this->service->crearSucursal($this->comandoSucursal($supermercado->usuarioId));

        // Assert
        self::assertGreaterThan(0, $sucursal->id);
        self::assertSame('Centro', $sucursal->nombre);
        self::assertCount(1, $this->service->listarSucursales($supermercado->usuarioId));
    }

    public function test_rechaza_crear_una_sucursal_de_un_supermercado_inexistente(): void
    {
        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->crearSucursal($this->comandoSucursal(999));
    }

    public function test_actualiza_una_sucursal_conservando_su_supermercado(): void
    {
        // Arrange
        $supermercado = $this->service->crearSupermercado($this->comandoSupermercado());
        $sucursal = $this->service->crearSucursal($this->comandoSucursal($supermercado->usuarioId));

        // Act
        $actualizada = $this->service->actualizarSucursal(new GuardarSucursalCommand(
            $supermercado->usuarioId,
            'Centro renovado',
            'Calle 2',
            -34.6,
            -58.4,
            false,
            $sucursal->id,
        ));

        // Assert
        self::assertSame('Centro renovado', $actualizada->nombre);
        self::assertFalse($actualizada->activa);
        self::assertSame($supermercado->usuarioId, $actualizada->supermercadoId);
    }

    public function test_define_la_zona_de_cobertura_de_una_sucursal(): void
    {
        // Arrange
        $supermercado = $this->service->crearSupermercado($this->comandoSupermercado());
        $sucursal = $this->service->crearSucursal($this->comandoSucursal($supermercado->usuarioId));

        // Act
        $zona = $this->service->definirZonaCobertura($sucursal->id, 0.5, 8.0);

        // Assert
        self::assertSame(8.0, $zona->radioMaxKm);
        self::assertNotNull($this->zonas->findBySucursal($sucursal->id));
    }

    public function test_rechaza_una_zona_con_radio_minimo_mayor_al_maximo(): void
    {
        // Arrange
        $supermercado = $this->service->crearSupermercado($this->comandoSupermercado());
        $sucursal = $this->service->crearSucursal($this->comandoSucursal($supermercado->usuarioId));

        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->service->definirZonaCobertura($sucursal->id, 10.0, 2.0);
    }

    public function test_rechaza_definir_la_zona_de_una_sucursal_inexistente(): void
    {
        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $this->service->definirZonaCobertura(999, 0.5, 8.0);
    }

    private function comandoSupermercado(): CrearSupermercadoCommand
    {
        return new CrearSupermercadoCommand(
            'dia@changuito.test',
            'secreta123',
            'DIA',
            'DIA Argentina SA',
            '30-12345678-9',
        );
    }

    private function comandoSucursal(int $supermercadoId): GuardarSucursalCommand
    {
        return new GuardarSucursalCommand($supermercadoId, 'Centro', 'Calle 1', -34.6, -58.4);
    }
}
