# Arquitectura del backend

La arquitectura del sistema está definida y documentada de forma centralizada en el sitio de documentación del proyecto:

- **Arquitectura general:** https://paw-2026-changuito-docs.vercel.app/docs/changuito-arquitectura
- **Modelo de objetos y diagrama entidad-relación:** https://paw-2026-changuito-docs.vercel.app/docs/modelo-de-objetos

El diagrama C4 cubre los cuatro niveles: contexto, contenedores, componentes de esta API y, en el nivel 4, el diagrama de objetos de tres operaciones clave más la secuencia del checkout.

Este archivo resume únicamente las decisiones que afectan de forma directa a este repositorio.

## Decisiones tomadas

**Separación en capas.** Cada módulo de negocio se organiza como `Controller → Service → Repository`. El Controller resuelve la petición HTTP, el Service concentra las reglas de negocio y el Repository es el único punto de acceso a la base de datos. Ningún componente fuera de un Repository abre una conexión.

**Punto de entrada único.** El Router (`src/routes`) recibe todas las solicitudes y las despacha al Controller del módulo correspondiente.

**Autenticación y autorización previas al despacho.** Un middleware verifica sesión y rol *antes* de que el Router llegue al Controller. Ningún Controller asume que quien llega ya está autenticado. Además del control por rol, cada Repository filtra por el identificador de usuario que está en la sesión, nunca por uno recibido en la petición.

**Acceso a datos con PDO y consultas parametrizadas**, sin excepción. Es el control principal contra inyección SQL.

**Optimizador sobre stored procedures.** El cálculo se apoya en dos procedimientos almacenados y la fórmula de costo logístico `P(n) = p0 + k·(n−1)^2.5`. El resultado de la optimización no se persiste: lo que se guarda es el carrito con las sucursales ya resueltas.

**Escritura transaccional del checkout.** `Pedido`, `OrdenComercio` e `ItemPedido` se insertan de forma atómica, con los precios congelados al momento de confirmar.

**Validación de transiciones de estado en el servidor.** La máquina de estados del pedido y de las órdenes de comercio se valida en el backend, no en los frontends.

**Trazabilidad.** Las cargas de catálogo quedan registradas en `LogAuditoriaCarga`, con su resultado y su detalle.

## Módulos de la API

Cuentas y acceso · Catálogo · Panel Supermercado · Pedidos · Panel Repartidor · Administración · Optimizador · Integración de catálogo.

El detalle de responsabilidades y entidades de cada módulo está en el nivel 3 del diagrama C4.

## Estado de implementación

La arquitectura corresponde a la segunda entrega y está completa a nivel de diseño. La implementación comienza en la tercera entrega, con el alcance acordado en [Alcance propuesto para la 3ra Entrega](https://paw-2026-changuito-docs.vercel.app/docs/propuesta-entrega-3).
