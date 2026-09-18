<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

/**
 * Arma la conexión PDO a partir de las variables de entorno (ver
 * .env.example), para que los repositorios PDO y el runner de migraciones
 * (bin/migrate.php) no repitan la configuración de conexión.
 */
final class ConnectionFactory
{
    public static function forApplication(): PDO
    {
        return self::connect(self::env('DB_DATABASE'));
    }

    /**
     * Conecta contra la base de datos de test (ver reglas-testing.md: los
     * tests de Integration de Infrastructure corren contra una base real,
     * nunca con mocks), separada de la de desarrollo para no pisar datos.
     */
    public static function forTests(): PDO
    {
        return self::connect(self::env('DB_TEST_DATABASE', 'changuito_test'));
    }

    private static function connect(string $database): PDO
    {
        $host = self::env('DB_HOST', '127.0.0.1');
        $port = self::env('DB_PORT', '3306');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        return new PDO($dsn, self::env('DB_USERNAME'), self::env('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private static function env(string $name, ?string $default = null): string
    {
        $value = getenv($name);

        if ($value !== false && $value !== '') {
            return $value;
        }

        if ($default !== null) {
            return $default;
        }

        throw new \RuntimeException("Falta la variable de entorno {$name}.");
    }
}
