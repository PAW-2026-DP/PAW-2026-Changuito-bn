# Lineamientos de ramas, Pull Requests y despliegues — PAWPrints Bookstore

> Convenciones de Git que sigue todo el equipo: cómo se nombran las ramas, cómo se arma un PR, qué dispara cada despliegue y quién puede mergear qué.

## 1. Regla no negociable

**Nadie pushea directo a `main`, bajo ninguna circunstancia.** Tampoco a `dev`. Toda línea de código llega a esas ramas exclusivamente vía Pull Request aprobado. `main` y `dev` están protegidas en GitHub (branch protection rules) para que esto no dependa de la disciplina individual sino de la configuración del repo.

## 2. Ramas del repositorio

| Rama | Rol | Quién escribe directo |
|---|---|---|
| `main` | Código en producción. Siempre desplegable. | Nadie — solo vía PR desde `dev` (o `hotfix/*`, ver sección 7) |
| `dev` | Rama de integración. Refleja el ambiente DEV. | Nadie — solo vía PR desde `feature/*` |
| `feature/<branch>` | Una unidad de entrega: **una o varias features relacionadas** que se suben juntas en un solo PR (ver sección 3.1) | El/la desarrollador/a a cargo |
| `hotfix/<branch>` | Corrección urgente directo sobre producción (ver sección 7) | El/la desarrollador/a a cargo, con aprobación inmediata |

Todo trabajo nuevo **parte de `dev`, nunca de `main`**.

## 3. Convención de nombres de rama

```
feature/<branch>
```

- Prefijo `feature/` obligatorio y en minúsculas, seguido de `/`.
- `<branch>` en `kebab-case`, descriptivo y corto: qué hace, no quién lo hace ni el número de TP suelto.
- Sin espacios, sin mayúsculas, sin acentos.

```
feature/order-repository
feature/auth-middleware
feature/book-catalog-pagination
```

```
# Evitar
feature/JuanCambios
feature/fix2
tp3-final-v2
```

Para correcciones urgentes sobre producción, prefijo `hotfix/` con la misma convención (`hotfix/checkout-500-error`).

### 3.1 Una rama puede agrupar varias features

**No hace falta una rama y un PR por cada feature.** Cuando varias features
están relacionadas o se depende una de otra, se implementan en **una sola rama**
y se suben en **un único PR** a `dev`.

El criterio es de entrega, no de tamaño: si dos features se revisan mejor juntas
—porque una no funciona sin la otra, o porque separarlas obligaría a abrir PRs
que no se pueden probar solos— van en la misma rama. Si son independientes y
cada una se puede revisar y probar por separado, pueden ir en ramas distintas.

Lo que **no** se relaja es el desglose de commits: dentro de la rama, cada
feature tiene sus propios commits chicos y con significado (sección 4.1). Por eso
estos PR se mergean con **merge commit** y no con squash (sección 8): el
historial de `dev` conserva qué feature entró en cada commit.

El nombre de una rama agrupada describe el conjunto, no una feature suelta:

```
feature/etapa-2-3-auth-y-maestros
feature/checkout-y-cotizacion
```

## 4. Flujo de trabajo — paso a paso

1. Actualizar `dev` local: `git checkout dev && git pull origin dev`
2. Crear la rama de trabajo **desde `dev`**: `git checkout -b feature/order-repository`
3. Commitear en la rama con mensajes claros (ver sección 5.1)
4. Pushear la rama: `git push -u origin feature/order-repository`
5. Cuando la funcionalidad —o el conjunto de features de la rama, ver sección 3.1— está lista y probada localmente, abrir un **Pull Request hacia `dev`** (nunca hacia `main`)
6. El PR dispara el pipeline de CI/CD (sección 6). Si el pipeline falla, no se pide revisión hasta corregirlo.
7. Con el CI en verde y al menos una aprobación, se mergea a `dev` (sección 8)
8. Al mergear, el pipeline de GitHub Actions despliega automáticamente el contenido de `dev` al ambiente **DEV**

### 4.1 Mensajes de commit

Formato sugerido, estilo Conventional Commits:

```
<tipo>: <descripción corta en infinitivo>

feat: agregar repositorio de pedidos con PDO
fix: corregir validación de stock negativo
refactor: extraer OrderMapper desde PdoOrderRepository
docs: actualizar lineamientos de arquitectura
```

