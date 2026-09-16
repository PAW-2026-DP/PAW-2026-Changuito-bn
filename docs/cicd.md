# CI/CD — guía paso a paso

Pipeline de integración y despliegue continuo para el backend de Changuito.
Complementa `.claude/rules/ramas-pr-despliegues.md`: acá está la configuración
concreta (GCP, GitHub Actions, rulesets) que hace ejecutable lo que esas reglas
dan por sentado.

## 1. Resumen del flujo

```
PR -> dev/main        push a dev
   |                      |
   v                      v
.github/workflows/    .github/workflows/
   ci.yml               deploy-dev.yml
   |                      |
   - composer validate    - build imagenes (app + nginx)
   - phpunit               - push a Artifact Registry
   - docker build           - deploy por SSH a la VM (IAP)
     (sin push)              - smoke test
```

- **Entrega**: las imágenes se compilan en GitHub Actions y se publican en
  **Artifact Registry**. La VM nunca compila: solo hace `docker compose pull`.
- **Auth GCP**: **Workload Identity Federation** (OIDC). No hay ninguna clave
  de service account guardada como secret.
- **Ambientes**: por ahora solo **DEV**, sobre la VM de Compute Engine ya
  provisionada. El pasaje a producción (sección 7) queda fuera de alcance
  hasta que exista una VM/ambiente de PROD — no se escribe workflow para eso.

### Por qué hay una imagen de nginx propia

En desarrollo, `docker-compose.yml` monta el repo como bind mount y nginx lee
`public/` directamente del filesystem. En la VM no hay código montado: la
imagen de producción de PHP lleva el código adentro del contenedor `app`, pero
nginx necesita ver `public/` para servir estáticos y rutear al front
controller. Por eso `docker/nginx/Dockerfile` arma una imagen de nginx que
copia `public/` y `default.conf` en build time. Se descartó compartir un
volumen escrito por el contenedor `app`: es más frágil (puede dejar archivos
viejos de un deploy anterior).

### Orden general (dónde se ejecuta cada parte)

Todo esto se hace **una sola vez**, en este orden, y cada paso dice dónde
correrlo — es la parte que más confunde la primera vez:

| # | Qué | Dónde |
|---|---|---|
| 0 | Instalar y configurar `gcloud` | 💻 tu compu |
| 3.1–3.4 | Crear recursos en GCP (APIs, Artifact Registry, service account, WIF) | 💻 tu compu, con `gcloud` |
| 3.5 | Instalar Docker y preparar carpeta en la VM | 🖥️ dentro de la VM (por SSH) |
| 3.6 | Reglas de firewall | 💻 tu compu, con `gcloud` |
| 4 | Cargar secrets | 🌐 navegador, github.com |
| 5 | Configurar rulesets | 🌐 navegador, github.com |
| 6 | Probar: crear rama, PR, mergear | 💻 tu compu + 🌐 github.com |

## 0. Antes de empezar: instalar y configurar `gcloud`

Todos los comandos de la sección 3 (salvo 3.5, que es dentro de la VM) se
corren **en tu terminal local**, no dentro de un contenedor Docker del
proyecto — son comandos contra tu cuenta de Google Cloud, no contra la app.

**Instalar el CLI** (en Arch, vía AUR — o el instalador oficial si preferís
no usar un paquete de AUR):

```bash
yay -S google-cloud-cli
# o, sin AUR:
curl https://sdk.cloud.google.com | bash
exec -l $SHELL
```

**Autenticarte** (abre el navegador para loguearte con la cuenta que tiene
acceso al proyecto de GCP):

```bash
gcloud auth login
```

**Encontrar los datos de tu proyecto y tu VM**, si no los tenés a mano:

```bash
# Lista tus proyectos y sus IDs
gcloud projects list

# Una vez que sepas el PROJECT_ID, lista las VMs de ese proyecto
# (te da NOMBRE, ZONA e IP externa en una sola línea)
gcloud config set project <PROJECT_ID>
gcloud compute instances list
```

