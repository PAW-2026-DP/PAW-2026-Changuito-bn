# Changuito - Backend

Backend de Changuito: plataforma que permite armar pedidos de compra
optimizados entre supermercados, con roles de cliente, supermercado,
repartidor y administrador.

- Usuarios y roles: cliente, supermercado, repartidor y administrador.
- Catálogo, precios, stock e historial de modificaciones.
- Listas de compras.
- Optimización de compra en uno o dos comercios.
- Generación y seguimiento de pedidos.
- Paneles para administración.

---

## Levantar el contenedor en local

Requiere Docker y Docker Compose instalados.

1. Clonar el repo y pararse en la raíz.
2. Copiar el archivo de variables de entorno de ejemplo:
   ```bash
   cp .env.example .env
   ```
3. Levantar los servicios (build incluido la primera vez):
   ```bash
   docker compose up -d --build
   ```
   Esto levanta tres contenedores: `nginx` (puerto `8080` por default, configurable
   con `APP_PORT` en `.env`), `app` (PHP-FPM) y `db` (MariaDB).
4. Instalar las dependencias de PHP dentro del contenedor (nunca con Composer
   local, para que `composer.lock` quede generado con la misma versión de
   PHP/extensiones del contenedor):
   ```bash
   docker compose exec app composer install
   ```
5. Probar que la app responde en `http://localhost:8080`:
   ```bash
   curl -i http://localhost:8080/health
   ```
   Debería devolver `200 OK` con un JSON `{"status":"ok",...}`.
6. Correr la suite de tests:
   ```bash
   docker compose exec app vendor/bin/phpunit
   ```

Para bajar los contenedores: `docker compose down` (agregar `-v` si además
se quiere borrar el volumen de la base de datos).

---

## Docs

https://paw-2026-changuito-docs-git-master-jbrodi99s-projects.vercel.app/

- [CI/CD](docs/cicd.md): configuración de GitHub Actions, Artifact Registry, Workload Identity Federation y despliegue a la VM de DEV.

---

## Arquitectura actual

PHP vanilla (sin frameworks), en capas `Http → Application → Domain`, con
`Infrastructure` implementando interfaces del `Domain`. El detalle completo
de convenciones vive en `.claude/rules/lineamientos-desarrollo.md`; acá se
resume lo que ya está implementado.

### Entrypoint y ruteo (`src/Http/`, `config/`)

Ya están armados el Front Controller y el Router (patrones 5.1 y 5.2 de los
lineamientos), sin controladores de negocio todavía:

- `public/index.php`: único punto de entrada. Arma la app vía
  `config/container.php`, construye un `Request` desde `$_SERVER`/`$_GET`/
  `php://input` y envía la `Response` resultante. Un `catch` general traduce
  cualquier excepción no controlada a `500`.
- `src/Http/Request.php` / `Response.php`: objetos inmutables que representan
  la request/response HTTP (método, path, headers, query, body, status).
- `src/Http/Router.php`: resuelve `método + path → handler`, soporta
  parámetros de ruta (`/pedidos/{id}`), devuelve `404` si no matchea ningún
  path y `405` con header `Allow` si el path existe pero con otro método.
- `src/Http/Pipeline.php` + `src/Http/Middleware.php`: pipeline de
  middlewares transversales (auth, CORS, logging) que envuelve al `Router`.
  Por ahora no hay middlewares registrados; agregar uno nuevo no requiere
  tocar controladores existentes.
- `src/Http/Controller/PingController.php`: único controlador existente,
  de prueba, reutilizado por todas las rutas prototipadas hasta que se
  implementen los controladores reales según las reglas de negocio.
- `config/routes.php`: registro de rutas. Tiene `GET /health` activa y
  bloques comentados por rol (cliente, supermercado, repartidor, admin) como
  puntos de partida para cuando lleguen las reglas de negocio de cada
  módulo.
- `config/container.php`: arma `Router` + `Pipeline` sin contenedor de DI
  externo.

### Capas todavía sin implementar

- `src/Application/`, `src/Domain/`, `src/Infrastructure/`: vacías, a la
  espera de la especificación de cada módulo de negocio.
- Base de datos: el servicio `db` (MariaDB) está levantado por Docker Compose
  pero todavía no hay ningún esquema ni migración.

### Entorno de contenedores

- Servicios (`docker-compose.yml`): `app` (PHP-FPM, stage `dev` con Xdebug),
  `nginx` (sirve `public/`), `db` (MariaDB).
- Las mismas capas de `docker/php/Dockerfile` (stage `base`) arman la imagen
  de deploy (`docker build --target production -f docker/php/Dockerfile .`),
  así dev y producción nunca divergen en dependencias.

## Carpetas

- `src/`: código de dominio en capas (`Http`, `Application`, `Domain`,
  `Infrastructure`).
- `config/`: configuración y armado de la app (rutas, contenedor).
- `public/`: front controller (`index.php`), document root de Nginx.
- `tests/`: `Unit/`, `Integration/` y `Fakes/` reutilizables entre tests.
- `docs/`: decisiones y documentación técnica.

## Estado actual

Front Controller, Router, Request/Response y Pipeline de middlewares
implementados y testeados (ver `src/Http/`). Todavía no hay controladores,
servicios ni entidades de negocio — se agregan a medida que se define cada
módulo.
