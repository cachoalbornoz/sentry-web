# Sentry Web

## Contexto y objetivo

`sentry-web` es la nueva interfaz web de SentryGuard construida con Laravel.
El objetivo es reemplazar de forma progresiva al proyecto `front`, migrando primero
la funcionalidad existente y luego incorporando nuevas capacidades.

Este proyecto se integra con `api` como backend principal de negocio y seguridad.

## Enfoque conceptual y metodológico

La evolución principal fue pasar de pantallas aisladas a un enfoque basado en
plantillas y layout compartido de Laravel Blade.

### Principios aplicados

- **Layout como shell de aplicación**: barra superior (`layouts/navbar`), panel de
  perfil lateral (`layouts/profile-sidebar`) y estructura común de contenido. No
  hay footer global en el layout principal actual.
- **Vistas por sección**: cada módulo renderiza su contenido dentro del layout vía
  `@extends` y `@yield`.
- **Separación web/API**: Laravel web actúa como BFF liviano; la lógica de negocio
  y persistencia permanece en `api`.
- **Experiencia reactiva sin SPA pesada**: combinación de Blade + JS/Vite + SSE
  para mantener la UI ágil y actualizable en tiempo real.
- **Tema oscuro coherente**: Tailwind en vistas; mapa Leaflet con tiles servidos en
  same-origin vía proxy para evitar problemas de CORS/clave expuesta en el cliente.

### Layouts base actuales

- `resources/views/layouts/app.blade.php`: shell principal autenticado.
- `resources/views/layouts/guest.blade.php`: pantallas de acceso (login).

## Arquitectura actual

### Capa UI (Blade + Vite)

- Vistas principales:
  - `resources/views/inicio.blade.php` (dashboard: mapa, eventos, cedulación)
  - `resources/views/objetivos.blade.php`
  - `resources/views/dashboard.blade.php` (debug)
  - `resources/views/auth/login.blade.php`
- Activos frontend compilados con Vite (`resources/css/app.css`, `resources/js/app.js`).

### Capa web (controladores y middleware)

- `AuthWebController`: login/logout y manejo de sesión local.
- `HomeWebController`: carga inicial de dashboard (eventos + objetivos + tiles).
- `ApiProxyController`: proxy de endpoints funcionales contra `api`.
- `SseProxyController`: proxy de stream SSE de dashboard.
- `TileProxyController`: proxy same-origin para tiles de mapa (Carto/Stadia).
- `EnsureApiToken` middleware: valida token en sesión para rutas protegidas.

### Capa de integración con API

- `SentryApiClient` centraliza llamadas HTTP a `api` (incluye timeouts ajustables
  en operaciones pesadas como guardar cedulación).
- Rutas proxy bajo prefijo `/x/*` para desacoplar el navegador del host real de `api`.
- Manejo de expiración de sesión y fallos de conectividad en endpoints críticos.

## Flujo funcional implementado

### 1) Autenticación

- `GET /login` muestra formulario.
- `POST /login` autentica contra `api` y guarda:
  - `api_token`
  - `api_user`
  - `api_token_expires_at`
- Rutas internas protegidas con middleware `api.token`.

### 2) Dashboard

- `GET /` redirige a `GET /dashboard` (nombre de ruta `dashboard`).
- `GET /dashboard`:
  - obtiene eventos y objetivos desde `api`,
  - inicializa mapa y métricas,
  - habilita actualización en tiempo real vía SSE (`/x/sse/dashboard`).

### 3) Mapa

- Tiles: `GET /x/tiles/carto-dark/...` y, si está habilitado Stadia,
  `GET /x/tiles/stadia-dark/...` (ver variables `MAP_USE_STADIA` y `STADIA_KEY`).

### 4) Cedulación de eventos

- Selección múltiple de eventos desde el listado.
- Modal de cedulación con datos del evento/objetivo.
- Carga de:
  - tipos de señal (`/x/cedulacion/tipos`)
  - observaciones predefinidas (`/x/cedulacion/observaciones`)
  - contactos por objetivo (`/x/objetivos/contactos/{id}`)
- Guardado por `POST /x/cedulacion/guardar`. El proxy puede **partir el envío en
  lotes** según `config('services.sentry_api.cedulacion_batch_size')` (variable
  `SENTRY_CEDULACION_BATCH_SIZE`, por defecto `1`) para reducir timeouts o
  conflictos en la API ante muchos eventos.

Ejemplo de payload:

```json
{
  "eventos": [318, 338],
  "cedulacion_tipo_id": 1,
  "observaciones": "cedulado"
}
```

### 5) Objetivos

- Vista modularizada en `GET /objetivos` dentro del layout común.
- Endpoints auxiliares:
  - listado (`/x/objetivos`)
  - detalle (`/x/objetivos/{objetivo}`)
  - contactos (`/x/objetivos/contactos/{objetivo}`)

## Entorno local (Laragon)

### Hosts recomendados

Agregar en `C:\Windows\System32\drivers\etc\hosts`:

```txt
127.0.0.1 sentry-web.test
127.0.0.1 api-sentry.test
```

### VirtualHosts

- `sentry-web.test` → `.../sentry-web/public`
- `api-sentry.test` → `.../api/public`

Se recomienda mantener los `.conf` de proyecto versionados en
`sentry-infra/dev/apache/` y cargarlos desde Apache con `IncludeOptional`.

## Variables de entorno clave (`.env`)

Mínimas para operación local:

```env
APP_URL=http://sentry-web.test
SENTRY_API_BASE_URL=http://api-sentry.test

# Mapa
MAP_USE_STADIA=false
# STADIA_KEY=tu_clave_real

# Opcional: tamaño de lote al enviar cedulación al API (entero >= 1)
# SENTRY_CEDULACION_BATCH_SIZE=1
```

Notas:

- `APP_KEY` debe estar seteada (`php artisan key:generate`).
- Si cambia la configuración de entorno: `php artisan config:clear`.

## Comandos útiles

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

En entorno Laragon normalmente no se usa `php artisan serve`, ya que Apache resuelve
el host virtual directamente.

## Roadmap corto

- Consolidar modal de eventos críticos (alineado a producción).
- Continuar migración funcional de `front` a vistas Blade modulares.
- Homogeneizar UI/UX entre `front` y `sentry-web` durante la transición.
- Documentar decisiones de arquitectura por módulo a medida que se migra.
