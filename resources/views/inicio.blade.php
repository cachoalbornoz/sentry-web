@extends('layouts.app', ['activeNav' => 'inicio'])

@section('title', 'Inicio')

@push('styles')
    <style>
        .card { border: 1px solid rgb(30 41 59 / 1); background: rgb(15 23 42 / .55); }
        .panel { border: 1px solid rgb(30 41 59 / 1); background: rgb(15 23 42 / .55); }
        .table-row { border-bottom: 1px solid rgb(30 41 59 / 1); }
        .table-row:hover { background: rgb(2 6 23 / .35); }
        #map { height: 420px; }
        /* Backdrop opaco: evita que se vea el mapa detrás a través del overlay */
        .modal-backdrop { background: rgba(2, 6, 23, 0.45); backdrop-filter: blur(1px); }
        .chip { border: 1px solid rgb(51 65 85 / 1); background: rgb(2 6 23 / .35); }
        /* Si los tiles tardan en cargar, evitamos “fondo vacío” */
        .leaflet-container { background: #0b1220; }
        /* Aseguramos que el modal siempre quede arriba del mapa (Leaflet tiene panes con z-index propios). */
        #cedular-modal { z-index: 99999 !important; }
        #cedular-modal .modal-backdrop { z-index: 99998 !important; background: rgba(2, 6, 23, 0.45) !important; }
        #cedular-panel { z-index: 99999 !important; }
        /* Mapa por defecto más bajo que el modal */
        #map { position: relative; z-index: 1; }
        #eventos-scroll { max-height: 420px; overflow-y: auto; }
        .sort-header-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgb(226 232 240 / 1);
            font-weight: 500;
            padding: 2px 0;
            border-bottom: 1px solid transparent;
        }
        .sort-header-btn:hover { color: rgb(248 250 252 / 1); }
        .sort-header-btn .sort-indicator {
            color: rgb(100 116 139 / 1);
            font-size: 11px;
            line-height: 1;
        }
        .sort-header-btn.is-active {
            color: rgb(191 219 254 / 1);
            border-bottom-color: rgb(59 130 246 / .7);
        }
        .sort-header-btn.is-active .sort-indicator {
            color: rgb(59 130 246 / 1);
        }
        .group-row {
            background: linear-gradient(90deg, rgb(15 23 42 / .95), rgb(15 23 42 / .65));
            border-top: 1px solid rgb(51 65 85 / 1);
            border-bottom: 1px solid rgb(51 65 85 / 1);
        }
        .group-row td {
            padding: 9px 10px;
            font-size: 12px;
            color: rgb(148 163 184 / 1);
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .group-row-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .group-row-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgb(186 230 253 / .95);
            font-weight: 600;
        }
        .group-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgb(186 230 253 / .95);
            font-weight: 600;
        }
        .group-toggle-icon {
            color: rgb(148 163 184 / 1);
            font-size: 11px;
            transition: transform .16s ease;
        }
        .group-toggle-btn.is-collapsed .group-toggle-icon {
            transform: rotate(-90deg);
        }
        .group-row-count {
            border: 1px solid rgb(71 85 105 / .85);
            border-radius: 999px;
            padding: 1px 8px;
            color: rgb(203 213 225 / 1);
            font-size: 11px;
            text-transform: none;
        }
        #critical-alerts-stack {
            position: fixed;
            left: 12px;
            top: 50vh;
            bottom: 12px;
            z-index: 5000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 320px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }
        .critical-alert-card {
            border: 1px solid rgba(248, 113, 113, 0.28);
            border-left: 3px solid rgba(248, 113, 113, 0.75);
            background: rgba(15, 23, 42, 0.96);
            box-shadow: 0 10px 24px rgb(2 6 23 / 0.45), 0 0 0 1px rgba(239, 68, 68, 0.08) inset;
            border-radius: 12px;
            padding: 10px 12px;
        }
        button {
            cursor: pointer;
            transition: background-color .18s ease, color .18s ease, border-color .18s ease, filter .18s ease;
        }
        button:hover:not(:disabled) {
            filter: brightness(.82);
        }
    </style>
@endpush