Tipos permitidos: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`.

En una rama que agrupa varias features (sección 3.1), **cada feature aporta sus
propios commits**: no se junta todo en un `feat:` gigante. Un commit debería
poder describirse en una línea y dejar la suite en verde por sí solo, así el
historial de `dev` sirve para leer qué entró y para revertir una parte sin
tocar el resto.

## 5. Reglas del Pull Request

### 5.1 Antes de abrirlo

- La rama está actualizada contra `dev` (rebase o merge de `dev` en la feature si `dev` avanzó mientras tanto)
- Corre localmente sin errores
- Cumple el checklist de `lineamientos-desarrollo.md` (capas, tipado, sin lógica de negocio en el controlador, etc.)

### 5.2 Título y descripción

- Título: mismo formato que un commit (`feat: agregar repositorio de pedidos`). Si el PR agrupa varias features, el título describe el conjunto (`feat: agregar autenticación y maestros de administración`).
- Descripción mínima:
  - **Qué** resuelve el PR (una o dos líneas)
  - **Cómo probarlo** (pasos concretos o request de ejemplo)
  - Referencia al TP/issue relacionado, si existe
- Si el PR agrupa varias features, sumar además:
  - **Qué features incluye**, en una lista, para que quien revisa sepa de antemano el alcance
  - **Qué decisiones conviene mirar con atención** (desvíos del plan, cambios sobre código ya mergeado, cualquier cosa discutible)

### 5.3 Revisión

- Mínimo **una aprobación** de otro integrante del equipo antes de mergear a `dev`.
- Quien abre el PR no se autoaprueba.
- Los comentarios de revisión que señalan algo del checklist de arquitectura (capas, nombres, patrones) son bloqueantes; los de estilo/preferencia personal no bloquean el merge.

## 6. CI/CD — GitHub Actions

- **Trigger:** todo PR abierto o actualizado con destino `dev` corre el workflow de CI (lint, tipado estático si está configurado, tests).
- **Merge a `dev`:** al mergear el PR, un segundo workflow de GitHub Actions despliega automáticamente el contenido de `dev` al ambiente **DEV**. No hay despliegue manual a DEV.
- Un PR con el pipeline en rojo no se mergea, aunque tenga aprobación.
- El pipeline vive versionado en `.github/workflows/` — cualquier cambio a los workflows sigue el mismo flujo de PR que el resto del código.

## 7. Pasaje de `dev` a `main` (propuesta a confirmar con el equipo)

Esto todavía no quedó explícito en la conversación inicial — se deja como propuesta de default hasta que el equipo lo confirme o ajuste:

- Cuando el contenido de `dev` está estabilizado y se decide promocionarlo, se abre un **Pull Request de `dev` hacia `main`**.
- Este PR requiere aprobación explícita (sugerido: del/la responsable técnico del equipo, no cualquier integrante) además de CI en verde.
- Al mergear ese PR, el workflow de GitHub Actions correspondiente despliega a producción.
- **Hotfixes**: una corrección urgente sobre `main` se hace en `hotfix/<branch>` partiendo de `main`, con PR directo a `main` y luego un segundo PR para traer ese fix de vuelta a `dev` (evita que `dev` y `main` diverjan).

> Si el equipo define un esquema distinto para este pasaje (por ejemplo, una rama `staging/` intermedia), esta sección se actualiza en consecuencia.

## 8. Reglas de merge

- Estrategia: **merge commit** al mergear un `feature/*` a `dev`, para conservar en el historial los commits por feature de la rama (sección 3.1 y 4.1). Sin esto, un PR agrupado colapsaría en un único commit y se perdería la trazabilidad de qué feature entró en cada cambio.
- El PR de `dev` a `main` (sección 7) también se mergea con **merge commit**, para conservar la trazabilidad de qué se promocionó.
- Contracara asumida: `dev` no tiene historial lineal. Se prefiere poder leer y revertir feature por feature antes que un `git log` plano.
- Ninguna rama `feature/*` queda viva después de mergeada: se borra al mergear (GitHub lo puede hacer automáticamente).
- Conflictos de merge se resuelven en la rama `feature/*`, nunca editando directo en `dev`.

## 9. Checklist antes de pedir revisión

- [ ] La rama arrancó desde `dev` actualizado y tiene el prefijo `feature/`
- [ ] Los commits están desglosados por feature, no acumulados en uno solo
- [ ] Si el PR agrupa varias features, la descripción lista cuáles son
- [ ] CI en verde
- [ ] Descripción del PR completa (qué + cómo probarlo)
- [ ] Sin cambios directos a `main` ni a `dev`
- [ ] Si el PR toca `.github/workflows/`, el cambio fue revisado con el mismo cuidado que el código de la app
