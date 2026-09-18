<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\ValidacionException;

/**
 * Publicación de un Producto maestro en una Sucursal concreta, con su
 * precio y stock vigentes. El supermercado la mantiene actualizada; sin
 * publicación, el producto no aparece en la comparación de precios.
 */
final class ProductoSucursal
{
    public Dinero $precio;
    public int $stock;
    public \DateTimeImmutable $fechaUltimaActualizacion;

    public function __construct(
        public readonly int $productoId,
        public readonly int $sucursalId,
        Dinero $precio,
        int $stock,
        \DateTimeImmutable $fechaUltimaActualizacion,
    ) {
        $this->validarStock($stock);

        $this->precio = $precio;
        $this->stock = $stock;
        $this->fechaUltimaActualizacion = $fechaUltimaActualizacion;
    }

    public function actualizarPrecioYStock(Dinero $precio, int $stock, \DateTimeImmutable $fecha): void
    {
        $this->validarStock($stock);

        $this->precio = $precio;
        $this->stock = $stock;
        $this->fechaUltimaActualizacion = $fecha;
    }

    private function validarStock(int $stock): void
    {
        if ($stock < 0) {
            throw new ValidacionException('El stock no puede ser negativo.');
        }
    }
}
