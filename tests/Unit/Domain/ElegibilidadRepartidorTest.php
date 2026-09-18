<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Coordenada;
use App\Domain\ElegibilidadRepartidor;
use App\Domain\Exception\ValidacionException;
use App\Domain\Repartidor;
use PHPUnit\Framework\TestCase;

final class ElegibilidadRepartidorTest extends TestCase
{
    private const LATITUD_BASE = -34.6037;
    private const LONGITUD_BASE = -58.3816;
    private const RADIO_KM = 5.0;

    private Coordenada $destino;

    protected function setUp(): void
    {
        $this->destino = $this->coordenada(0.0);
    }

    public function test_ofrece_el_pedido_al_repartidor_disponible_dentro_del_radio(): void
    {
        // Arrange
        $repartidor = $this->repartidorDisponible(1, 0.01);

        // Act
        $elegibles = $this->elegibilidad()->elegibles(
            [$repartidor],
            [$this->coordenada(0.01)],
            $this->destino,
            self::RADIO_KM,
        );

        // Assert
        self::assertSame([$repartidor], $elegibles);
    }

    public function test_excluye_al_repartidor_que_no_declaro_disponibilidad(): void
    {
        // Arrange
        $repartidor = new Repartidor(usuarioId: 1);

        // Act
        $elegibles = $this->elegibilidad()->elegibles(
            [$repartidor],
            [$this->coordenada(0.01)],
            $this->destino,
            self::RADIO_KM,
        );

        // Assert
        self::assertSame([], $elegibles);
    }

    public function test_excluye_al_repartidor_lejos_de_un_punto_de_retiro(): void
    {
        // Arrange — está sobre el destino, pero la segunda sucursal queda a ~11 km
        $repartidor = $this->repartidorDisponible(1, 0.0);

        // Act
        $elegibles = $this->elegibilidad()->elegibles(
            [$repartidor],
            [$this->coordenada(0.0), $this->coordenada(0.1)],
            $this->destino,
            self::RADIO_KM,
        );

        // Assert
        self::assertSame([], $elegibles);
    }

    public function test_excluye_al_repartidor_lejos_del_destino_de_entrega(): void
    {
        // Arrange — está sobre la sucursal, pero a ~11 km de la entrega
        $repartidor = $this->repartidorDisponible(1, 0.1);

        // Act
        $elegibles = $this->elegibilidad()->elegibles(
            [$repartidor],
            [$this->coordenada(0.1)],
            $this->destino,
            self::RADIO_KM,
        );

        // Assert
        self::assertSame([], $elegibles);
    }

    public function test_devuelve_solo_los_repartidores_que_cubren_todos_los_puntos(): void
    {
        // Arrange
        $cerca = $this->repartidorDisponible(1, 0.01);
        $lejos = $this->repartidorDisponible(2, 0.1);

        // Act
        $elegibles = $this->elegibilidad()->elegibles(
            [$cerca, $lejos],
            [$this->coordenada(0.0)],
            $this->destino,
            self::RADIO_KM,
        );

        // Assert
        self::assertSame([$cerca], $elegibles);
    }

    public function test_rechaza_un_radio_de_oferta_no_positivo(): void
    {
        // Assert
        $this->expectException(ValidacionException::class);

        // Act
        $this->elegibilidad()->elegibles([], [$this->coordenada(0.0)], $this->destino, 0.0);
    }

    private function elegibilidad(): ElegibilidadRepartidor
    {
        return new ElegibilidadRepartidor();
    }

    private function coordenada(float $offsetGrados): Coordenada
    {
        return new Coordenada(self::LATITUD_BASE + $offsetGrados, self::LONGITUD_BASE);
    }

    private function repartidorDisponible(int $usuarioId, float $offsetGrados): Repartidor
    {
        $repartidor = new Repartidor($usuarioId);
        $repartidor->ponerseDisponible($this->coordenada($offsetGrados), new \DateTimeImmutable('2026-09-18 10:00:00'));

        return $repartidor;
    }
}
