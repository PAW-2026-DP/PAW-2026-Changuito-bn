# Lineamientos de desarrollo — PAWPrints Bookstore

> Convenciones de nombres, semántica y patrones de diseño para todo el equipo.
> Alcance: backend PHP (vanilla, sin frameworks). Objetivo: que el código de
> cualquier integrante se lea como si lo hubiera escrito una sola persona.

## 1. Principios generales

1. **PHP vanilla, sin frameworks.** Sin Laravel, Symfony ni micro-frameworks. Se permite Composer únicamente para autoload PSR-4 y, si hace falta, librerías puntuales sin efectos de framework (ej. un validador). Ante la duda, preguntar antes de sumar una dependencia nueva.
2. **`declare(strict_types=1);` en todo archivo `.php` nuevo.** Sin excepciones.
3. **Tipado explícito siempre**: parámetros, retornos y propiedades tipadas. Si un valor puede faltar, se tipa `?Tipo` o se devuelve un tipo unión explícito — nunca se deja sin tipo "para simplificar".
4. **Cada clase tiene una sola razón para cambiar.** Si al describir qué hace una clase usás la palabra "y" más de una vez, probablemente son dos clases.
5. **El dominio no importa nada de infraestructura.** Ninguna clase en `src/Domain/` puede tener un `use` de PDO, de una clase HTTP, ni de ninguna librería externa.

## 2. Estructura de carpetas

> **Nota:** el árbol de abajo es un ejemplo ilustrativo de cómo se organizan las capas, no la estructura real del proyecto. Los archivos, clases y nombres concretos van a salir de la spec de cada TP/módulo — acá lo que importa es **en qué carpeta va cada tipo de clase**, no que exista literalmente un `Book.php` o un `OrderController.php`.

```
src/
  Http/
    Router.php
    Request.php
    Response.php
    Pipeline.php
    Middleware.php
    Handler.php
    Middleware/
      AuthMiddleware.php
      CorsMiddleware.php
    Controller/
      BookController.php
      OrderController.php
  Application/
    BookService.php
    OrderService.php
    Command/
      PlaceOrderCommand.php
  Domain/
    Book.php
    Order.php
    OrderLine.php
    BookRepositoryInterface.php
    OrderRepositoryInterface.php
  Infrastructure/
    PdoBookRepository.php
    PdoOrderRepository.php
    Mapper/
      BookMapper.php
      OrderMapper.php
    UnitOfWork.php
config/
  container.php
public/
  index.php
```

Regla de ubicación: **la carpeta se elige por capa, no por feature.** `OrderController`, `OrderService` y `Order` conviven en carpetas distintas aunque compartan dominio — eso es intencional, refuerza la dirección de dependencia (sección 4).

## 3. Convenciones de nombres

### 3.1 Archivos y clases

| Elemento | Convención | Ejemplo |
|---|---|---|
| Archivo de clase | `PascalCase.php`, un archivo = una clase, nombre de archivo = nombre de clase | `OrderService.php` |
| Clase / interfaz / enum | `PascalCase` | `class OrderService`, `interface OrderRepositoryInterface` |
| Interfaz | Nombre + sufijo `Interface` | `OrderRepositoryInterface`, no `IOrderRepository` |
| Trait | Sufijo `Trait` | `TimestampableTrait` |
| Namespace | `PascalCase`, refleja la ruta desde `src/` | `App\Domain\Order` |
| Método / función | `camelCase`, verbo en infinitivo | `findById()`, `placeOrder()` |
| Variable / propiedad | `camelCase`, sustantivo | `$customerId`, `$orderLines` |
| Constante / caso de `enum` | `SCREAMING_SNAKE_CASE` | `const MAX_ITEMS_PER_ORDER = 50;` |
| Columnas de base de datos | `snake_case` (se traduce en el Mapper, nunca en la entidad) | `customer_id`, `created_at` |

### 3.2 Semántica por tipo de clase

El **sufijo de la clase dice a qué capa pertenece y qué puede hacer**. No se mezclan:

| Sufijo | Capa | Responsabilidad | Prohibido |
|---|---|---|---|
| `...Controller` | Http | Traducir HTTP ↔ comando/DTO de aplicación | SQL, reglas de negocio |
| `...Middleware` | Http | Concern transversal (auth, CORS, logging) | Lógica de negocio del endpoint |
| `...Service` | Application | Orquestar un caso de uso completo | Conocer `$_SERVER`, headers, JSON |
| `...Command` | Application | DTO inmutable de entrada a un Service | Lógica, métodos que no sean getters |
| `...Repository` (interfaz) | Domain | Contrato de persistencia orientado al dominio | Exponer SQL o nombres de columnas |
| `Pdo...Repository` | Infrastructure | Implementación concreta de un `...RepositoryInterface` | Reglas de negocio |
| `...Mapper` | Infrastructure | Traducir fila ↔ entidad | Validar reglas de negocio |
| `UnitOfWork` | Infrastructure | Agrupar escrituras de varios repositorios en una transacción | Contener lógica de negocio |

**Nombres de métodos de repositorio**: siempre en términos de dominio, nunca de tabla o de query.

```php
// Bien — dice qué representa, no cómo se busca
$repository->findById($id);
$repository->findActiveByCustomer($customerId);

// Mal — expone la forma de la query en el nombre
$repository->selectWhereStatusEqualsActive($customerId);
```

### 3.3 Verbos estándar

Usar siempre el mismo verbo para la misma operación en todo el proyecto:

