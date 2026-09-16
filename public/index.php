<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Front controller: arma el contenedor, registra rutas y despacha.
// Placeholder hasta que existan Router/Pipeline/Handler en src/Http/:
// por ahora responde siempre el mismo JSON, solo para validar que la
// cadena Nginx -> PHP-FPM -> PHP funciona end to end.

header('Content-Type: application/json; charset=utf-8');

echo json_encode(['message' => 'Hello, World!'], JSON_THROW_ON_ERROR);
