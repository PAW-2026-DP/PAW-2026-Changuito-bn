<?php

declare(strict_types=1);

use App\Infrastructure\Database\ConnectionFactory;

/**
 * Fábrica de la conexión PDO de la app, para que los módulos que agreguen
 * repositorios PDO la reutilicen sin repetir la configuración de conexión
 * (ver App\Infrastructure\Database\ConnectionFactory). Devuelve un closure
 * en vez de un PDO ya conectado: la conexión se abre recién cuando el
 * primer repositorio la necesita, no en cada request.
 *
 * @return callable(): PDO
 */
return static fn (): PDO => ConnectionFactory::forApplication();
