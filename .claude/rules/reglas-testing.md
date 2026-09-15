# Lineamientos de testing — PAWPrints Bookstore

> Reglas de testing que se aplican **siempre**, sin excepción, en todo desarrollo del proyecto. Se complementa con `lineamientos-desarrollo.md` (arquitectura y patrones) y `ramas-pr-despliegues.md` (cuándo corre el CI y qué bloquea un merge).

## 1. Regla no negociable

**TDD es obligatorio.** No se escribe código de producción sin un test que falle primero. Todo desarrollo (feature, bugfix, refactor) se automatiza: si no tiene test automatizado, no está terminado, aunque funcione al probarlo a mano. Esto no es una preferencia de estilo — es lo que le permite al pipeline de `ramas-pr-despliegues.md` ejecutar la suite en cada PR y bloquear un merge si algo se rompe. Sin tests, el CI/CD de esa sección no tiene nada real que ejecutar.

## 2. El ciclo TDD (Red → Green → Refactor)

1. **Red**: se escribe el test para el comportamiento que todavía no existe. Corre y falla (si pasa de entrada, el test no está probando nada nuevo).
2. **Green**: se escribe el código **mínimo** para que ese test pase. No se adelanta funcionalidad que el test no pide.
3. **Refactor**: con el test en verde como red de seguridad, se limpia el código (nombres, duplicación, capas) sin cambiar el comportamiento. Se vuelve a correr el test.

Se repite este ciclo por cada unidad de comportamiento, no por archivo ni por clase completa de una sola vez.

## 3. Pirámide de testing aplicada a las capas

La arquitectura en capas de `lineamientos-desarrollo.md` define qué tipo de test corresponde a cada una — no todo se testea igual:

| Capa | Tipo de test | Qué se prueba | Con qué |
|---|---|---|---|
| Domain (`Order`, `Book`, entidades) | **Unitario** | Reglas de negocio puras: invariantes, cálculos, transiciones de estado | PHPUnit, sin dobles — es lógica pura |
| Application (`...Service`) | **Unitario** | Orquestación de un caso de uso | PHPUnit + **Fakes** de los repositorios (in-memory), nunca base de datos real |
| Infrastructure (`Pdo...Repository`, `...Mapper`) | **Integración** | Que el SQL/mapeo funciona contra una base real | PHPUnit contra una base de datos de test (SQLite en memoria o MySQL de test), nunca mocks |
| Http (`...Controller`, `Router`, `Middleware`) | **Integración/funcional** | Que la request HTTP produce el status y payload correctos | PHPUnit simulando el `Request`, con los servicios reales o fakeados |
| Flujo completo | **End-to-end** (mínimo indispensable) | Los 2-3 flujos críticos del negocio de punta a punta | Solo para lo que de verdad no se puede validar por capas — no se abusa de este nivel, es el más lento y frágil |

La proporción esperada es: **muchos** tests unitarios de dominio y servicios, **algunos** de integración de repositorios/HTTP, **pocos** end-to-end. Si el equipo se encuentra escribiendo más e2e que unitarios, es señal de que la lógica no está suficientemente aislada en el dominio.

## 4. Herramienta

**PHPUnit**, instalado vía Composer solo como dependencia de desarrollo (`require-dev`) — no rompe la regla de "PHP vanilla, sin frameworks" de `lineamientos-desarrollo.md`, porque PHPUnit es un framework de testing, no un framework de aplicación: el código de `src/` no depende de él en ningún punto.

```bash
composer require --dev phpunit/phpunit
```

## 5. Estructura y convenciones de nombres

```
tests/
  Unit/
    Domain/
      OrderTest.php
      BookTest.php
    Application/
      OrderServiceTest.php
  Integration/
    Infrastructure/
      PdoOrderRepositoryTest.php
    Http/
      OrderControllerTest.php
  Fakes/
    InMemoryOrderRepository.php
    InMemoryBookRepository.php
```

- Un archivo de test por clase de producción: `OrderService.php` → `OrderServiceTest.php`, mismo namespace relativo.
- Clase de test: `PascalCase` + sufijo `Test`.
- Método de test: `camelCase`, describe el comportamiento en lenguaje natural, no el nombre del método probado:

```php
// Bien — describe el comportamiento y el caso
public function test_lanza_excepcion_si_el_producto_no_existe(): void {}
public function test_calcula_el_total_incluyendo_descuento_por_cupon(): void {}

// Mal — repite el nombre del método, no dice nada del caso
public function test_place(): void {}
public function test_placeOrder2(): void {}
```

## 6. Patrón AAA (Arrange–Act–Assert)

Todo test se estructura en tres bloques, separados y en ese orden — sin mezclarlos:

