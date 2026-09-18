<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Estado de una SolicitudAltaProducto. Solo se resuelve (aprueba o
 * rechaza) una solicitud PENDIENTE.
 */
enum EstadoSolicitudAltaProducto: string
{
    case PENDIENTE = 'pendiente';
    case APROBADA = 'aprobada';
    case RECHAZADA = 'rechazada';
}
