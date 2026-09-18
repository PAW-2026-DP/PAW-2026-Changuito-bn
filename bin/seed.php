#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Carga datos de desarrollo: un usuario por rol, una cadena con sucursales
 * y zonas, categorías, productos y parámetros logísticos, para poder
 * probar la API sin cargar todo a mano.
 *
 * Va en PHP y no en un .sql porque las contraseñas tienen que pasar por
 * password_hash(); un INSERT con el hash escrito a mano quedaría atado al
 * algoritmo del día que se escribió.
 *
 * Es idempotente: si el admin ya existe, no hace nada.
 *
 * Uso:
 *   docker compose exec app php bin/seed.php          # base de la app
 *   docker compose exec app php bin/seed.php --test   # base de test
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Domain\Categoria;
use App\Domain\Coordenada;
use App\Domain\Dinero;
use App\Domain\ParametroLogistico;
use App\Domain\Producto;
use App\Domain\Rol;
use App\Domain\Sucursal;
use App\Domain\Supermercado;
use App\Domain\TamanioEnvio;
use App\Domain\Usuario;
use App\Domain\ZonaCobertura;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\PdoCategoriaRepository;
use App\Infrastructure\PdoParametroLogisticoRepository;
use App\Infrastructure\PdoProductoRepository;
use App\Infrastructure\PdoSucursalRepository;
use App\Infrastructure\PdoSupermercadoRepository;
use App\Infrastructure\PdoUsuarioRepository;
use App\Infrastructure\PdoZonaCoberturaRepository;

const PASSWORD_DEMO = 'changuito123';

$esTest = in_array('--test', $argv, true);
$pdo = $esTest ? ConnectionFactory::forTests() : ConnectionFactory::forApplication();

$usuarios = new PdoUsuarioRepository($pdo);

if ($usuarios->findByEmail('admin@changuito.test') !== null) {
    fwrite(STDOUT, "El seed ya fue aplicado, no se hace nada.\n");
    exit(0);
}

$ahora = new \DateTimeImmutable();

$crearCuenta = static function (string $email, string $nombre, Rol $rol) use ($usuarios, $ahora): Usuario {
    return $usuarios->save(Usuario::registrar(0, $email, PASSWORD_DEMO, $nombre, $rol, $ahora));
};

$crearCuenta('admin@changuito.test', 'Administradora', Rol::ADMINISTRADOR);
$crearCuenta('ana@changuito.test', 'Ana Cliente', Rol::CLIENTE);
$crearCuenta('rider@changuito.test', 'Rita Repartidora', Rol::REPARTIDOR);

$cuentaDia = $crearCuenta('dia@changuito.test', 'DIA', Rol::SUPERMERCADO);
$cuentaCoto = $crearCuenta('coto@changuito.test', 'COTO', Rol::SUPERMERCADO);

$supermercados = new PdoSupermercadoRepository($pdo);
$supermercados->save(new Supermercado($cuentaDia->id, 'DIA Argentina SA', '30-11111111-1'));
$supermercados->save(new Supermercado($cuentaCoto->id, 'COTO CICSA', '30-22222222-2'));

// Luján, donde está la sede de la UNLu: las coordenadas del seed rondan el
// centro para que las zonas de cobertura se solapen y haya competencia.
$sucursales = new PdoSucursalRepository($pdo);
$zonas = new PdoZonaCoberturaRepository($pdo);

$puntos = [
    [$cuentaDia->id, 'DIA Centro', 'San Martín 100', -34.5703, -59.1050],
    [$cuentaDia->id, 'DIA Norte', 'Av. Constitución 900', -34.5580, -59.1120],
    [$cuentaCoto->id, 'COTO Centro', 'Mitre 250', -34.5720, -59.1010],
];

foreach ($puntos as [$supermercadoId, $nombre, $direccion, $latitud, $longitud]) {
    $sucursal = $sucursales->save(new Sucursal(
        0,
        $supermercadoId,
        $nombre,
        $direccion,
        new Coordenada($latitud, $longitud),
        true,
    ));

    $zonas->save(new ZonaCobertura($sucursal->id, 0.0, 10.0));
}

$categorias = new PdoCategoriaRepository($pdo);
$productos = new PdoProductoRepository($pdo);

$catalogo = [
    'Almacén' => [
        ['Fideos secos 500g', TamanioEnvio::PEQUENIO],
        ['Arroz largo fino 1kg', TamanioEnvio::PEQUENIO],
        ['Aceite de girasol 1.5L', TamanioEnvio::MEDIANO],
    ],
    'Bebidas' => [
        ['Agua mineral 2L', TamanioEnvio::MEDIANO],
        ['Gaseosa cola 2.25L', TamanioEnvio::MEDIANO],
    ],
    'Limpieza' => [
        ['Detergente 750ml', TamanioEnvio::PEQUENIO],
        ['Pack de papel higiénico x12', TamanioEnvio::GRANDE],
    ],
];

foreach ($catalogo as $nombreCategoria => $items) {
    $categoria = $categorias->save(new Categoria(0, $nombreCategoria));

    foreach ($items as [$nombreProducto, $tamanio]) {
        $productos->save(new Producto(0, $nombreProducto, $nombreProducto, $categoria->id, $tamanio));
    }
}

$parametros = new PdoParametroLogisticoRepository($pdo);
$parametros->save(new ParametroLogistico(TamanioEnvio::PEQUENIO, Dinero::pesos(500.0), Dinero::pesos(100.0)));
$parametros->save(new ParametroLogistico(TamanioEnvio::MEDIANO, Dinero::pesos(800.0), Dinero::pesos(150.0)));
$parametros->save(new ParametroLogistico(TamanioEnvio::GRANDE, Dinero::pesos(1200.0), Dinero::pesos(300.0)));

fwrite(STDOUT, "Seed aplicado. Todas las cuentas usan la contraseña: " . PASSWORD_DEMO . "\n");
