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

### 6) Alertas críticas y sonido

- Se implementó un stack visual compartido de alertas críticas reutilizado por:
  - `resources/views/inicio.blade.php`
  - `resources/views/objetivos.blade.php`
- El stack ahora tiene una sola fuente de verdad para:
  - posición fija desde mitad de pantalla,
  - scroll vertical,
  - orden con alertas más nuevas arriba,
  - iconografía y estilo visual consistentes,
  - sincronización con el estado de alertas activas.
- La parte visual común quedó extraída a:
  - `resources/views/components/critical-alert-stack.blade.php`
- El render/comportamiento compartido quedó centralizado en:
  - `resources/js/critical-alerts.js`
- El sonido crítico quedó implementado con escalado progresivo de intensidad
  inspirado en producción:
  - usa el asset `public/sounds/alarmas/critico.wav`,
  - arranca con volumen bajo,
  - incrementa volumen con el paso del tiempo,
  - luego acelera la cadencia de repetición,
  - evita duplicación entre pestañas cuando el navegador soporta `navigator.locks`.
- El control de audio quedó centralizado en:
  - `resources/js/critical-sound.js`
- Si el navegador bloquea autoplay, la app muestra un aviso global para habilitar
  manualmente el sonido.
- Estado validado hasta ahora:
  - `http://sentry-web.test/sounds/alarmas/critico.wav` responde correctamente,
  - el dashboard local autenticado ya incluye el markup del stack compartido y del
    fallback de audio,
  - queda pendiente la validación manual final del comportamiento audible en un flujo
    real de evento crítico.

## Limpieza y modularización reciente

### Objetivo de esta etapa

- Reducir lógica inline en vistas Blade.
- Consolidar helpers repetidos del frontend.
- Eliminar plantillas sin uso para dejar una base más mantenible.

### Estado actual de Blade

- `resources/views/inicio.blade.php` ya no depende de bloques inline de CSS/JS para su
  funcionamiento principal.
- `resources/views/objetivos.blade.php` quedó apoyada en módulos JS dedicados y en el
  componente compartido de alertas críticas.
- A nivel de vistas activas ya no quedan `<style>` o `<script>` embebidos como fuente
  principal de comportamiento de pantalla.

### Estructura frontend consolidada

- Entrada principal:
  - `resources/js/app.js`
- Módulos de página:
  - `resources/js/inicio-page.js`
  - `resources/js/objetivos-page.js`
  - `resources/js/login-page.js`
  - `resources/js/layout-shell.js`
- Módulos UI reutilizables:
  - `resources/js/critical-alerts.js`
  - `resources/js/critical-sound.js`
  - `resources/js/objetivo-card.js`
  - `resources/js/objetivo-modal-content.js`
  - `resources/js/objetivo-modal-controller.js`
- Helpers compartidos incorporados en esta etapa:
  - `resources/js/shared/page-boot.js`
  - `resources/js/shared/http.js`
  - `resources/js/shared/objetivo-utils.js`
  - `resources/js/shared/html.js`

### Qué quedó centralizado

- Boot robusto de páginas con una sola estrategia compartida (`document.readyState` +
  `DOMContentLoaded`) para evitar inicializaciones perdidas cuando el bundle carga tarde.
- Fetch con timeout y manejo común de expiración de sesión para pantallas web.
- Helpers de objetivos/eventos reutilizados entre `Inicio` y `Objetivos`:
  - conteo por estado,
  - resolución de `objetivoId`,
  - nombre visible de objetivo,
  - construcción de rutas parametrizadas,
  - normalización básica de texto y colecciones.
- Render seguro de HTML (`escapeHtml`) y formateo simple de valores visibles para evitar
  duplicación en cards, modales y alertas.

### Plantillas eliminadas por no uso

- `resources/views/welcome.blade.php`
- `resources/views/layouts/footer.blade.php`

Se eliminaron luego de verificar que:

