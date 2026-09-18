<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\TransicionInvalidaException;

/**
 * Pedido de un supermercado para dar de alta un producto que no está en el
 * catálogo maestro. Solo el admin la resuelve, y solo mientras está
 * PENDIENTE, para no duplicar el producto normalizado.
 */
final class SolicitudAltaProducto
{
    public EstadoSolicitudAltaProducto $estado;
    public ?int $productoId = null;
    public ?string $motivoRechazo = null;
    public ?\DateTimeImmutable $fechaResolucion = null;

    public function __construct(
        public readonly int $id,
        public readonly int $supermercadoId,
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly int $categoriaSugeridaId,
        public readonly \DateTimeImmutable $fechaSolicitud,
    ) {
        $this->estado = EstadoSolicitudAltaProducto::PENDIENTE;
    }

    public function aprobar(int $productoId, \DateTimeImmutable $fechaResolucion): void
    {
        $this->verificarPendiente();

        $this->estado = EstadoSolicitudAltaProducto::APROBADA;
        $this->productoId = $productoId;
        $this->fechaResolucion = $fechaResolucion;
    }

    public function rechazar(string $motivo, \DateTimeImmutable $fechaResolucion): void
    {
        $this->verificarPendiente();

        $this->estado = EstadoSolicitudAltaProducto::RECHAZADA;
        $this->motivoRechazo = $motivo;
        $this->fechaResolucion = $fechaResolucion;
    }

    private function verificarPendiente(): void
    {
        if ($this->estado !== EstadoSolicitudAltaProducto::PENDIENTE) {
            throw new TransicionInvalidaException('Solo se puede resolver una solicitud pendiente.');
        }
    }
}