Si nunca usaste `gcloud` contra ese proyecto, también podés ver lo mismo en
la consola web: [console.cloud.google.com](https://console.cloud.google.com)
→ selector de proyecto arriba a la izquierda (ahí está el `PROJECT_ID`) →
menú ☰ → **Compute Engine → Instancias de VM** (ahí están nombre, zona e IP).

Con esos tres datos (`PROJECT_ID`, `VM_NAME`, `VM_ZONE`) ya podés seguir con
la sección 3.

## 2. Archivos que agrega este cambio

| Archivo | Rol |
|---|---|
| `.github/workflows/ci.yml` | Corre en cada PR hacia `dev`/`main`: tests + build de verificación. |
| `.github/workflows/deploy-dev.yml` | Corre en cada push a `dev` (o sea, al mergear un PR): build, push y deploy a la VM de DEV. |
| `docker/nginx/Dockerfile` | Imagen de nginx con `public/` incluido, para el deploy. |
| `deploy/docker-compose.dev.yml` | Compose que corre en la VM. Se copia ahí en cada deploy — no se edita a mano en la VM. |
| `deploy/.env.example` | Plantilla de las variables que necesita la VM. El `.env` real con credenciales vive solo en la VM. |

## 3. Setup en GCP (una sola vez)

💻 **Todo en esta sección (salvo 3.5) se corre en tu terminal local**, con el
`gcloud` que instalaste y autenticaste en el paso 0. Requiere permisos de
owner/editor en el proyecto — si el proyecto es del equipo y no sos vos
quien lo administra, pedile a quien lo administra que corra esta sección o
que te dé el rol `roles/owner`/`roles/editor` temporalmente.

Pegá este bloque primero en tu terminal (con tus valores reales) — las
variables quedan disponibles para todos los comandos que siguen, mientras no
cierres la terminal:

```bash
export PROJECT_ID=<tu-project-id>
export REGION=southamerica-east1       # o la región que uses
export VM_NAME=<nombre-vm-existente>
export VM_ZONE=<zona-de-la-vm>
export REPO_GH=PAW-2026-DP/PAW-2026-Changuito-bn

gcloud config set project "$PROJECT_ID"
```

### 3.1 Habilitar APIs

```bash
gcloud services enable \
  artifactregistry.googleapis.com \
  iamcredentials.googleapis.com \
  iap.googleapis.com \
  compute.googleapis.com
```

### 3.2 Repositorio de Artifact Registry

```bash
gcloud artifacts repositories create changuito \
  --repository-format=docker \
  --location="$REGION" \
  --description="Imagenes de Changuito backend"
```

### 3.3 Service account que va a usar GitHub Actions

```bash
gcloud iam service-accounts create github-deployer \
  --display-name="GitHub Actions - Changuito deploy"

export SA_EMAIL="github-deployer@${PROJECT_ID}.iam.gserviceaccount.com"

gcloud projects add-iam-policy-binding "$PROJECT_ID" \
  --member="serviceAccount:${SA_EMAIL}" \
  --role="roles/artifactregistry.writer"

gcloud projects add-iam-policy-binding "$PROJECT_ID" \
  --member="serviceAccount:${SA_EMAIL}" \
  --role="roles/iap.tunnelResourceAccessor"

gcloud projects add-iam-policy-binding "$PROJECT_ID" \
  --member="serviceAccount:${SA_EMAIL}" \
  --role="roles/compute.instanceAdmin.v1"

gcloud projects add-iam-policy-binding "$PROJECT_ID" \
  --member="serviceAccount:${SA_EMAIL}" \
  --role="roles/iam.serviceAccountUser"
```

### 3.4 Workload Identity Federation (OIDC con GitHub)

```bash
gcloud iam workload-identity-pools create github-pool \
  --location="global" \
  --display-name="GitHub Actions"

gcloud iam workload-identity-pools providers create-oidc github-provider \
  --location="global" \
  --workload-identity-pool="github-pool" \
  --display-name="GitHub OIDC" \
  --attribute-mapping="google.subject=assertion.sub,attribute.repository=assertion.repository" \
  --attribute-condition="assertion.repository == '${REPO_GH}'" \
  --issuer-uri="https://token.actions.githubusercontent.com"
```

La `attribute-condition` es la parte crítica: sin ella, **cualquier repo de
GitHub** (de cualquier cuenta) podría generar un token que impersone esta
service account. Verificar que el valor de `REPO_GH` sea exactamente
`owner/repo` tal como aparece en la URL de GitHub.

```bash
export PROJECT_NUMBER=$(gcloud projects describe "$PROJECT_ID" --format='value(projectNumber)')

gcloud iam service-accounts add-iam-policy-binding "$SA_EMAIL" \
  --role="roles/iam.workloadIdentityUser" \
  --member="principalSet://iam.googleapis.com/projects/${PROJECT_NUMBER}/locations/global/workloadIdentityPools/github-pool/attribute.repository/${REPO_GH}"

# Este es el valor para el secret GCP_WIF_PROVIDER:
gcloud iam workload-identity-pools providers describe github-provider \
  --location="global" \
  --workload-identity-pool="github-pool" \
  --format="value(name)"
```

### 3.5 Preparar la VM

🖥️ **Esta parte es dentro de la VM**, no en tu compu. Para entrar, desde tu
terminal local (con las variables `VM_NAME`/`VM_ZONE` del bloque `export` de
arriba, al principio de la sección 3, ya seteadas):

```bash
gcloud compute ssh "$VM_NAME" --zone "$VM_ZONE" --tunnel-through-iap
```

`--tunnel-through-iap` es porque el firewall (paso 3.6) solo va a permitir
SSH a través de IAP, no directo por internet — si todavía no corriste 3.6
puede fallar; en ese caso corré 3.6 primero. La primera vez que uses este
comando `gcloud` te va a pedir generar una clave SSH: aceptá con Enter.

**A partir de acá, los comandos son dentro de la VM** (el prompt de tu
terminal va a cambiar, algo como `tu-usuario@nombre-vm:~$`):

```bash
# Docker + compose plugin (Debian/Ubuntu, que es la imagen base mas comun)
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker "$USER"
# el grupo nuevo no aplica a la sesion actual: salí y volvé a entrar por SSH
# (Ctrl+D y repetir el comando `gcloud compute ssh` de arriba) antes de seguir

sudo mkdir -p /opt/changuito
sudo chown "$USER":"$USER" /opt/changuito
cd /opt/changuito

# .env real, a partir de la plantilla deploy/.env.example del repo.
# Se escribe a mano (o con este heredoc) porque la VM no necesita clonar el
# repo: solo corre imagenes ya compiladas, no compila nada.
cat > .env <<'ENV'
IMAGE_REPO=REGION-docker.pkg.dev/PROJECT_ID/changuito
IMAGE_TAG=dev
DB_DATABASE=changuito
DB_USERNAME=changuito
DB_PASSWORD=<generar uno fuerte>
DB_ROOT_PASSWORD=<generar uno fuerte>
ENV
# Reemplazar REGION y PROJECT_ID por los valores reales antes de guardar
# (no quedan como variables de shell acá, son texto literal en el archivo).
```

**Permiso para hacer `pull` de Artifact Registry.** Esto es lo que más
suele fallar la primera vez: por default, una VM de Compute Engine se crea
con la service account "Compute Engine default" y **sin** acceso a Artifact
Registry. Verificalo (todavía dentro de la VM):

```bash
gcloud auth configure-docker REGION-docker.pkg.dev --quiet
docker pull REGION-docker.pkg.dev/PROJECT_ID/changuito/app:dev
```

Si el `pull` da `permission denied` o `403`, hay que darle acceso a la
service account **de la VM** (no a `github-deployer`, que es la de GitHub
Actions). Desde tu compu (💻, no la VM):

```bash
# Ver que service account usa la instancia
gcloud compute instances describe "$VM_NAME" --zone "$VM_ZONE" \
  --format="value(serviceAccounts[0].email)"

# Darle permiso de lectura sobre el repo de imagenes
gcloud projects add-iam-policy-binding "$PROJECT_ID" \
  --member="serviceAccount:<el-email-de-arriba>" \
  --role="roles/artifactregistry.reader"
```

Y volver a intentar el `docker pull` de arriba desde la VM — como todavía no
se publicó ninguna imagen (eso pasa en el paso 6), este primer `pull` va a
fallar con "not found" en vez de "permission denied" una vez que el permiso
esté bien: ese es el resultado esperado en este punto, confirma que el
permiso funciona y que falta la imagen, que llega en el primer deploy.

El primer `docker compose up -d` lo dispara el primer deploy automático — no
hace falta correrlo a mano, alcanza con que exista `/opt/changuito/.env`.

### 3.6 Firewall

💻 De vuelta en tu compu (salir de la VM con `Ctrl+D` si seguís conectado):

```bash
# HTTP publico
gcloud compute firewall-rules create allow-http \
  --allow=tcp:80 --target-tags=http-server

# SSH solo via IAP (no exponer 22 a internet)
gcloud compute firewall-rules create allow-iap-ssh \
  --allow=tcp:22 --source-ranges=35.235.240.0/20
```

Confirmar que la VM tenga la tag `http-server` (o la que uses) y que
`--zone`/nombre coincidan con los secrets de GitHub (sección 4).

## 4. Secrets en GitHub

🌐 En el navegador, en `github.com/PAW-2026-DP/PAW-2026-Changuito-bn` →
`Settings → Secrets and variables → Actions → New repository secret`. Se
crean los 7, uno por uno (nombre exacto a la izquierda, valor a la derecha —
son los mismos valores que fuiste generando/anotando en la sección 3):

| Secret | Valor |
|---|---|
| `GCP_PROJECT_ID` | tu project id |
| `GCP_REGION` | ej. `southamerica-east1` |
| `GCP_WIF_PROVIDER` | salida del comando `providers describe` de 3.4 |
| `GCP_SERVICE_ACCOUNT` | `github-deployer@<project-id>.iam.gserviceaccount.com` |
| `GCP_VM_NAME` | nombre de la instancia |
| `GCP_VM_ZONE` | zona de la instancia |
| `DEV_HOST` | IP pública de la VM (para el smoke test) |

Se recomienda crear un **environment** `dev` (`Settings → Environments`) y
cargar ahí los secrets en vez de a nivel repo, para poder agregar protección
(ej. reviewers) más adelante sin tocar el workflow.

## 5. Rulesets de GitHub

🌐 También en el navegador: `Settings → Rules → Rulesets → New branch
ruleset`. Un ruleset con target
branches `main` **y** `dev` (o dos rulesets separados si `main` va a pedir
merge commit en vez de squash, sección 8 de `ramas-pr-despliegues.md`):

- **Restrict deletions**
- **Block force pushes**
- **Require a pull request before merging**
  - Required approvals: 1
  - Dismiss stale approvals on new commits
  - Allowed merge methods: Squash (para `dev`); Merge commit (para `main`)
- **Require status checks to pass**
  - Agregar el check `tests` (el job de `ci.yml`) — **solo aparece en la
    lista después de que el workflow corrió al menos una vez**, así que el
    orden correcto es: mergear primero el PR que agrega estos workflows con
    el ruleset todavía sin este check, y recién después crear/editar el
    ruleset para exigirlo.
  - Require branches to be up to date before merging
- **No** dar bypass a nadie, ni siquiera a admins/repo admins — la regla del
  equipo es "nadie pushea directo", sin excepciones.

Aplicar los mismos pasos para `main`, ajustando el método de merge.

## 6. Probar el pipeline de punta a punta

1. 💻 En tu compu, en la carpeta del repo:
   ```bash
   git checkout dev && git pull origin dev
   git checkout -b feature/cicd
   git add .github deploy docker/nginx docs/cicd.md README.md
   git commit -m "feat: agregar pipeline de CI/CD a DEV"
   git push -u origin feature/cicd
   ```
   Antes de pushear, `docker compose exec app vendor/bin/phpunit` tiene que
   estar en verde local (ya lo verificamos al armar estos archivos).
2. 🌐 En GitHub, abrir el PR `feature/cicd → dev` (el mismo push de arriba
   te da el link, o `Compare & pull request` en la web).
3. 🌐 En la pestaña **Actions** del PR, verificar que `ci.yml` corre:
   `composer validate`, `phpunit`, build de la imagen production — los tres
   en verde.
4. 💻/🌐 Con el check `tests` ya visible en la lista de checks del PR (paso
   necesario antes de poder exigirlo), volver a la sección 5 y terminar de
   configurar el ruleset.
5. 💻 Confirmar que la protección funciona — un push directo a `dev` debe
   ser rechazado:
   ```bash
   git checkout dev
   git commit --allow-empty -m "test: probar proteccion de dev"
   git push origin dev   # tiene que fallar
   git reset --hard origin/dev   # deshacer el commit de prueba local
   ```
6. 🌐 Pedirle a otra persona del equipo que apruebe el PR (vos no podés
   autoaprobarte, sección 5.3 de `ramas-pr-despliegues.md`) y mergearlo con
   **squash**.
7. 🌐 En la pestaña Actions, verificar que `deploy-dev.yml` corre y termina
   en verde (tarda un par de minutos: build de dos imágenes + deploy por
   SSH).
8. 💻 o 🌐: confirmar que quedaron publicadas las imágenes —
   `gcloud artifacts docker images list REGION-docker.pkg.dev/PROJECT_ID/changuito`
   o la consola de Artifact Registry — deberías ver `app:dev`,
   `app:sha-<commit>`, `nginx:dev`, `nginx:sha-<commit>`.
9. 💻 `curl http://<IP-de-la-VM>/` debe devolver
   `{"message":"Hello, World!"}` — es el mismo smoke test que ya corrió el
   workflow en el paso 7, pero confirmarlo vos mismo cierra el ciclo.

Si algo de esto falla, el punto más común de falla es 3.5 (permiso de la VM
para hacer `pull`) o un secret mal copiado en la sección 4 — los errores del
job de `deploy-dev.yml` en Actions dicen en qué step se cortó.

## 7. Rollback

Sin re-ejecutar el pipeline: en la VM, editar `/opt/changuito/.env` y fijar
`IMAGE_TAG` al sha anterior conocido, después:

```bash
cd /opt/changuito
docker compose pull
docker compose up -d --remove-orphans
```

El sha de cada deploy queda visible en el log de Actions y en los tags de
Artifact Registry.

## 8. Fuera de alcance por ahora

- **Producción**: no existe todavía una segunda VM/ambiente. Cuando exista,
  agregar `deploy-prod.yml` (`on: push: branches: [main]`) siguiendo el mismo
  patrón, con sus propios secrets/environment `prod` y aprobación manual
  (`environment` con required reviewers es la forma nativa de Actions de
  exigir esa aprobación antes del deploy).
- **Migraciones de base de datos**: no hay esquema todavía. Cuando exista, el
  paso de migración se agrega en `deploy-dev.yml` antes del `docker compose
  up -d`.
- **Análisis estático** (PHPStan/Psalm): no está en las reglas actuales del
  equipo; se puede sumar como step extra de `ci.yml` si el equipo lo decide.
