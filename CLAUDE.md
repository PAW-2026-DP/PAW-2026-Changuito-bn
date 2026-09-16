# Changuito — Backend

Backend de Changuito: plataforma que permite armar pedidos de compra
optimizados entre supermercados, con roles de cliente, supermercado,
repartidor y administrador.

## Estado actual

Proyecto en etapa inicial: la estructura de carpetas (`src/`, `tests/`,
`public/`) y el entorno de contenedores están creados, pero todavía no hay
código PHP de dominio ni base de datos con esquema. La arquitectura
definitiva se implementa siguiendo los lineamientos importados abajo.

## Entorno de desarrollo (Docker)

Todo el desarrollo corre en contenedores para que el equipo comparta
exactamente las mismas dependencias, extensiones de PHP y versión — y esas
mismas capas (`docker/php/Dockerfile`, stage `base`) son las que arma la
imagen de deploy, así dev y producción nunca divergen en dependencias.

- Servicios (`docker-compose.yml`): `app` (PHP-FPM, stage `dev` con Xdebug),
  `nginx` (sirve `public/`), `db` (MariaDB).
- Primer uso: `cp .env.example .env`, luego `docker compose up -d --build`.
- Instalar/actualizar dependencias: `docker compose exec app composer install`
  (o `composer require ...`) — nunca con Composer local, para que
  `composer.lock` quede generado con la misma versión de PHP/extensiones del
  contenedor.
- Tests: `docker compose exec app vendor/bin/phpunit`.
- La imagen de deploy se construye con `docker build --target production
  -f docker/php/Dockerfile .` — mismo `Dockerfile`, stage sin Xdebug ni
  dependencias `require-dev`, con autoload optimizado.

## Lineamientos del equipo

Estas reglas son obligatorias para todo el desarrollo en este repo y no se
negocian por PR individual. Los documentos completos viven en
`.claude/rules/` — acá se importan para que estén siempre presentes en el
contexto:

@.claude/rules/lineamientos-desarrollo.md
@.claude/rules/ramas-pr-despliegues.md
@.claude/rules/reglas-testing.md

## Resumen rápido

- **Arquitectura**: PHP vanilla (sin frameworks), capas `Http → Application →
  Domain`, con `Infrastructure` implementando interfaces del `Domain`. Ver
  detalle completo en `lineamientos-desarrollo.md`.
- **Git**: nunca pushear directo a `main` ni `dev`. Rama de trabajo
  `feature/<branch>` desde `dev`, PR hacia `dev`, squash merge. Ver
  `ramas-pr-despliegues.md`.
- **Testing**: TDD obligatorio (Red → Green → Refactor), PHPUnit, patrón AAA,
  se prefieren Fakes sobre Mocks. Sin tests no hay PR aprobado. Ver
  `reglas-testing.md`.
