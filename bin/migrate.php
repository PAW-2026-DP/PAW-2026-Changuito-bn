#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Runner de migraciones SQL. Aplica, en orden, los archivos pendientes de
 * database/migrations/*.sql contra la base de la app o la de test, y deja
 * registro de lo aplicado en la tabla `schema_migrations`.
 *
 * Uso:
 *   docker compose exec app php bin/migrate.php          # base de la app
 *   docker compose exec app php bin/migrate.php --test   # base de test
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Infrastructure\Database\ConnectionFactory;

$esTest = in_array('--test', $argv, true);
$pdo = $esTest ? ConnectionFactory::forTests() : ConnectionFactory::forApplication();

$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(255) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL
    )
    SQL);

$aplicadas = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$directorio = dirname(__DIR__) . '/database/migrations';
$archivos = glob($directorio . '/*.sql') ?: [];
sort($archivos);

$pendientes = array_filter(
    $archivos,
    static fn (string $archivo): bool => !in_array(basename($archivo), $aplicadas, true),
);

if ($pendientes === []) {
    fwrite(STDOUT, 'No hay migraciones pendientes (' . ($esTest ? 'test' : 'app') . ").\n");
    exit(0);
}

foreach ($pendientes as $archivo) {
    $nombre = basename($archivo);
    $sql = file_get_contents($archivo);

    if ($sql === false) {
        fwrite(STDERR, "No se pudo leer {$nombre}\n");
        exit(1);
    }

    $pdo->beginTransaction();

    try {
        $pdo->exec($sql);

        $registrar = $pdo->prepare(
            'INSERT INTO schema_migrations (migration, applied_at) VALUES (:migration, :appliedAt)',
        );
        $registrar->execute(['migration' => $nombre, 'appliedAt' => date('Y-m-d H:i:s')]);

        $pdo->commit();
        fwrite(STDOUT, "Aplicada: {$nombre}\n");
    } catch (\Throwable $exception) {
        $pdo->rollBack();
        fwrite(STDERR, "Error aplicando {$nombre}: {$exception->getMessage()}\n");
        exit(1);
    }
}
