# Lineamientos de ramas, Pull Requests y despliegues — PAWPrints Bookstore

> Convenciones de Git que sigue todo el equipo: cómo se nombran las ramas, cómo se arma un PR, qué dispara cada despliegue y quién puede mergear qué.

## 1. Regla no negociable

**Nadie pushea directo a `main`, bajo ninguna circunstancia.** Tampoco a `dev`. Toda línea de código llega a esas ramas exclusivamente vía Pull Request aprobado. `main` y `dev` están protegidas en GitHub (branch protection rules) para que esto no dependa de la disciplina individual sino de la configuración del repo.

## 2. Ramas del repositorio

| Rama | Rol | Quién escribe directo |
|---|---|---|
| `main` | Código en producción. Siempre desplegable. | Nadie — solo vía PR desde `dev` (o `hotfix/*`, ver sección 7) |
| `dev` | Rama de integración. Refleja el ambiente DEV. | Nadie — solo vía PR desde `feature/*` |
| `feature/<branch>` | Una unidad de trabajo (una historia, un TP, un bugfix no urgente) | El/la desarrollador/a a cargo |
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

## 4. Flujo de trabajo — paso a paso

1. Actualizar `dev` local: `git checkout dev && git pull origin dev`
2. Crear la rama de trabajo **desde `dev`**: `git checkout -b feature/order-repository`
3. Commitear en la rama con mensajes claros (ver sección 5.1)
4. Pushear la rama: `git push -u origin feature/order-repository`
5. Cuando la funcionalidad está lista y probada localmente, abrir un **Pull Request hacia `dev`** (nunca hacia `main`)
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

## 5. Reglas del Pull Request

### 5.1 Antes de abrirlo

- La rama está actualizada contra `dev` (rebase o merge de `dev` en la feature si `dev` avanzó mientras tanto)
- Corre localmente sin errores
- Cumple el checklist de `lineamientos-desarrollo.md` (capas, tipado, sin lógica de negocio en el controlador, etc.)

### 5.2 Título y descripción

- Título: mismo formato que el commit principal (`feat: agregar repositorio de pedidos`)
- Descripción mínima:
  - **Qué** resuelve el PR (una o dos líneas)
  - **Cómo probarlo** (pasos concretos o request de ejemplo)
  - Referencia al TP/issue relacionado, si existe

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

- Estrategia: **squash merge** al mergear un `feature/*` a `dev` — un commit limpio por PR en el historial de `dev`.
- El PR de `dev` a `main` (sección 7) se mergea con **merge commit** (no squash), para conservar la trazabilidad de qué se promocionó.
- Ninguna rama `feature/*` queda viva después de mergeada: se borra al mergear (GitHub lo puede hacer automáticamente).
- Conflictos de merge se resuelven en la rama `feature/*`, nunca editando directo en `dev`.

## 9. Checklist antes de pedir revisión

- [ ] La rama arrancó desde `dev` actualizado y tiene el prefijo `feature/`
- [ ] CI en verde
- [ ] Descripción del PR completa (qué + cómo probarlo)
- [ ] Sin cambios directos a `main` ni a `dev`
- [ ] Si el PR toca `.github/workflows/`, el cambio fue revisado con el mismo cuidado que el código de la app