@section('content')
        {{-- Cards superiores --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos críticos</div>
                        <div class="text-xs text-slate-400">Requieren atención inmediata</div>
                    </div>
                    <div class="w-5 h-5 rounded-md" style="background:#ef4444;border:2px solid rgba(248,113,113,.55);box-shadow:0 0 10px rgba(239,68,68,.45);"></div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-critico">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos conectados</div>
                        <div class="text-xs text-slate-400">Comunicación activa</div>
                    </div>
                    <div class="w-5 h-5 rounded-md" style="background:#38bdf8;border:2px solid rgba(125,211,252,.55);box-shadow:0 0 10px rgba(56,189,248,.45);"></div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-online">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivo inactivo</div>
                        <div class="text-xs text-slate-400">Sin comunicación</div>
                    </div>
                    <div class="w-5 h-5 rounded-md" style="background:#cbd5e1;border:2px solid rgba(226,232,240,.55);box-shadow:0 0 10px rgba(203,213,225,.4);"></div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-offline">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos sin señal</div>
                        <div class="text-xs text-slate-400">Desconectados</div>
                    </div>
                    <div class="w-5 h-5 rounded-md" style="background:#fb923c;border:2px solid rgba(253,186,116,.55);box-shadow:0 0 10px rgba(251,146,60,.45);"></div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-muerto">0</div>
            </div>
        </div>

        {{-- Grilla eventos + mapa --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-stretch">
            <section class="panel rounded-xl p-0 overflow-hidden lg:col-span-7">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="font-medium">Eventos - <span id="eventos-total">0</span></div>
                        <div class="text-xs text-slate-400">Última actualización: <span id="eventos-updated">—</span></div>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <button id="toggle-group-eventos" type="button" class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-900/60 px-2 py-1 text-slate-300 hover:bg-slate-800" title="Agrupar por tipo de señal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 5h6v6H4zM14 5h6v4h-6zM14 13h6v6h-6zM4 15h6v4H4z"></path>
                            </svg>
                            <span id="group-eventos-label">Agrupar</span>
                        </button>
                        <a class="underline underline-offset-4 hover:text-slate-200" href="{{ route('debug') }}">Debug</a>
                    </div>
                </div>

                <div id="batchbar" class="hidden px-4 py-2 bg-blue-600/90 text-white text-sm flex items-center justify-between">
                    <div><span id="selected-count">0</span> evento(s) seleccionado(s)</div>
                    <div class="flex items-center gap-4">
                        <button id="open-cedular" class="cursor-pointer rounded-md border border-blue-200/50 bg-slate-900/35 px-3 py-1.5 font-medium text-white transition-colors duration-150 hover:bg-slate-900/55">
                            Cedular eventos
                        </button>
                        <button id="clear-selection" class="cursor-pointer rounded-md border border-blue-200/35 bg-slate-900/25 px-3 py-1.5 font-medium text-blue-100 transition-colors duration-150 hover:bg-slate-900/45 hover:text-white">
                            Cancelar
                        </button>
                    </div>
                </div>

                <div class="p-3">
                    <div class="mb-2">
                        <input id="eventos-search" type="text" placeholder="Buscar eventos"
                               class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400/40"/>
                    </div>

                    <div id="eventos-scroll" class="rounded-lg border border-slate-800">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-900/70 text-slate-200">
                                <tr>
                                    <th class="p-2 w-10 text-left">
                                        <input id="select-all" type="checkbox" class="accent-blue-500"/>
                                    </th>
                                    <th class="p-2 text-left">#</th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="tipoSenal">
                                            Tipo de señal <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="fecha">
                                            Fecha y Hora <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="objetivo">
                                            Objetivo <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="zona">
                                            Zona <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="eventos-tbody" class="text-slate-100">
                                {{-- Render inicial (SSR) --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="panel rounded-xl overflow-hidden lg:col-span-5">
                <div class="px-4 py-3 border-b border-slate-800">
                    <div class="font-medium">Mapa</div>
                </div>
                <div class="p-3">
                    <div id="map" class="rounded-lg border border-slate-800 overflow-hidden"></div>
                    <div class="mt-2 text-xs text-slate-500">
                        Marcadores: <span id="map-count">0</span>
                    </div>
                </div>
            </section>
        </div>

    {{-- Alertas críticas apiladas (bottom-left) --}}
    <div id="critical-alerts-stack" class="hidden"></div>

    {{-- Modal cedulación --}}
    <div id="cedular-modal" class="hidden fixed inset-0 z-[4000] p-4">
        <div class="absolute inset-0 z-[4000] modal-backdrop"></div>
        <div id="cedular-panel" class="relative z-[4010] mx-auto mt-8 w-full max-w-4xl h-[80vh] overflow-hidden bg-slate-900/95 border border-slate-700 rounded-xl shadow-2xl shadow-black flex flex-col">
            <div id="cedular-header" class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
                <div>
                    <div class="text-sm text-slate-400" id="cedular-subtitle">Cedular evento</div>
                    <div class="text-xl font-semibold" id="cedular-title">—</div>
                    <div class="text-sm text-slate-200 mt-1" id="cedular-objetivo">—</div>
                    <div class="text-xs text-slate-400 mt-2">
                        Fecha y hora: <span id="cedular-fecha">—</span> · Zona: <span id="cedular-zona">—</span>
                    </div>
                </div>
                <button id="cedular-close" class="text-slate-300 hover:text-white text-xl leading-none">×</button>
            </div>

            <div id="cedular-multi-list" class="hidden px-4 pt-3" style="flex:0 0 auto;">
                <div class="rounded-lg border border-slate-700 overflow-hidden">
                    <div style="max-height:220px; overflow-y:auto; overflow-x:hidden;">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-700 text-slate-100">
                                <tr>
                                    <th class="p-2 text-left">#</th>
                                    <th class="p-2 text-left">Tipo de Señal</th>
                                    <th class="p-2 text-left">Fecha y Hora</th>
                                    <th class="p-2 text-left">Objetivo</th>
                                    <th class="p-2 text-left">Zona</th>
                                </tr>
                            </thead>
                            <tbody id="cedular-multi-tbody" class="bg-slate-800/70"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 overflow-y-auto flex-1 min-h-0">
                <div class="space-y-2.5">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-blue-600 p-3 text-center">
                            <div class="text-xl font-semibold">911</div>
                            <div class="text-sm">Policía</div>
                        </div>
                        <div class="rounded-lg p-3 text-center" style="background:#dc2626;border:1px solid rgba(254,202,202,.35);">
                            <div class="text-xl font-semibold">100</div>
                            <div class="text-sm">Bomberos</div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-800 chip p-2.5">
                        <div class="text-xs text-slate-400 mb-1">Contactos por objetivo</div>
                        <select id="contactos-objetivo" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                            <option value="">—</option>
                        </select>
                        <div id="contactos-empty" class="mt-3 text-slate-300">
                            <div class="text-lg font-medium">No hay contactos</div>
                            <div class="text-sm text-slate-400">No hay contactos cargados para este objetivo.</div>
                        </div>
                        <div id="contactos-list" class="hidden mt-3 space-y-2 text-sm"></div>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Tipo de señal</label>
                        <select id="cedulacion-tipo" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                            <option value="">Cargando…</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Observaciones predefinidas</label>
                        <select id="obs-predef" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                            <option value="">Cargando…</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Observaciones</label>
                        <textarea id="obs-text" rows="3"
                                  class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400/40"
                                  placeholder="Observaciones del evento"></textarea>
                    </div>
                    <div id="cedular-msg" class="hidden text-sm rounded-lg border p-3"></div>
                </div>
            </div>

            <div class="px-4 py-3 border-t border-slate-800 flex items-center justify-between bg-slate-950/90">
                <button id="cedular-cancel" class="text-slate-300 hover:text-white underline underline-offset-4">Cancelar</button>
                <button id="cedular-save" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold hover:bg-blue-500">
                    Guardar cedulación
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const INITIAL_EVENTOS = @json($eventos);
        const INITIAL_OBJETIVOS = @json($objetivos);
        const STADIA_KEY = @json($stadiaKey);
        const USE_STADIA_BASEMAP = @json($useStadiaBasemap);
        const CONTACTOS_ROUTE_TEMPLATE = @json(route('x.objetivos.contactos', ['objetivo' => '__OBJETIVO__']));
        const OBJETIVO_DETALLE_ROUTE_TEMPLATE = @json(route('x.objetivos.detalle', ['objetivo' => '__OBJETIVO__']));

        const state = {
            eventos: Array.isArray(INITIAL_EVENTOS) ? INITIAL_EVENTOS : [],
            objetivos: Array.isArray(INITIAL_OBJETIVOS) ? INITIAL_OBJETIVOS : [],
            selected: new Set(),
            cedulacionTipos: null,
            cedulacionObservaciones: null,
            contactosByObjetivo: {},
            criticalAlerts: [],
            lastFocusedCriticalObjetivoId: null,
            coordsHydrationInFlight: false,
            lastCoordsHydrationAt: 0,
            eventosSort: { key: 'fecha', dir: 'desc' },
            eventosGroupByTipo: false,
            eventosCollapsedGroups: new Set(),
        };
        const mapAnimation = {
            focusDuration: 6.8,
            resetDuration: 8.4,
            easeLinearity: 0.14,
        };

        function fmtDate(s) {
            if (!s) return '—';
            return String(s).replace('T', ' ').replace('.000000Z', '');
        }

        function normalizeObjetivo(o) {
            if (!o || typeof o !== 'object') return o;

            const toNumber = (v) => {
                if (typeof v === 'number') return v;
                if (typeof v !== 'string') return null;
                const n = Number(v.trim().replace(',', '.'));
                return Number.isFinite(n) ? n : null;
            };

            const normalizeCoords = (latRaw, lonRaw) => {
                let lat = toNumber(latRaw);
                let lon = toNumber(lonRaw);
                if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null;

                // Si llegan invertidas (muy comun desde algunas fuentes), corregimos.
                // Regla: en Argentina la latitud suele estar en [-56,-21] y longitud en [-76,-53].
                const looksLikeArLon = lon >= -76 && lon <= -53;
                const looksLikeArLat = lat >= -56 && lat <= -21;
                const looksSwappedForAr = !looksLikeArLat && !looksLikeArLon
                    && lat >= -76 && lat <= -53
                    && lon >= -56 && lon <= -21;

                if (looksSwappedForAr) {
                    [lat, lon] = [lon, lat];
                }

                return { latitud: lat, longitud: lon };
            };

            if (o.ubicacion && typeof o.ubicacion === 'object') {
                const coords = normalizeCoords(o.ubicacion.latitud, o.ubicacion.longitud);
                if (coords) return { ...o, ubicacion: coords };
            }

            const coords = normalizeCoords(o.latitud, o.longitud);
            if (coords) {
                return { ...o, ubicacion: coords };
            }

            return o;
        }

        function computeObjetivosStates(objetivos) {
            const counts = { CRITICO: 0, ONLINE: 0, OFFLINE: 0, MUERTO: 0 };
            for (const o of objetivos) {
                const st = (o.estado || '').toUpperCase();
                if (counts[st] !== undefined) counts[st] += 1;
            }
            return counts;
        }

        function hasCoords(obj) {
            return !!(obj?.ubicacion && typeof obj.ubicacion.latitud === 'number' && typeof obj.ubicacion.longitud === 'number');
        }

        function mergeObjetivosPreservingCoords(current, incoming) {
            const currentById = new Map((current || []).map(o => [Number(o.id), normalizeObjetivo(o)]));
            return (incoming || []).map((raw) => {
                const next = normalizeObjetivo(raw);
                const id = Number(next?.id || 0);
                const prev = currentById.get(id);
                if (!hasCoords(next) && hasCoords(prev)) {
                    return { ...next, ubicacion: prev.ubicacion };
                }
                return next;
            });
        }

        function getObjetivoNameById(objetivoId) {
            const obj = state.objetivos.find((o) => Number(o.id) === Number(objetivoId));
            return obj?.nombre ?? obj?.descripcion ?? `Objetivo ${objetivoId}`;
        }

        function renderCriticalAlerts() {
            const stack = document.getElementById('critical-alerts-stack');
            if (!Array.isArray(state.criticalAlerts) || state.criticalAlerts.length === 0) {
                stack.classList.add('hidden');
                stack.innerHTML = '';
                return;
            }

            stack.classList.remove('hidden');
            const orderedAlerts = [...state.criticalAlerts].reverse(); // Nuevo arriba, viejo abajo
            stack.innerHTML = orderedAlerts.map((a) => `
                <div class="critical-alert-card">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-100">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full" style="color:rgba(248,113,113,.75);border:1px solid rgba(248,113,113,.75);background:rgba(248,113,113,.08);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round">
                                    <path d="M6 6l12 12M18 6L6 18"></path>
                                </svg>
                            </span>
                            Atención requerida en objetivo crítico
                        </div>
                        <button class="text-slate-400 hover:text-white text-sm leading-none critical-alert-close" data-id="${a.id}">×</button>
                    </div>
                    <div class="mt-1 text-sm text-slate-200">${a.objetivoNombre}</div>
                    <div class="mt-1 text-xs text-slate-400">Se detectó estado crítico.</div>
                    <div class="mt-3">
                        <button class="critical-alert-cedular rounded-md border border-slate-600 bg-slate-900/80 px-3 py-1.5 text-sm hover:bg-slate-800"
                                data-objetivo-id="${a.objetivoId}">
                            Cedular evento
                        </button>
                    </div>
                </div>
            `).join('');

            stack.querySelectorAll('.critical-alert-close').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = String(btn.dataset.id || '');
                    state.criticalAlerts = state.criticalAlerts.filter((a) => String(a.id) !== id);
                    if (state.criticalAlerts.length > 0) {
                        state.lastFocusedCriticalObjetivoId = state.criticalAlerts[state.criticalAlerts.length - 1].objetivoId;
                    } else {
                        state.lastFocusedCriticalObjetivoId = null;
                    }
                    renderCriticalAlerts();
                });
            });

            stack.querySelectorAll('.critical-alert-cedular').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const objetivoId = Number(btn.dataset.objetivoId || 0);
                    if (!objetivoId) return;

                    // Selecciona el evento más reciente de ese objetivo para abrir cedulación.
                    const ev = state.eventos.find((e) => Number(getEventoObjetivoId(e)) === objetivoId);
                    if (!ev?.idEvento) return;
                    state.selected.clear();
                    state.selected.add(Number(ev.idEvento));
                    updateBatchBar();
                    await openCedularModal();
                });
            });
        }

        function addCriticalAlert(objetivoId) {
            if (!objetivoId) return;
            // No crear alerta si no hay eventos para ese objetivo.
            const hasEvento = state.eventos.some((e) => Number(getEventoObjetivoId(e)) === Number(objetivoId));
            if (!hasEvento) return;

            // Evita duplicados para el mismo objetivo mientras siga pendiente.
            if (state.criticalAlerts.some((a) => Number(a.objetivoId) === Number(objetivoId))) {
                state.lastFocusedCriticalObjetivoId = objetivoId;
                focusMapOnObjetivo(objetivoId);
                return;
            }

            const alert = {
                id: `${Date.now()}-${objetivoId}`,
                objetivoId,
                objetivoNombre: getObjetivoNameById(objetivoId),
                createdAt: Date.now(),
            };
            state.criticalAlerts.push(alert);
            state.lastFocusedCriticalObjetivoId = objetivoId;
            renderCriticalAlerts();
            focusMapOnObjetivo(objetivoId);
        }

        function removeCriticalAlertByObjetivoId(objetivoId) {
            const before = state.criticalAlerts.length;
            state.criticalAlerts = state.criticalAlerts.filter((a) => Number(a.objetivoId) !== Number(objetivoId));
            if (before !== state.criticalAlerts.length) {
                if (state.criticalAlerts.length > 0) {
                    state.lastFocusedCriticalObjetivoId = state.criticalAlerts[state.criticalAlerts.length - 1].objetivoId;
                } else {
                    state.lastFocusedCriticalObjetivoId = null;
                    // Si ya no quedan alertas críticas pendientes, restablece vista global.
                    renderMarkers();
                }
                renderCriticalAlerts();
            }
        }

        function removeCriticalAlertsForSelectedEvents() {
            const selectedIds = Array.from(state.selected.values()).map(Number);
            if (selectedIds.length === 0) return;
            const objetivos = new Set(
                state.eventos
                    .filter((e) => selectedIds.includes(Number(e.idEvento)))
                    .map((e) => Number(getEventoObjetivoId(e)))
                    .filter((n) => Number.isFinite(n) && n > 0)
            );
            objetivos.forEach((id) => removeCriticalAlertByObjetivoId(id));
        }

        function clearCriticalAlertsIfNoCriticalObjetivos() {
            const hasAnyCritical = state.objetivos.some((o) => String(o?.estado || '').toUpperCase() === 'CRITICO');
            if (!hasAnyCritical) {
                state.criticalAlerts = [];
                state.lastFocusedCriticalObjetivoId = null;
                renderCriticalAlerts();
                renderMarkers(); // al no haber críticos pendientes, vuelve a vista global
            }
        }

        function syncCriticalAlertsWithEventos() {
            // Regla de negocio: sin eventos visibles, no mostramos alertas críticas.
            if (!Array.isArray(state.eventos) || state.eventos.length === 0) {
                state.criticalAlerts = [];
                state.lastFocusedCriticalObjetivoId = null;
                renderCriticalAlerts();
                return;
            }

            // Si hay eventos, sólo mantenemos alertas cuyo objetivo aún tenga algún evento asociado.
            const objetivosConEvento = new Set(
                state.eventos
                    .map((e) => Number(getEventoObjetivoId(e)))
                    .filter((n) => Number.isFinite(n) && n > 0)
            );
            state.criticalAlerts = state.criticalAlerts.filter((a) => objetivosConEvento.has(Number(a.objetivoId)));
            if (state.criticalAlerts.length === 0) {
                state.lastFocusedCriticalObjetivoId = null;
            }
            renderCriticalAlerts();
        }

        function focusMapOnObjetivo(objetivoId) {
            if (!map) return;
            const obj = state.objetivos.find((o) => Number(o.id) === Number(objetivoId));
            const n = normalizeObjetivo(obj);
            const lat = n?.ubicacion?.latitud;
            const lon = n?.ubicacion?.longitud;
            if (typeof lat !== 'number' || typeof lon !== 'number') return;

            // Siempre arranca desde vista general y luego acerca al crítico.
            const latLngs = [];
            for (const o of state.objetivos) {
                const no = normalizeObjetivo(o);
                const la = no?.ubicacion?.latitud;
                const lo = no?.ubicacion?.longitud;
                if (typeof la === 'number' && typeof lo === 'number') latLngs.push([la, lo]);
            }

            const zoomToCritical = () => {
                map.setView([lat, lon], 14, {
                    animate: true,
                    duration: mapAnimation.focusDuration,
                    easeLinearity: mapAnimation.easeLinearity,
                });
            };

            if (latLngs.length > 0) {
                const bounds = L.latLngBounds(latLngs);
                // Apenas termina de mostrar "todos", arranca el zoom al crítico.
                map.once('moveend', () => {
                    zoomToCritical();
                });
                map.fitBounds(bounds, {
                    padding: [24, 24],
                    maxZoom: 12,
                    animate: true,
                    duration: mapAnimation.resetDuration,
                    easeLinearity: mapAnimation.easeLinearity,
                });
            } else {
                zoomToCritical();
            }
        }

        function renderCounts() {
            const c = computeObjetivosStates(state.objetivos);
            document.getElementById('count-critico').textContent = c.CRITICO ?? 0;
            document.getElementById('count-online').textContent = c.ONLINE ?? 0;
            document.getElementById('count-offline').textContent = c.OFFLINE ?? 0;
            document.getElementById('count-muerto').textContent = c.MUERTO ?? 0;
        }

        function compareDateLike(valueA, valueB) {
            const parse = (v) => {
                const raw = String(v ?? '').trim();
                if (!raw) return Number.NEGATIVE_INFINITY;
                const normalized = raw
                    .replaceAll('a. m.', 'AM')
                    .replaceAll('p. m.', 'PM')
                    .replaceAll('a.m.', 'AM')
                    .replaceAll('p.m.', 'PM');
                const native = Date.parse(normalized);
                if (!Number.isNaN(native)) return native;

                const match = normalized.match(/(\d{1,2})\/(\d{1,2})\/(\d{2,4})[,\s]+(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)?/i);
                if (!match) return Number.NEGATIVE_INFINITY;
                let [, dd, mm, yy, hh, mi, ss, ampm] = match;
                let year = Number(yy);
                if (year < 100) year += 2000;
                let hour = Number(hh);
                if (ampm) {
                    const up = ampm.toUpperCase();
                    if (up === 'PM' && hour < 12) hour += 12;
                    if (up === 'AM' && hour === 12) hour = 0;
                }
                return new Date(year, Number(mm) - 1, Number(dd), hour, Number(mi), Number(ss || 0)).getTime();
            };
            return parse(valueA) - parse(valueB);
        }

        function sortEventos(items) {
            const cfg = state.eventosSort || { key: 'fecha', dir: 'desc' };
            const dir = cfg.dir === 'asc' ? 1 : -1;
            const key = cfg.key || 'fecha';
            return [...items].sort((a, b) => {
                if (key === 'fecha') {
                    const byDate = compareDateLike(a?.fecha, b?.fecha);
                    if (byDate !== 0) return byDate * dir;
                    return String(a?.idEvento ?? '').localeCompare(String(b?.idEvento ?? ''), 'es', { numeric: true }) * dir;
                }
                const va = String(a?.[key] ?? '');
                const vb = String(b?.[key] ?? '');
                const byText = va.localeCompare(vb, 'es', { sensitivity: 'base', numeric: true });
                if (byText !== 0) return byText * dir;
                return compareDateLike(a?.fecha, b?.fecha) * -1;
            });
        }

        function updateSortHeadersUi() {
            const cfg = state.eventosSort || { key: 'fecha', dir: 'desc' };
            document.querySelectorAll('.sort-header-btn').forEach((btn) => {
                const key = btn.dataset.sortKey;
                const indicator = btn.querySelector('.sort-indicator');
                const isActive = key === cfg.key;
                btn.classList.toggle('is-active', isActive);
                if (!indicator) return;
                indicator.textContent = isActive ? (cfg.dir === 'asc' ? '↑' : '↓') : '↕';
            });
        }

        function renderEventosRows(items) {
            return items.map((e) => {
                const checked = state.selected.has(Number(e.idEvento)) ? 'checked' : '';
                return `
                    <tr class="table-row">
                        <td class="p-2">
                            <input class="row-check accent-blue-500" type="checkbox" data-id="${e.idEvento}" ${checked}/>
                        </td>
                        <td class="p-2 text-slate-300">${e.idEvento ?? ''}</td>
                        <td class="p-2">${e.tipoSenal ?? ''}</td>
                        <td class="p-2 text-slate-200">${e.fecha ?? ''}</td>
                        <td class="p-2">${e.objetivo ?? ''}</td>
                        <td class="p-2 text-slate-300">${e.zona ?? ''}</td>
                    </tr>
                `;
            }).join('');
        }

        function hookGroupCollapseButtons() {
            document.querySelectorAll('.group-toggle-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const key = decodeURIComponent(String(btn.dataset.groupKey || ''));
                    if (!key) return;
                    if (state.eventosCollapsedGroups.has(key)) state.eventosCollapsedGroups.delete(key);
                    else state.eventosCollapsedGroups.add(key);
                    renderEventos();
                });
            });
        }

        function renderEventos() {
            const tbody = document.getElementById('eventos-tbody');
            const q = (document.getElementById('eventos-search').value || '').toLowerCase().trim();

            const filteredAll = state.eventos.filter(e => {
                const hay = [
                    e.idEvento, e.tipoSenal, e.fecha, e.objetivo, e.zona
                ].filter(Boolean).join(' ').toLowerCase();
                return q === '' || hay.includes(q);
            });

            const sorted = sortEventos(filteredAll);
            // Solo mostramos hasta 30 eventos para no desarmar la pantalla
            const filtered = sorted.slice(0, 30);

            document.getElementById('eventos-total').textContent = String(filteredAll.length);
            if (state.eventosGroupByTipo) {
                const groups = new Map();
                for (const ev of filtered) {
                    const key = String(ev.tipoSenal || 'Sin tipo');
                    if (!groups.has(key)) groups.set(key, []);
                    groups.get(key).push(ev);
                }
                const rows = [];
                for (const [tipo, events] of groups.entries()) {
                    const groupKey = encodeURIComponent(tipo);
                    const collapsed = state.eventosCollapsedGroups.has(tipo);
                    rows.push(`
                        <tr class="group-row">
                            <td colspan="6">
                                <div class="group-row-content">
                                    <button type="button" class="group-toggle-btn ${collapsed ? 'is-collapsed' : ''}" data-group-key="${groupKey}" aria-expanded="${collapsed ? 'false' : 'true'}" title="${collapsed ? 'Expandir grupo' : 'Colapsar grupo'}">
                                        <span class="group-toggle-icon">▾</span>
                                        <span class="group-row-label">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M4 5h6v6H4zM14 5h6v4h-6zM14 13h6v6h-6zM4 15h6v4H4z"></path>
                                            </svg>
                                            ${tipo}
                                        </span>
                                    </button>
                                    <span class="group-row-count">${events.length} evento(s)</span>
                                </div>
                            </td>
                        </tr>
                    `);
                    if (!collapsed) rows.push(renderEventosRows(events));
                }
                tbody.innerHTML = rows.join('');
            } else {
                tbody.innerHTML = renderEventosRows(filtered);
            }

            updateSortHeadersUi();
            hookRowChecks();
            hookGroupCollapseButtons();
            updateBatchBar();
        }

        function hookSortHeaders() {
            document.querySelectorAll('.sort-header-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const key = btn.dataset.sortKey;
                    if (!key) return;
                    if (state.eventosSort.key === key) {
                        state.eventosSort.dir = state.eventosSort.dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        state.eventosSort.key = key;
                        state.eventosSort.dir = key === 'fecha' ? 'desc' : 'asc';
                    }
                    renderEventos();
                });
            });
        }

        function hookGroupToggle() {
            const btn = document.getElementById('toggle-group-eventos');
            const label = document.getElementById('group-eventos-label');
            if (!btn || !label) return;
            const updateButton = () => {
                label.textContent = state.eventosGroupByTipo ? 'Agrupado' : 'Agrupar';
                btn.classList.toggle('border-blue-500/60', state.eventosGroupByTipo);
                btn.classList.toggle('text-blue-300', state.eventosGroupByTipo);
                btn.classList.toggle('bg-blue-950/45', state.eventosGroupByTipo);
                btn.classList.toggle('shadow-[0_0_0_1px_rgba(59,130,246,.22)_inset]', state.eventosGroupByTipo);
                btn.setAttribute('aria-pressed', state.eventosGroupByTipo ? 'true' : 'false');
                btn.title = state.eventosGroupByTipo ? 'Quitar agrupación por tipo de señal' : 'Agrupar por tipo de señal';
            };
            btn.addEventListener('click', () => {
                state.eventosGroupByTipo = !state.eventosGroupByTipo;
                updateButton();
                renderEventos();
            });
            updateButton();
        }

        function updateBatchBar() {
            const n = state.selected.size;
            const bar = document.getElementById('batchbar');
            document.getElementById('selected-count').textContent = String(n);
            bar.classList.toggle('hidden', n === 0);
        }

        function hookRowChecks() {
            document.querySelectorAll('.row-check').forEach(el => {
                el.addEventListener('change', () => {
                    const id = Number(el.dataset.id);
                    if (el.checked) state.selected.add(id);
                    else state.selected.delete(id);
                    updateBatchBar();
                });
            });
        }

        document.getElementById('select-all').addEventListener('change', (e) => {
            const checked = e.target.checked;
            document.querySelectorAll('.row-check').forEach(el => {
                el.checked = checked;
                const id = Number(el.dataset.id);
                if (checked) state.selected.add(id);
                else state.selected.delete(id);
            });
            updateBatchBar();
        });

        document.getElementById('clear-selection').addEventListener('click', () => {
            state.selected.clear();
            document.getElementById('select-all').checked = false;
            renderEventos();
        });

        document.getElementById('eventos-search').addEventListener('input', () => renderEventos());

        // ===== Modal Cedulación =====
        const modal = document.getElementById('cedular-modal');
        const msgBox = document.getElementById('cedular-msg');
        const cedularPanel = document.getElementById('cedular-panel');

        function showMsg(kind, text) {
            msgBox.classList.remove('hidden');
            msgBox.className = 'text-sm rounded-lg border p-3';
            if (kind === 'ok') msgBox.classList.add('border-emerald-900','bg-emerald-950','text-emerald-200');
            else msgBox.classList.add('border-red-900','bg-red-950','text-red-200');
            msgBox.textContent = text;
        }

        function hideMsg() { msgBox.classList.add('hidden'); msgBox.textContent=''; }

        async function fetchJsonWithTimeout(url, options = {}, timeoutMs = 7000) {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), timeoutMs);
            try {
                const res = await fetch(url, { ...options, signal: controller.signal });
                const data = await res.json().catch(() => null);
                if (res.status === 401 && data?.session_expired) {
                    window.location.href = @json(route('login.form'));
                }
                return { ok: res.ok, status: res.status, data };
            } finally {
                clearTimeout(timer);
            }
        }

        async function ensureCedulacionPresets() {
            if (!state.cedulacionTipos) {
                const tiposRes = await fetchJsonWithTimeout('{{ route('x.cedulacion.tipos') }}', {}, 6000);
                state.cedulacionTipos = tiposRes.ok
                    ? (tiposRes.data ?? { data: [] })
                    : { data: [] };
            }
            if (!state.cedulacionObservaciones) {
                const obsRes = await fetchJsonWithTimeout('{{ route('x.cedulacion.observaciones') }}', {}, 6000);
                state.cedulacionObservaciones = obsRes.ok
                    ? (obsRes.data ?? { data: [] })
                    : { data: [] };
            }
        }

        function populateSelect(selectEl, items, { valueKey='id', labelKey='nombre', placeholder='Seleccionar' } = {}) {
            selectEl.innerHTML = '';
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = placeholder;
            selectEl.appendChild(opt0);
            for (const item of (items || [])) {
                const opt = document.createElement('option');
                opt.value = item[valueKey];
                opt.textContent = item[labelKey] ?? String(item[valueKey]);
                selectEl.appendChild(opt);
            }
        }

        function getEventoObjetivoId(ev) {
            return Number(
                ev?.idObjetivo
                ?? ev?.objetivoId
                ?? ev?.objetivo_id
                ?? 0
            );
        }

        function getEventoObjetivoNombre(ev) {
            return String(ev?.objetivo ?? ev?.objetivoNombre ?? ev?.nombreObjetivo ?? `Objetivo ${getEventoObjetivoId(ev)}`);
        }

        async function fetchContactosByObjetivo(objetivoId) {
            if (!objetivoId) return [];
            const cacheKey = String(objetivoId);
            if (Array.isArray(state.contactosByObjetivo[cacheKey])) {
                return state.contactosByObjetivo[cacheKey];
            }

            const url = CONTACTOS_ROUTE_TEMPLATE.replace('__OBJETIVO__', String(objetivoId));
            const data = await fetchJsonWithTimeout(url, {}, 6000).then(r => r.data).catch(() => ({}));
            const contactos = Array.isArray(data?.data) ? data.data : [];
            state.contactosByObjetivo[cacheKey] = contactos;
            return contactos;
        }

        function renderContactos(contactos) {
            const empty = document.getElementById('contactos-empty');
            const list = document.getElementById('contactos-list');

            if (!Array.isArray(contactos) || contactos.length === 0) {
                empty.classList.remove('hidden');
                list.classList.add('hidden');
                list.innerHTML = '';
                return;
            }

            empty.classList.add('hidden');
            list.classList.remove('hidden');
            list.innerHTML = contactos.map((c) => {
                const nombre = c.nombre ?? c.apellido_nombre ?? 'Contacto';
                const telefono = c.telefono ?? c.celular ?? c.numero ?? '—';
                return `<div class="rounded border border-slate-700/70 px-3 py-2">
                    <div class="font-medium text-slate-100">${nombre}</div>
                    <div class="text-slate-300">${telefono}</div>
                </div>`;
            }).join('');
        }

        function renderSelectedEventsPreview(selectedEvents) {
            const wrap = document.getElementById('cedular-multi-list');
            const tbody = document.getElementById('cedular-multi-tbody');
            if (!Array.isArray(selectedEvents) || selectedEvents.length <= 1) {
                wrap.classList.add('hidden');
                tbody.innerHTML = '';
                return;
            }

            wrap.classList.remove('hidden');
            tbody.innerHTML = selectedEvents.map((e) => `
                <tr class="border-t border-slate-700/70">
                    <td class="p-2 text-slate-200">${e.idEvento ?? ''}</td>
                    <td class="p-2 text-slate-100">${e.tipoSenal ?? ''}</td>
                    <td class="p-2 text-slate-200">${e.fecha ?? ''}</td>
                    <td class="p-2 text-slate-100">${e.objetivo ?? ''}</td>
                    <td class="p-2 text-slate-300">${e.zona ?? ''}</td>
                </tr>
            `).join('');
        }

        async function openCedularModal() {
            hideMsg();
            const ids = Array.from(state.selected.values());
            if (ids.length === 0) return;

            const firstId = ids[0];
            const ev = state.eventos.find(e => Number(e.idEvento) === Number(firstId));
            const selectedEvents = state.eventos.filter(e => ids.includes(Number(e.idEvento)));
            const isMultiple = selectedEvents.length > 1;

            document.getElementById('cedular-subtitle').textContent = isMultiple
                ? `Cedular ${selectedEvents.length} eventos`
                : 'Cedular evento';
            document.getElementById('cedular-title').textContent = isMultiple
                ? (selectedEvents[0]?.tipoSenal ?? 'Eventos seleccionados')
                : (ev?.tipoSenal ?? 'Evento');
            document.getElementById('cedular-objetivo').textContent = isMultiple
                ? (selectedEvents[0]?.objetivo ?? '—')
                : (ev?.objetivo ?? '—');
            document.getElementById('cedular-fecha').textContent = isMultiple
                ? (selectedEvents[0]?.fecha ?? '—')
                : (ev?.fecha ?? '—');
            document.getElementById('cedular-zona').textContent = isMultiple
                ? (selectedEvents[0]?.zona ?? '—')
                : (ev?.zona ?? '—');
            renderSelectedEventsPreview(selectedEvents);

            // Abrimos el modal primero para que el botón siempre responda.
            modal.classList.remove('hidden');

            try {
                await ensureCedulacionPresets();
            } catch (_) {
                state.cedulacionTipos = { data: [] };
                state.cedulacionObservaciones = { data: [] };
                showMsg('err', 'No se pudieron cargar tipos/observaciones. Reintentá en unos segundos.');
            }

            const tipos = state.cedulacionTipos?.data ?? [];
            const obs = state.cedulacionObservaciones?.data ?? [];
            populateSelect(document.getElementById('cedulacion-tipo'), tipos, { valueKey: 'id', labelKey: 'nombre', placeholder: 'Seleccionar' });
            populateSelect(document.getElementById('obs-predef'), obs, { valueKey: 'id', labelKey: 'nombre', placeholder: 'Click para ver opciones' });

            document.getElementById('obs-predef').value = '';
            document.getElementById('obs-text').value = '';
            const objetivosMap = new Map();
            for (const e of selectedEvents) {
                const objetivoId = getEventoObjetivoId(e);
                if (!objetivoId) continue;
                if (!objetivosMap.has(objetivoId)) {
                    objetivosMap.set(objetivoId, getEventoObjetivoNombre(e));
                }
            }

            const objetivos = Array.from(objetivosMap, ([id, nombre]) => ({ id, nombre }));
            const objetivoSelect = document.getElementById('contactos-objetivo');
            populateSelect(objetivoSelect, objetivos, { valueKey: 'id', labelKey: 'nombre', placeholder: 'Seleccionar objetivo' });

            if (objetivos.length > 0) {
                objetivoSelect.value = String(objetivos[0].id);
                try {
                    const contactos = await fetchContactosByObjetivo(objetivos[0].id);
                    renderContactos(contactos);
                } catch (_) {
                    renderContactos([]);
                }
            } else {
                renderContactos([]);
            }
        }

        function closeCedularModal() {
            modal.classList.add('hidden');
        }

        document.getElementById('open-cedular').addEventListener('click', openCedularModal);
        document.getElementById('cedular-close').addEventListener('click', closeCedularModal);
        document.getElementById('cedular-cancel').addEventListener('click', closeCedularModal);

        document.getElementById('obs-predef').addEventListener('change', (e) => {
            const id = Number(e.target.value || 0);
            if (!id) {
                document.getElementById('obs-text').value = '';
                return;
            }
            const obs = (state.cedulacionObservaciones?.data ?? []).find(o => Number(o.id) === id);
            const texto = obs?.nombre ?? obs?.observacion ?? '';
            document.getElementById('obs-text').value = texto;
        });

        document.getElementById('contactos-objetivo').addEventListener('change', async (e) => {
            const objetivoId = Number(e.target.value || 0);
            const contactos = await fetchContactosByObjetivo(objetivoId);
            renderContactos(contactos);
        });

        document.getElementById('cedular-save').addEventListener('click', async () => {
            hideMsg();
            const ids = Array.from(state.selected.values());
            // Capturamos objetivos relacionados antes del refresh de eventos.
            const objetivosSeleccionados = new Set(
                state.eventos
                    .filter((e) => ids.includes(Number(e.idEvento)))
                    .map((e) => Number(getEventoObjetivoId(e)))
                    .filter((n) => Number.isFinite(n) && n > 0)
            );
            const cedulacionTipoId = Number(document.getElementById('cedulacion-tipo').value || 0);
            const observaciones = document.getElementById('obs-text').value || null;

            if (!cedulacionTipoId) {
                showMsg('err', 'Seleccioná un tipo de señal.');
                return;
            }

            const result = await fetchJsonWithTimeout('{{ route('x.cedulacion.guardar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({
                    eventos: ids,
                    cedulacion_tipo_id: cedulacionTipoId,
                    observaciones,
                })
            }, 9000).catch(() => ({ ok: false, data: null }));

            const data = result.data;
            if (result.ok) {
                showMsg('ok', data?.message ?? 'Cedulación guardada.');
                objetivosSeleccionados.forEach((id) => removeCriticalAlertByObjetivoId(id));
                // refrescar eventos (los cedulados deberían desaparecer)
                await refreshEventos();
                state.selected.clear();
                document.getElementById('select-all').checked = false;
                renderEventos();
                setTimeout(() => closeCedularModal(), 600);
            } else {
                showMsg('err', data?.message ?? 'No se pudo guardar la cedulación.');
                // si hubo conflicto, refrescamos igual
                await refreshEventos();
                renderEventos();
            }
        });

        // ===== Mapa =====
        let map;
        let markersLayer;
        let tilesLayer;

        function markerColorByEstado(estado) {
            const st = String(estado || '').toUpperCase();
            if (st === 'CRITICO') return '#ef4444';
            if (st === 'ONLINE') return '#38bdf8';
            if (st === 'OFFLINE') return '#e5e7eb';
            if (st === 'MUERTO') return '#fb923c';
            return '#a78bfa';
        }

        function initMap() {
            // Vista inicial: Provincia de Entre Ríos (aprox.)
            map = L.map('map', { zoomControl: true }).setView([-32.06, -59.23], 7);

            // Blindaje de z-index entre tiles y marcadores:
            // algunos cambios del DOM/tiles pueden terminar “apilando” los panes raro.
            // Dejamos markerPane arriba de tilePane, pero como el modal tiene z-index muy alto,
            // el modal seguirá superponiéndose al mapa.
            const tilePane = map.getPane('tilePane');
            const markerPane = map.getPane('markerPane');
            if (tilePane) tilePane.style.zIndex = '200';
            if (markerPane) markerPane.style.zIndex = '650';

            // Tiles same-origin (proxy) para evitar bloqueos del navegador a terceros.
            const cartoTpl = @json($cartoTileTemplate);
            const stadiaTpl = @json($stadiaTileTemplate);

            const useCarto = () => {
                if (tilesLayer) {
                    try { map.removeLayer(tilesLayer); } catch {}
                }
                tilesLayer = L.tileLayer(cartoTpl, { maxZoom: 18 }).addTo(map);
            };

            // Carto por defecto (proxy same-origin). Stadia solo con MAP_USE_STADIA + STADIA_KEY reales.
            if (USE_STADIA_BASEMAP && STADIA_KEY) {
                tilesLayer = L.tileLayer(stadiaTpl, { maxZoom: 18 });
                tilesLayer.on('tileerror', () => useCarto());
                tilesLayer.addTo(map);
            } else {
                useCarto();
            }

            markersLayer = L.layerGroup().addTo(map);
            renderMarkers();
        }

        function renderMarkers() {
            if (!markersLayer) return;
            markersLayer.clearLayers();
            let n = 0;

            for (const o of state.objetivos) {
                const obj = normalizeObjetivo(o);
                const lat = obj?.ubicacion?.latitud;
                const lon = obj?.ubicacion?.longitud;
                if (typeof lat !== 'number' || typeof lon !== 'number') continue;
                n += 1;
                const color = markerColorByEstado(obj.estado);
                const icon = L.divIcon({
                    className: '',
                    html: `<div style="width:14px;height:14px;border-radius:999px;background:${color};box-shadow:0 0 0 2px rgba(0,0,0,.55), 0 0 14px ${color}55"></div>`,
                });
                // Fuerza el pane para evitar que queden detrás de los tiles.
                const marker = L.marker([lat, lon], { icon, pane: 'markerPane' });
                marker.bindPopup(`<div style="color:#0f172a"><b>${obj.nombre ?? obj.descripcion ?? 'Objetivo'}</b><br/>Estado: ${obj.estado ?? ''}</div>`);
                marker.addTo(markersLayer);
            }
            document.getElementById('map-count').textContent = String(n);

            // Si hay alerta crítica pendiente, mantenemos foco en el último crítico alertado.
            if (state.lastFocusedCriticalObjetivoId) {
                focusMapOnObjetivo(state.lastFocusedCriticalObjetivoId);
            } else if (n > 0) {
                // Autocentrar si hay marcadores y no hay alertas críticas pendientes.
                const latLngs = [];
                for (const o of state.objetivos) {
                    const obj = normalizeObjetivo(o);
                    const lat = obj?.ubicacion?.latitud;
                    const lon = obj?.ubicacion?.longitud;
                    if (typeof lat === 'number' && typeof lon === 'number') latLngs.push([lat, lon]);
                }
                if (latLngs.length > 0) {
                    const bounds = L.latLngBounds(latLngs);
                    map.fitBounds(bounds, {
                        padding: [24, 24],
                        maxZoom: 12,
                        animate: true,
                        duration: mapAnimation.resetDuration,
                        easeLinearity: mapAnimation.easeLinearity,
                    });
                }
            }
        }

        // ===== Refresh + SSE =====
        async function refreshEventos() {
            const result = await fetchJsonWithTimeout('{{ route('x.eventos') }}', {}, 6000).catch(() => ({ ok: false, data: null }));
            const data = result.data;
            state.eventos = Array.isArray(data) ? data : (Array.isArray(state.eventos) ? state.eventos : []);
            document.getElementById('eventos-updated').textContent = new Date().toLocaleTimeString();
            syncCriticalAlertsWithEventos();
            renderEventos();
        }

        async function refreshObjetivos() {
            const result = await fetchJsonWithTimeout('{{ route('x.objetivos') }}', {}, 6000).catch(() => ({ ok: false, data: null }));
            const data = result.data ?? {};
            const incoming = Array.isArray(data?.data) ? data.data : [];
            state.objetivos = mergeObjetivosPreservingCoords(state.objetivos, incoming);
            await hydrateObjetivosCoordsIfNeeded();
            renderCounts();
            renderMarkers();
        }

        async function hydrateObjetivosCoordsIfNeeded() {
            const now = Date.now();
            // Evita tormenta de requests contra /objetivos/{id}
            if (state.coordsHydrationInFlight) return;
            if (now - state.lastCoordsHydrationAt < 20000) return;

            const sinCoords = state.objetivos.filter((o) => {
                const n = normalizeObjetivo(o);
                return !(n?.ubicacion && typeof n.ubicacion.latitud === 'number' && typeof n.ubicacion.longitud === 'number');
            });

            // Si no hay faltantes, no hacemos nada.
            if (sinCoords.length === 0) return;

            state.coordsHydrationInFlight = true;
            state.lastCoordsHydrationAt = now;
            try {
                // Límite conservador por ciclo para no saturar la API.
                const target = sinCoords.slice(0, 8);
                await Promise.allSettled(target.map(async (o) => {
                    const id = Number(o?.id || 0);
                    if (!id) return;
                    const url = OBJETIVO_DETALLE_ROUTE_TEMPLATE.replace('__OBJETIVO__', String(id));
                    const detail = await fetchJsonWithTimeout(url, {}, 5000).then(r => r.ok ? r.data : null).catch(() => null);
                    if (!detail) return;
                    const idx = state.objetivos.findIndex((x) => Number(x.id) === id);
                    if (idx >= 0) {
                        state.objetivos[idx] = normalizeObjetivo({ ...state.objetivos[idx], ...detail });
                    }
                }));
            } finally {
                state.coordsHydrationInFlight = false;
            }
        }

        function startSSE() {
            const es = new EventSource('{{ route('x.sse.dashboard') }}');
            es.onmessage = () => {};
            es.addEventListener('init-eventos', (e) => {
                try { state.eventos = JSON.parse(e.data) ?? []; } catch {}
                document.getElementById('eventos-updated').textContent = new Date().toLocaleTimeString();
                syncCriticalAlertsWithEventos();
                renderEventos();
            });
            es.addEventListener('init-objetivos', (e) => {
                try {
                    const arr = JSON.parse(e.data) ?? [];
                    const incoming = Array.isArray(arr) ? arr : [];
                    state.objetivos = mergeObjetivosPreservingCoords(state.objetivos, incoming);
                    // Barrido inicial: todo objetivo en CRITICO debe alertar/apilarse.
                    for (const o of state.objetivos) {
                        const id = Number(o.id || 0);
                        const curr = String(o.estado || '').toUpperCase();
                        if (id > 0 && curr === 'CRITICO') addCriticalAlert(id);
                    }
                    clearCriticalAlertsIfNoCriticalObjetivos();
                } catch {}
                syncCriticalAlertsWithEventos();
                renderCounts();
                renderMarkers();
            });
            es.addEventListener('new-eventos', (e) => {
                try {
                    const ev = JSON.parse(e.data);
                    if (ev) state.eventos = [ev, ...state.eventos];
                } catch {}
                document.getElementById('eventos-updated').textContent = new Date().toLocaleTimeString();
                syncCriticalAlertsWithEventos();
                renderEventos();
            });
            es.addEventListener('new-objetivos', (e) => {
                try {
                    const up = normalizeObjetivo(JSON.parse(e.data));
                    if (up?.id) {
                        const idx = state.objetivos.findIndex(o => Number(o.id) === Number(up.id));
                        if (idx >= 0) {
                            const prev = normalizeObjetivo(state.objetivos[idx]);
                            const merged = { ...prev, ...up };
                            if (!hasCoords(merged) && hasCoords(prev)) merged.ubicacion = prev.ubicacion;
                            state.objetivos[idx] = merged;
                            const prevState = String(prev?.estado || '').toUpperCase();
                            const newState = String(merged?.estado || '').toUpperCase();
                            if (newState === 'CRITICO' && prevState !== 'CRITICO') addCriticalAlert(Number(merged.id));
                        }
                        else {
                            state.objetivos.push(up);
                            if (String(up?.estado || '').toUpperCase() === 'CRITICO') addCriticalAlert(Number(up.id));
                        }
                        clearCriticalAlertsIfNoCriticalObjetivos();
                    }
                } catch {}
                syncCriticalAlertsWithEventos();
                renderCounts();
                renderMarkers();
            });
            es.onerror = async () => {
                // fallback: si el proxy SSE cae, refrescamos y esperamos reconexión automática del navegador
                await Promise.allSettled([refreshEventos(), refreshObjetivos()]);
            };
        }

        function waitForLeafletReady(cb) {
            const start = Date.now();
            const tick = () => {
                if (window.L) return cb();
                if (Date.now() - start > 8000) {
                    console.error('Leaflet no cargó (window.L indefinido).');
                    return;
                }
                setTimeout(tick, 50);
            };
            tick();
        }

        // Boot (espera a que Vite cargue Leaflet)
        document.addEventListener('DOMContentLoaded', () => {
            hookSortHeaders();
            hookGroupToggle();
            renderEventos();
            renderCounts();
            waitForLeafletReady(() => {
                initMap();
                // Carga inicial para que haya marcadores sin esperar al SSE
                Promise.allSettled([refreshObjetivos(), refreshEventos()]);
                startSSE();
            });
        });
    </script>
@endpush

