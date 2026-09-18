<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Command\GuardarDireccionCommand;
use App\Domain\Coordenada;
use App\Domain\Direccion;
use App\Domain\DireccionRepositoryInterface;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\UnitOfWorkInterface;

/**
 * Casos de uso de las direcciones de entrega de un cliente.
 *
 * Todas las operaciones reciben el id del cliente autenticado y buscan la
 * dirección acotada a él: una dirección ajena se comporta como inexistente.
 */
final class DireccionService
{
    public function __construct(
        private readonly DireccionRepositoryInterface $direcciones,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {
    }

    /**
     * @return Direccion[]
     */
    public function listar(int $clienteId): array
    {
        return $this->direcciones->findByCliente($clienteId);
    }

    public function crear(GuardarDireccionCommand $command): Direccion
    {
        // La primera dirección queda como predeterminada: sin una, el
        // checkout no tendría destino por defecto.
        $esLaPrimera = $this->direcciones->findByCliente($command->clienteId) === [];

        return $this->direcciones->save(new Direccion(
            0,
            $command->clienteId,
            $command->calle,
            $command->numero,
            $command->ciudad,
            $command->cp,
            new Coordenada($command->latitud, $command->longitud),
            $esLaPrimera,
        ));
    }

    public function actualizar(GuardarDireccionCommand $command): Direccion
    {
        $actual = $this->obtener($command->id, $command->clienteId);

        return $this->direcciones->save(new Direccion(
            $actual->id,
            $actual->clienteId,
            $command->calle,
            $command->numero,
            $command->ciudad,
            $command->cp,
            new Coordenada($command->latitud, $command->longitud),
            $actual->esDefault,
        ));
    }

    public function eliminar(int $id, int $clienteId): void
    {
        $this->direcciones->delete($this->obtener($id, $clienteId));
    }

    public function marcarPredeterminada(int $id, int $clienteId): Direccion
    {
        $elegida = $this->obtener($id, $clienteId);

        return $this->unitOfWork->transactional(function () use ($elegida, $clienteId): Direccion {
            foreach ($this->direcciones->findByCliente($clienteId) as $direccion) {
                if ($direccion->esDefault) {
                    $direccion->quitarPredeterminada();
                    $this->direcciones->save($direccion);
                }
            }

            $elegida->marcarComoPredeterminada();

            return $this->direcciones->save($elegida);
        });
    }

    private function obtener(int $id, int $clienteId): Direccion
    {
        $direccion = $this->direcciones->findByIdDelCliente($id, $clienteId);

        if ($direccion === null) {
            throw new EntidadNoEncontradaException('La dirección no existe.');
        }

        return $direccion;
    }
}
