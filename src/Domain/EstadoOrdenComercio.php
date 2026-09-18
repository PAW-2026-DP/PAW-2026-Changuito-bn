<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Estado de preparación y retiro de la porción de un Pedido que corresponde
 * a una sucursal puntual.
 */
enum EstadoOrdenComercio: string
{
    case PENDIENTE = 'pendiente';
    case EN_PREPARACION = 'en_preparacion';
    case LISTO_PARA_RETIRO = 'listo_para_retiro';
    case RETIRADO = 'retirado';
}