- `/` redirige a `dashboard` y no usa la welcome por defecto de Laravel.
- El layout principal autenticado no incluye footer compartido.

### Validación posterior a la limpieza

- `npm run build` compila correctamente luego de la externalización y refactor.
- La carga de `Inicio` volvió a quedar funcional con mapa, eventos, SSE y cedulación.
- `Objetivos` mantiene búsqueda, contadores, modal y alertas compartidas.
- No se detectaron errores de lint en los módulos JS recientemente reorganizados.

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

Archivos relevantes actualmente:

- `sentry-infra/dev/apache/auto.sentry-web.test.conf`
- `sentry-infra/dev/apache/auto.api-sentry.test.conf`
- `sentry-infra/dev/apache/zzz.local-apache-tuning.conf`

El último archivo concentra el ajuste local de performance para Laragon/Apache en
Windows y debe quedar como fuente de verdad para replicar la corrección en otra PC.

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

## Diagnóstico operativo reciente (login inestable)

> Nota rápida: si este problema reaparece en otra PC, revisar primero Apache/Laragon
> (`hosts`, `httpd -S`, vhosts duplicados de `api-sentry.test` y `error.log`) antes
> de volver a investigar cookies, CSRF o credenciales. En esta máquina, ese fue el
> causal principal.

### Síntoma observado

- En navegador, `POST /login` termina en redirect a `/login` y en algunos casos `419 Page Expired`.
- En Postman, el endpoint `POST http://api-sentry.test/login` responde correctamente con token.

### Hallazgos confirmados

- El problema principal no es de credenciales del usuario de testing.
- Se detectaron timeouts intermitentes desde `sentry-web` hacia `api-sentry.test`:
  - `ConnectionException`
  - `cURL error 28` en `SentryApiClient::login()`.
- También hubo inestabilidad de sesión/CSRF en navegador (caso `419`) al validar login.
- Se confirmó además un falso positivo de `419` en pruebas por CLI cuando se sigue el
  redirect de `POST /login` forzando nuevamente `POST` en `/login` (por ejemplo, con
  `curl -L -X POST ...`). Con cookie jar normal, el flujo devuelve `302` y conserva
  correctamente la sesión web.
- Con credenciales válidas de testing se confirmó que:
  - `POST http://api-sentry.test/login` responde `200` correctamente,
  - el mismo login ejecutado desde una request web de `sentry-web` cae en
    `ConnectionException` / `cURL error 28`,
  - y el mismo cliente invocado por CLI desde Laravel responde bien.
- Esto sugiere un problema del stack local al hacer una llamada HTTP desde
  `sentry-web` hacia `api-sentry.test` dentro de la misma máquina/Apache, más que un
  error de credenciales o de CSRF del formulario.
- Al revisar la configuración real de Apache en Laragon se detectó que
  `api-sentry.test` estaba declarado más de una vez en distintos `.conf`.
- En el `error.log` de Apache apareció además:
  - `AH00326: Server ran out of threads to serve requests`
- Ese hallazgo es consistente con el síntoma observado: una request web a
  `sentry-web.test` que intenta llamar por HTTP a `api-sentry.test` dentro del mismo
  Apache local puede quedarse esperando recursos y terminar en timeout.

### Acciones aplicadas

- Ajuste de sesión para estabilizar cookies web:
  - `SESSION_DRIVER=file`
  - `SESSION_PATH=/`
  - `SESSION_DOMAIN=sentry-web.test`
  - `SESSION_SECURE_COOKIE=false`
  - `SESSION_SAME_SITE=lax`
- Limpieza de cache de framework tras cambios:
  - `php artisan optimize:clear`
- Mensajería de login más clara en `AuthWebController`:
  - distingue timeout/conexión API vs credenciales inválidas.
