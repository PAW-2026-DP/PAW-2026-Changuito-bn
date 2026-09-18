<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Dinero;
use App\Domain\EstadoPedido;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\ItemPedido;
use App\Domain\OrdenComercio;
use App\Domain\Pedido;
use PHPUnit\Framework\TestCase;

final class PedidoTest extends TestCase
{
    private function ordenParaSucursal(int $sucursalId, int $ordenId = 0): OrdenComercio
    {
        return new OrdenComercio($ordenId ?: $sucursalId, $sucursalId, [new ItemPedido(5, 2, Dinero::pesos(150))]);
    }

    private function crearPedidoDeUnaSucursal(): Pedido
    {
        return new Pedido(
            1,
            10,
            100,
            Dinero::pesos(500),
            Dinero::pesos(500),
            [$this->ordenParaSucursal(20)],
        );
    }

    private function crearPedidoDeDosSucursales(): Pedido
    {
        return new Pedido(
            1,
            10,
            100,
            Dinero::pesos(500),
            Dinero::pesos(650),
            [$this->ordenParaSucursal(20, 1), $this->ordenParaSucursal(21, 2)],
        );
    }

    public function test_nace_pendiente(): void
    {
        // Arrange / Act
        $pedido = $this->crearPedidoDeUnaSucursal();

        // Assert
        self::assertSame(EstadoPedido::PENDIENTE, $pedido->estado);
        self::assertNull($pedido->fechaConfirmacion);
    }

    public function test_lanza_excepcion_si_no_tiene_ninguna_orden_de_comercio(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        new Pedido(1, 10, 100, Dinero::pesos(500), Dinero::pesos(500), []);
    }

    public function test_confirmar_pasa_a_confirmado_y_registra_la_fecha(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $fecha = new \DateTimeImmutable('2026-09-17 12:00:00');

        // Act
        $pedido->confirmar($fecha);

        // Assert
        self::assertSame(EstadoPedido::CONFIRMADO, $pedido->estado);
        self::assertSame($fecha, $pedido->fechaConfirmacion);
    }

    public function test_lanza_excepcion_al_confirmar_un_pedido_que_no_esta_pendiente(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $pedido->confirmar(new \DateTimeImmutable());

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $pedido->confirmar(new \DateTimeImmutable());
    }

    public function test_iniciar_preparacion_de_la_unica_orden_pasa_el_pedido_a_en_preparacion(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $pedido->confirmar(new \DateTimeImmutable());

        // Act
        $pedido->iniciarPreparacion(20);

        // Assert
        self::assertSame(EstadoPedido::EN_PREPARACION, $pedido->estado);
    }

    public function test_lanza_excepcion_si_la_sucursal_no_tiene_una_orden_en_el_pedido(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $pedido->confirmar(new \DateTimeImmutable());

        // Assert
        $this->expectException(EntidadNoEncontradaException::class);

        // Act
        $pedido->iniciarPreparacion(999);
    }

    public function test_el_pedido_pasa_a_listo_para_retiro_solo_cuando_todas_las_ordenes_estan_listas(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeDosSucursales();
        $pedido->confirmar(new \DateTimeImmutable());
        $pedido->iniciarPreparacion(20);
        $pedido->iniciarPreparacion(21);
        $pedido->marcarListaParaRetiro(20);

        // Act / Assert: falta la segunda sucursal
        self::assertSame(EstadoPedido::EN_PREPARACION, $pedido->estado);

        $pedido->marcarListaParaRetiro(21);
        self::assertSame(EstadoPedido::LISTO_PARA_RETIRO, $pedido->estado);
    }

    public function test_asignar_un_repartidor_requiere_que_el_pedido_este_listo_para_retiro(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $pedido->asignar(77);
    }

    public function test_asignar_un_repartidor_lo_deja_en_estado_asignado(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $pedido->confirmar(new \DateTimeImmutable());
        $pedido->iniciarPreparacion(20);
        $pedido->marcarListaParaRetiro(20);

        // Act
        $pedido->asignar(77);

        // Assert
        self::assertSame(EstadoPedido::ASIGNADO, $pedido->estado);
        self::assertSame(77, $pedido->repartidorId);
    }

    public function test_el_pedido_pasa_a_retirado_solo_cuando_todas_las_ordenes_fueron_retiradas(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeDosSucursales();
        $pedido->confirmar(new \DateTimeImmutable());
        $pedido->iniciarPreparacion(20);
        $pedido->iniciarPreparacion(21);
        $pedido->marcarListaParaRetiro(20);
        $pedido->marcarListaParaRetiro(21);
        $pedido->asignar(77);
        $pedido->registrarRetiro(20);

        // Act / Assert: falta el retiro de la segunda sucursal
        self::assertSame(EstadoPedido::ASIGNADO, $pedido->estado);

        $pedido->registrarRetiro(21);
        self::assertSame(EstadoPedido::RETIRADO, $pedido->estado);
    }

    public function test_marcar_en_camino_requiere_que_el_pedido_este_retirado(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $pedido->marcarEnCamino();
    }

    public function test_entregar_requiere_que_el_pedido_este_en_camino(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $pedido->entregar();
    }

    public function test_recorre_el_ciclo_completo_hasta_entregado(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $pedido->confirmar(new \DateTimeImmutable());
        $pedido->iniciarPreparacion(20);
        $pedido->marcarListaParaRetiro(20);
        $pedido->asignar(77);
        $pedido->registrarRetiro(20);

        // Act
        $pedido->marcarEnCamino();
        $pedido->entregar();

        // Assert
        self::assertSame(EstadoPedido::ENTREGADO, $pedido->estado);
    }

    public function test_se_puede_cancelar_en_cualquier_estado_previo_a_en_camino(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $pedido->confirmar(new \DateTimeImmutable());

        // Act
        $pedido->cancelar();

        // Assert
        self::assertSame(EstadoPedido::CANCELADO, $pedido->estado);
    }

    public function test_lanza_excepcion_al_cancelar_un_pedido_en_camino(): void
    {
        // Arrange
        $pedido = $this->crearPedidoDeUnaSucursal();
        $pedido->confirmar(new \DateTimeImmutable());
        $pedido->iniciarPreparacion(20);
        $pedido->marcarListaParaRetiro(20);
        $pedido->asignar(77);
        $pedido->registrarRetiro(20);
        $pedido->marcarEnCamino();

        // Assert
        $this->expectException(TransicionInvalidaException::class);

        // Act
        $pedido->cancelar();
    }
}
