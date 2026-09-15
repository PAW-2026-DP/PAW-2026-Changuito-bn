# Changuito — Backend

Backend de Changuito: plataforma que permite armar pedidos de compra
optimizados entre supermercados, con roles de cliente, supermercado,
repartidor y administrador.

## Estado actual

Proyecto en etapa inicial: la estructura de carpetas (`src/`, `test/`) está
creada pero todavía no hay código PHP, dependencias (Composer) ni base de
datos configurados. La arquitectura definitiva se implementa siguiendo los
lineamientos importados abajo.

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
