<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\TransicionInvalidaException;
use App\Domain\Exception\ValidacionException;

/**
 * Agregado raíz de la compra. Coordina sus OrdenComercio: el pedido nunca
 * transiciona a EN_CAMINO hasta que todas fueron retiradas, y CANCELADO es
 * alcanzable desde cualquier estado previo a EN_CAMINO.
 */
final class Pedido
{
    private const ESTADOS_CANCELABLES = [
        EstadoPedido::PENDIENTE,
        EstadoPedido::CONFIRMADO,
        EstadoPedido::EN_PREPARACION,
        EstadoPedido::LISTO_PARA_RETIRO,
        EstadoPedido::ASIGNADO,
        EstadoPedido::RETIRADO,
    ];

    public EstadoPedido $estado;
    public ?int $repartidorId = null;
    public ?\DateTimeImmutable $fechaConfirmacion = null;

    /** @var array<int, OrdenComercio> indexado por sucursalId */
    private array $ordenes = [];

    /** @param OrdenComercio[] $ordenes */
    public function __construct(
        public readonly int $id,
        public readonly int $clienteId,
        public readonly int $direccionEntregaId,
        public readonly Dinero $costoLogisticoRecomendado,
        public readonly Dinero $costoLogisticoElegido,
        array $ordenes,
    ) {
        if ($ordenes === []) {
            throw new ValidacionException('Un pedido debe tener al menos una orden de comercio.');
        }

        foreach ($ordenes as $orden) {
            $this->ordenes[$orden->sucursalId] = $orden;
        }

        $this->estado = EstadoPedido::PENDIENTE;
    }

    public function confirmar(\DateTimeImmutable $fecha): void
    {
        $this->verificarEstado(EstadoPedido::PENDIENTE, 'confirmar');

        $this->estado = EstadoPedido::CONFIRMADO;
        $this->fechaConfirmacion = $fecha;
    }

    public function iniciarPreparacion(int $sucursalId): void
    {
        $this->orden($sucursalId)->iniciarPreparacion();

        if ($this->estado === EstadoPedido::CONFIRMADO) {
            $this->estado = EstadoPedido::EN_PREPARACION;
        }
    }

    public function marcarListaParaRetiro(int $sucursalId): void
    {
        $this->orden($sucursalId)->marcarLista();

        if ($this->todasLasOrdenesEstan(EstadoOrdenComercio::LISTO_PARA_RETIRO)) {
            $this->estado = EstadoPedido::LISTO_PARA_RETIRO;
        }
    }

    public function asignar(int $repartidorId): void
    {
        $this->verificarEstado(EstadoPedido::LISTO_PARA_RETIRO, 'asignar un repartidor a');

        $this->estado = EstadoPedido::ASIGNADO;
        $this->repartidorId = $repartidorId;
    }

    public function registrarRetiro(int $sucursalId): void
    {
        $this->orden($sucursalId)->registrarRetiro();

        if ($this->todasLasOrdenesEstan(EstadoOrdenComercio::RETIRADO)) {
            $this->estado = EstadoPedido::RETIRADO;
        }
    }

    public function marcarEnCamino(): void
    {
        $this->verificarEstado(EstadoPedido::RETIRADO, 'marcar en camino');

        $this->estado = EstadoPedido::EN_CAMINO;
    }

    public function entregar(): void
    {
        $this->verificarEstado(EstadoPedido::EN_CAMINO, 'entregar');

        $this->estado = EstadoPedido::ENTREGADO;
    }

    public function cancelar(): void
    {
        if (!in_array($this->estado, self::ESTADOS_CANCELABLES, true)) {
            throw new TransicionInvalidaException(
                "No se puede cancelar un pedido en estado {$this->estado->value}."
            );
        }

        $this->estado = EstadoPedido::CANCELADO;
    }

    /** @return OrdenComercio[] */
    public function ordenes(): array
    {
        return array_values($this->ordenes);
    }

    private function orden(int $sucursalId): OrdenComercio
    {
        return $this->ordenes[$sucursalId]
            ?? throw new EntidadNoEncontradaException("El pedido no tiene una orden para la sucursal {$sucursalId}.");
    }

    private function todasLasOrdenesEstan(EstadoOrdenComercio $estado): bool
    {
        foreach ($this->ordenes as $orden) {
            if ($orden->estado !== $estado) {
                return false;
            }
        }

        return true;
    }

    private function verificarEstado(EstadoPedido $esperado, string $accion): void
    {
        if ($this->estado !== $esperado) {
            throw new TransicionInvalidaException("No se puede {$accion} un pedido en estado {$this->estado->value}.");
        }
    }
}
