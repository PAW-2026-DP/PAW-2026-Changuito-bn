<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Roles de usuario del sistema. Determina qué panel y qué grupo de rutas
 * puede usar cada cuenta (ver RolMiddleware, a implementar junto con la
 * autenticación).
 */
enum Rol: string
{
    case CLIENTE = 'cliente';
    case SUPERMERCADO = 'supermercado';
    case REPARTIDOR = 'repartidor';
    case ADMINISTRADOR = 'administrador';
}