```php
final class OrderServiceTest extends TestCase
{
    public function test_lanza_excepcion_si_el_producto_no_existe(): void
    {
        // Arrange
        $products = new InMemoryProductRepository();
        $orders   = new InMemoryOrderRepository();
        $service  = new OrderService($orders, $products, new NullEventDispatcher());

        $command = new PlaceOrderCommand(
            customerId: 'cust-1',
            items: [['productId' => 'no-existe', 'qty' => 1]],
        );

        // Assert (se declara antes del Act cuando se espera una excepción)
        $this->expectException(InvalidOrderException::class);

        // Act
        $service->place($command);
    }

    public function test_guarda_el_pedido_cuando_los_productos_son_validos(): void
    {
        // Arrange
        $products = new InMemoryProductRepository([Product::create('p1', 'Libro', 1500)]);
        $orders   = new InMemoryOrderRepository();
        $service  = new OrderService($orders, $products, new NullEventDispatcher());
        $command  = new PlaceOrderCommand('cust-1', [['productId' => 'p1', 'qty' => 2]]);

        // Act
        $order = $service->place($command);

        // Assert
        self::assertSame('cust-1', $order->customerId());
        self::assertNotNull($orders->findById($order->id()));
    }
}
```

Una sola razón de fallo por test. Si un test necesita más de un `assert` de conceptos distintos para verificar "una cosa", probablemente son dos tests.

## 7. Test doubles: cuál usar y cuándo

| Tipo | Qué es | Cuándo se usa acá |
|---|---|---|
| **Fake** | Implementación real y funcional, simplificada (ej. repositorio en memoria con un array) | Es el default para probar Services: implementa la interfaz de dominio de verdad, sin base de datos |
| **Stub** | Devuelve una respuesta fija, no tiene lógica | Para simular una dependencia externa (ej. un cliente HTTP que siempre devuelve el mismo JSON) |
| **Mock** | Verifica que se llamó a un método con ciertos argumentos | Solo cuando lo que se quiere probar es la interacción en sí (ej. "se despachó el evento `OrderPlaced`"), no el resultado |

**Regla del proyecto: se prefiere Fake sobre Mock.** Un repositorio en memoria (`InMemoryOrderRepository implements OrderRepositoryInterface`) da más confianza que un mock, porque es una implementación real de la interfaz — si la interfaz cambia, el Fake deja de compilar y avisa. Los Fakes viven en `tests/Fakes/` y se reutilizan entre tests, no se reescriben por archivo.

```php
final class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function findById(string $id): ?Order
    {
        return $this->orders[$id] ?? null;
    }

    public function save(Order $order): void
    {
        $this->orders[$order->id()] = $order;
    }
}
```

## 8. Qué es obligatorio cubrir

No se pide un porcentaje de cobertura arbitrario — se pide cubrir esto, siempre:

- Toda regla de negocio nueva o modificada en `Domain/` (entidades) y `Application/` (servicios)
- Todo camino de error de negocio (excepciones de dominio): cada `throw` tiene al menos un test que lo dispara
- Los casos límite explícitos del enunciado del TP (cantidades en cero, stock insuficiente, IDs inexistentes)
- Los repositorios PDO nuevos, con un test de integración contra base de test

No es obligatorio (y no se testea por costumbre):

- Getters simples sin lógica
- Código de terceros (PDO, la extensión JSON, etc.)
- El `Router`/`Pipeline` en detalle si ya está cubierto por los tests de integración de `Http/`

## 9. Integración con el pipeline (`ramas-pr-despliegues.md`)

- El workflow de CI de GitHub Actions que corre en cada PR contra `dev` incluye un step de `phpunit` — no es opcional ni un step aparte que se pueda saltear.
- **Un PR con tests en rojo, o sin tests para el código nuevo, no se aprueba**, independientemente de que la funcionalidad "funcione" probada a mano.
- Ejemplo de step en el workflow:

```yaml
- name: Run test suite
  run: vendor/bin/phpunit --colors=always
```

- Si un test queda marcado `markTestSkipped()` o `@group slow` para no correr en cada PR, se documenta por qué en el propio test y se corre igual antes de cada pasaje a `main`.

## 10. Checklist antes de abrir el PR

- [ ] El código nuevo se escribió con TDD (test primero, no agregado después "para cumplir")
- [ ] `vendor/bin/phpunit` corre en verde localmente antes de pushear
- [ ] Cada regla de negocio nueva tiene al menos un test unitario en `Domain/` o `Application/`
- [ ] Cada excepción de dominio nueva tiene un test que la dispara
- [ ] Los tests de servicios usan Fakes de `tests/Fakes/`, no acceden a una base de datos real
- [ ] Si se agregó un repositorio PDO nuevo, tiene su test de integración correspondiente
- [ ] Los nombres de los métodos de test describen el comportamiento, no repiten el nombre del método probado