- Se revisó `hosts`, los VirtualHosts efectivos (`httpd -S`) y el `error.log` de Apache.
- Se consolidó la configuración para dejar una sola definición de `api-sentry.test`.
- Se reinició Apache con la configuración limpia.
- Luego de la limpieza:
  - `POST /login` volvió a redirigir correctamente a `dashboard`,
  - `GET /dashboard` respondió `200`,
  - y el dashboard cargó correctamente sus requests internas.

### Estado actual

- En esta PC el login web quedó estabilizado tras limpiar los VirtualHosts duplicados
  de Apache y reiniciar Laragon/Apache.
- La API y `sentry-web` siguen conviviendo en el mismo stack local, por lo que si el
  problema reaparece conviene volver a revisar saturación de Apache o separar `api`
  en otro proceso/puerto para descartar bloqueo intra-Apache.

### Ajuste posterior por lentitud general de pantallas

Luego de estabilizar el login apareció un segundo síntoma distinto:

- la API respondía rápido al consultarla directamente,
- pero `sentry-web.test` se volvía muy lento al navegar `Inicio`, abrir cedulación o
  dejar el dashboard abierto,
- en una medición puntual `GET /dashboard` llegó a tardar ~52 segundos para responder.

Hallazgos asociados:

- el `error.log` de Apache seguía registrando:
  - `AH00326: Server ran out of threads to serve requests`
- los endpoints directos de API seguían respondiendo rápido (`eventos`, `objetivos`,
  `cedulacion/getTipos`, `cedulacion/getObservaciones`), por lo que el cuello no estaba
  en la API sino en Apache atendiendo simultáneamente:
  - `sentry-web`,
  - `api-sentry`,
  - SSE del dashboard,
  - polling/requests auxiliares.

Acciones aplicadas en esta PC:

- reinicio manual de Apache para liberar hilos saturados;
- ajuste del MPM WinNT versionado en `sentry-infra/dev/apache/zzz.local-apache-tuning.conf`:
  - `ThreadsPerChild 256`
  - `KeepAlive On`
  - `MaxKeepAliveRequests 100`
  - `KeepAliveTimeout 2`
- mantenimiento de:
  - `AcceptFilter http none`
  - `AcceptFilter https none`
  - `EnableSendfile Off`
  - `EnableMMAP Off`
- agregado de tratamiento explícito para SSE en
  `sentry-infra/dev/apache/auto.sentry-web.test.conf` sobre `/x/sse/dashboard`:
  - `no-gzip`
  - `no-buffering`
  - `X-Accel-Buffering no`
  - `Content-Type text/event-stream`
  - `Cache-Control no-cache`

Resultado inmediato observado tras aplicar el ajuste:

- `GET /login` respondió alrededor de `0.08s`
- `GET /dashboard` volvió a responder alrededor de `0.33s`
- los endpoints de cedulación siguieron respondiendo en tiempos bajos

Conclusión práctica:

- si en la PC de casa reaparece lentitud general, aunque la API responda bien por
  separado, revisar primero saturación del Apache local y replicar este ajuste antes de
  buscar problemas en Laravel, Blade o JavaScript.
- el ajuste debe replicarse desde los archivos versionados de `sentry-infra/dev/apache/`
  y no rehacerse manualmente en `httpd.conf`, salvo que el include del proyecto no esté
  activo en esa máquina.

## Si Se Repite En Otra PC

1. Verificar `C:\Windows\System32\drivers\etc\hosts`:
   - `127.0.0.1 sentry-web.test`
   - `127.0.0.1 api-sentry.test`
2. Revisar el Apache activo con `httpd -S`.
3. Confirmar que exista una sola definición de `api-sentry.test`.
4. Si hay más de una, consolidar los `.conf` y reiniciar Apache.
5. Revisar `error.log` de Apache buscando:
   - `AH00326: Server ran out of threads to serve requests`
   - reinicios abruptos o timeouts repetidos durante `POST /login`
6. Si no hay vhosts duplicados pero la app sigue lenta:
   - reiniciar Apache,
   - revisar `ThreadsPerChild`,
   - bajar `KeepAliveTimeout`,
   - revisar configuración SSE de `sentry-web.test`.
