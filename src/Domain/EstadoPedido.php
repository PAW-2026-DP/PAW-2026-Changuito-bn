<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Ciclo de vida de un Pedido. LISTO_PARA_RETIRO y RETIRADO son estados
 * derivados: el agregado Pedido los alcanza solo cuando todas sus
 * OrdenComercio están, respectivamente, listas o retiradas.
 * CANCELADO es alcanzable desde cualquier estado previo a EN_CAMINO.
 */
enum EstadoPedido: string
{
    case PENDIENTE = 'pendiente';
    case CONFIRMADO = 'confirmado';
    case EN_PREPARACION = 'en_preparacion';
    case LISTO_PARA_RETIRO = 'listo_para_retiro';
    case ASIGNADO = 'asignado';
    case RETIRADO = 'retirado';
    case EN_CAMINO = 'en_camino';
    case ENTREGADO = 'entregado';
    case CANCELADO = 'cancelado';
}
