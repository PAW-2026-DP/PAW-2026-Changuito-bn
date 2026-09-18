<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Tamaño de un envío. Determina qué coeficientes de costo logístico usa
 * CalculadoraCostoLogistico y a qué categoría de productos pertenece
 * (ver Producto), porque cada tamaño requiere transporte y tiempos de
 * entrega distintos.
 */
enum TamanioEnvio: string
{
    case PEQUENIO = 'pequenio';
    case MEDIANO = 'mediano';
    case GRANDE = 'grande';
}