- `find...` → puede devolver `null` (ej. `findById(): ?Order`)
- `get...` → se espera que exista; si no existe, lanza excepción
- `save()` → crea o actualiza (upsert), no distinguir `insert`/`update` en la interfaz del repositorio
- `place`, `cancel`, `confirm`, `ship` → verbos de negocio en servicios y entidades (`Order::cancel()`, `OrderService::place()`), no `process()` ni `handle()`, que no dicen nada del dominio

## 4. Arquitectura en capas (resumen)

Dirección de dependencia única: **Http → Application → Domain**, e **Infrastructure implementa** interfaces del Domain (nunca al revés).

| Capa | Ejemplo de clase | Depende de |
|---|---|---|
| Http | `OrderController` | `Application` (Services, Commands) |
| Application | `OrderService` | `Domain` (entidades, interfaces) |
| Domain | `Order`, `OrderRepositoryInterface` | Nada externo |
| Infrastructure | `PdoOrderRepository`, `OrderMapper` | `Domain` (implementa sus interfaces) |

Flujo de una request: `Router → Pipeline de middlewares → Controller → Service → Repository (interfaz) → Infraestructura (PDO)`. Ningún paso salta capas: un controlador **nunca** llama directo a un repositorio, un middleware **nunca** llama a un servicio.

## 5. Patrones de diseño obligatorios

Estos patrones no son opcionales por proyecto ni por preferencia individual — son la base común del equipo.

### 5.1 Front controller + Router
Un único punto de entrada (`public/index.php`) que arma el contenedor, registra rutas y despacha. El router solo resuelve `método + path → handler`.

### 5.2 Pipeline de middlewares
Concerns transversales (auth, CORS, logging) implementan `Middleware` y se componen en un `Pipeline`. Un middleware nuevo no debe requerir tocar ningún controlador existente.

### 5.3 Repository + interfaz en el dominio
Toda persistencia pasa por una interfaz definida en `Domain/`. La implementación concreta (`Pdo...Repository`) vive en `Infrastructure/` y se cablea en `config/container.php`. Esto es lo que permite testear servicios con un repositorio en memoria, sin base de datos.

### 5.4 Mapper / Hydrator
Ninguna entidad de dominio tiene un método `fromRow()` ni conoce nombres de columnas. Esa traducción vive en una clase `...Mapper` en `Infrastructure/`:

```php
final class OrderMapper
{
    public static function toEntity(array $row): Order { /* ... */ }
    public static function toRow(Order $order): array { /* ... */ }
}
```

### 5.5 Unit of Work
Cuando un caso de uso escribe en más de un repositorio (ej. guardar un pedido y descontar stock), esa operación se envuelve en una transacción explícita, inyectada al servicio:

```php
$this->uow->transactional(function () use ($order, $product) {
    $this->orders->save($order);
    $this->books->save($product);
});
```

No se permite hacer dos `save()` de repositorios distintos "sueltos" dentro de un servicio sin pasar por el Unit of Work.

### 5.6 Commands / DTOs para entrada de servicios
Los servicios no reciben arrays sueltos ni el `Request` HTTP. Reciben un objeto `...Command` inmutable (propiedades `readonly`) armado por el controlador.

### 5.7 Cuándo NO aplicar un patrón
Un repositorio o mapper de una sola línea sin ninguna variación real (una tabla simple, un solo tipo de consulta) no necesita las cinco capas completas. Se agrega la capa cuando resuelve un problema concreto, no por costumbre. Ante la duda, se discute en revisión de código, no se decide en soledad.

## 6. Estilo de código

- Se sigue **PSR-12** como base de formato (indentación de 4 espacios, llaves de clase/método en línea nueva, un `use` por línea).
- Visibilidad explícita siempre: `public`, `private` o `protected` — nunca omitida.
- Propiedades inmutables se declaran `readonly` y se construyen por constructor (constructor promotion permitido y preferido).
- Comparaciones estrictas (`===`, `!==`) salvo que exista una razón documentada para lo contrario.
- Un método no debería superar ~25-30 líneas. Si las supera, es señal de que está haciendo más de una cosa.
- Comentarios explican **por qué**, no **qué** — el código ya dice qué hace si los nombres son buenos.

## 7. Manejo de errores

- Las reglas de negocio violadas se comunican con **excepciones de dominio propias** (`InvalidOrderException`, no `\Exception` genérica).
- El único lugar donde una excepción de dominio se traduce a código HTTP es el **controlador** (`catch` → `Response` con el status correspondiente). Los servicios no conocen códigos HTTP.
- Nunca se silencia una excepción con un `catch` vacío. Como mínimo, se loguea.

## 8. Checklist antes de abrir un PR

- [ ] `declare(strict_types=1);` presente
- [ ] Ningún `use` de infraestructura dentro de `Domain/`
- [ ] El controlador no contiene SQL ni reglas de negocio
- [ ] El repositorio nuevo tiene su interfaz en `Domain/` y su implementación en `Infrastructure/`
- [ ] Si la operación escribe en más de un repositorio, usa `UnitOfWork`
- [ ] Nombres de métodos siguen los verbos estándar de la sección 3.3
- [ ] Se puede testear el servicio nuevo sin levantar base de datos (repositorio en memoria)

## 9. Antipatrones que se rechazan en revisión

- **Controlador gordo**: validación de negocio, SQL o lógica de dominio dentro del método del controlador.
- **Repositorio anémico**: un método por cada query específica de una pantalla en vez de operaciones con significado de dominio.
- **Servicio que conoce HTTP**: un método de `...Service` que recibe `Request` o arma JSON.
- **Entidad con `fromRow()`**: mezcla el dominio con el esquema de la base de datos — esa lógica va al Mapper.
- **Capas de más sin necesidad**: armar Repository + Mapper + Service para un CRUD trivial de una tabla sin ninguna regla — evaluar caso a caso, no por plantilla.
