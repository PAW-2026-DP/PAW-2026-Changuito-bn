<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Monto monetario. Se guarda en centavos (enteros) para no arrastrar los
 * errores de redondeo de los floats al sumar o comparar precios.
 */
final class Dinero
{
    private function __construct(public readonly int $centavos)
    {
        if ($centavos < 0) {
            throw new ValidacionException('El monto no puede ser negativo.');
        }
    }

    public static function centavos(int $centavos): self
    {
        return new self($centavos);
    }

    public static function pesos(float $pesos): self
    {
        return new self((int) round($pesos * 100));
    }

    public function sumar(self $otro): self
    {
        return new self($this->centavos + $otro->centavos);
    }

    public function restar(self $otro): self
    {
        return new self($this->centavos - $otro->centavos);
    }

    public function multiplicarPor(int $factor): self
    {
        if ($factor < 0) {
            throw new ValidacionException('El factor no puede ser negativo.');
        }

        return new self($this->centavos * $factor);
    }

    public function esMayorQue(self $otro): bool
    {
        return $this->centavos > $otro->centavos;
    }

    public function equals(self $otro): bool
    {
        return $this->centavos === $otro->centavos;
    }
}
