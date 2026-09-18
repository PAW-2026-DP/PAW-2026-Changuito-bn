<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Command\CrearSupermercadoCommand;
use App\Application\Command\GuardarSucursalCommand;
use App\Domain\Coordenada;
use App\Domain\Exception\EntidadNoEncontradaException;
use App\Domain\Exception\ValidacionException;
use App\Domain\Rol;
use App\Domain\Sucursal;
use App\Domain\SucursalRepositoryInterface;
use App\Domain\Supermercado;
use App\Domain\SupermercadoRepositoryInterface;
use App\Domain\UnitOfWorkInterface;
use App\Domain\Usuario;
use App\Domain\UsuarioRepositoryInterface;
use App\Domain\ZonaCobertura;
use App\Domain\ZonaCoberturaRepositoryInterface;

/**
 * Incorporación de comercios: alta de cadenas con su cuenta de acceso,
 * de sus sucursales y del radio con el que cada una compite por una compra.
 */
final class AdminComercioService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly SupermercadoRepositoryInterface $supermercados,
        private readonly SucursalRepositoryInterface $sucursales,
        private readonly ZonaCoberturaRepositoryInterface $zonas,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {
    }

    /**
     * La cuenta y los datos comerciales se crean juntos o no se crea
     * ninguno: un usuario con rol SUPERMERCADO sin fila en `supermercados`
     * no podría operar.
     */
    public function crearSupermercado(CrearSupermercadoCommand $command): Supermercado
    {
        if ($this->usuarios->findByEmail($command->email) !== null) {
            throw new ValidacionException('Ya existe una cuenta con ese email.');
        }

        return $this->unitOfWork->transactional(function () use ($command): Supermercado {
            $usuario = $this->usuarios->save(Usuario::registrar(
                0,
                $command->email,
                $command->password,
                $command->nombre,
                Rol::SUPERMERCADO,
                new \DateTimeImmutable(),
            ));

            return $this->supermercados->save(
                new Supermercado($usuario->id, $command->razonSocial, $command->cuit),
            );
        });
    }

    /**
     * @return Supermercado[]
     */
    public function listarSupermercados(): array
    {
        return $this->supermercados->findAll();
    }

    public function crearSucursal(GuardarSucursalCommand $command): Sucursal
    {
        if ($this->supermercados->findByUsuarioId($command->supermercadoId) === null) {
            throw new EntidadNoEncontradaException('El supermercado no existe.');
        }

        return $this->sucursales->save(new Sucursal(
            0,
            $command->supermercadoId,
            $command->nombre,
            $command->direccion,
            new Coordenada($command->latitud, $command->longitud),
            $command->activa,
        ));
    }

    public function actualizarSucursal(GuardarSucursalCommand $command): Sucursal
    {
        $actual = $this->obtenerSucursal($command->id);

        return $this->sucursales->save(new Sucursal(
            $actual->id,
            $actual->supermercadoId,
            $command->nombre,
            $command->direccion,
            new Coordenada($command->latitud, $command->longitud),
            $command->activa,
        ));
    }

    /**
     * @return Sucursal[]
     */
    public function listarSucursales(int $supermercadoId): array
    {
        return $this->sucursales->findBySupermercado($supermercadoId);
    }

    public function definirZonaCobertura(int $sucursalId, float $radioMinKm, float $radioMaxKm): ZonaCobertura
    {
        $this->obtenerSucursal($sucursalId);

        return $this->zonas->save(new ZonaCobertura($sucursalId, $radioMinKm, $radioMaxKm));
    }

    private function obtenerSucursal(int $id): Sucursal
    {
        $sucursal = $this->sucursales->findById($id);

        if ($sucursal === null) {
            throw new EntidadNoEncontradaException('La sucursal no existe.');
        }

        return $sucursal;
    }
}