7. Recién después volver a revisar cookies/sesión/CSRF si el problema persiste.

## Checklist Rápido Para PC De Casa

Si al llegar a la otra PC el login vuelve a fallar con redirect a `/login`, timeout o
`419`, revisar primero Apache antes que Laravel:

1. Confirmar `hosts`:
   - `127.0.0.1 sentry-web.test`
   - `127.0.0.1 api-sentry.test`
2. Ejecutar `httpd -S` en el Apache activo de Laragon.
3. Buscar si `api-sentry.test` aparece más de una vez.
4. Si está duplicado:
   - dejar una sola definición efectiva de `api-sentry.test`,
   - dejar `sentry-web.test` apuntando a `.../sentry-web/public`,
   - reiniciar Apache/Laragon.
5. Revisar `error.log` y buscar:
   - `AH00326: Server ran out of threads to serve requests`
   - `ConnectionException`
   - `cURL error 28`
6. Si no hay duplicados pero la web sigue lenta:
   - verificar que Apache esté incluyendo `sentry-infra/dev/apache/*.conf`
   - aplicar o copiar `sentry-infra/dev/apache/zzz.local-apache-tuning.conf`
   - conservar la configuración SSE de `sentry-infra/dev/apache/auto.sentry-web.test.conf`
   - reiniciar Apache.
7. Probar de nuevo:
   - `GET /login`
   - `POST /login`
   - `GET /dashboard`

### Causal Más Probable

En esta PC el problema no terminó siendo credenciales ni CSRF: el causal más fuerte fue
Apache local con VirtualHosts duplicados para `api-sentry.test`, lo que podía disparar
timeouts internos al hacer `sentry-web -> api-sentry.test` dentro del mismo stack.

## Roadmap corto

- `Objetivos`:
  - ya existe una primera implementación web del módulo en `sentry-web`,
  - incluye listado, búsqueda, contadores por estado, cards y modal de detalle,
  - el modal ya contempla fichas `Datos`, `Contactos`, `Eventos` y `Zonas`.
- Integración `Objetivos` en `sentry-web`:
  - se agregaron proxies web para `GET /x/objetivos/eventos/{objetivo}` y
    `GET /x/objetivos/zonas/{objetivo}`,
  - se reutilizó el contrato real del `api` observado en producción:
    - `GET /objetivos/{id}`
    - `GET /objetivos/contactos/{id}`
    - `GET /objetivos/eventos/{id}/{cantidad?}`
    - `GET /objetivos/zonas/{id}`
  - la pantalla ya renderiza el listado real de objetivos autenticados.
- Estado actual de `Objetivos`:
  - el módulo ya quedó funcional como base,
  - falta seguir puliendo la UI para acercarla más a `front`/producción,
  - conviene validar manualmente varios objetivos reales para ajustar formato de datos,
  - la interacción del modal quedó implementada, pero todavía requiere una ronda de QA
    visual/manual sobre navegación, tabs y contenido final.
- Alertas críticas:
  - `Inicio` y `Objetivos` ya comparten el mismo stack de alertas críticas,
  - la apariencia y el comportamiento quedaron centralizados para evitar divergencias,
  - las diferencias entre pantallas quedan limitadas a la acción contextual del botón
    (`Cedular evento` vs `Ver objetivo`).
- Sonido crítico:
  - ya quedó integrada la lógica base de audio y escalado de intensidad,
  - ya se incorporó el `critico.wav` real en `sentry-web`,
  - falta una validación manual final en navegador sobre autoplay, desbloqueo y
    progresión audible de volumen/cadencia.
- Continuar migración funcional de `front` a vistas Blade modulares.
- Homogeneizar UI/UX entre `front` y `sentry-web` durante la transición.
- Documentar decisiones de arquitectura por módulo a medida que se migra.
