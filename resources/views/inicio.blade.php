<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .card { border: 1px solid rgb(30 41 59 / 1); background: rgb(15 23 42 / .55); }
        .panel { border: 1px solid rgb(30 41 59 / 1); background: rgb(15 23 42 / .55); }
        .table-row { border-bottom: 1px solid rgb(30 41 59 / 1); }
        .table-row:hover { background: rgb(2 6 23 / .35); }
        #map { height: 420px; }
        .modal-backdrop { background: rgba(2,6,23,.7); }
        .chip { border: 1px solid rgb(51 65 85 / 1); background: rgb(2 6 23 / .35); }
        /* Si los tiles tardan en cargar, evitamos “fondo vacío” */
        .leaflet-container { background: #0b1220; }
        #eventos-scroll { max-height: 420px; overflow-y: auto; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 p-4 md:p-6">
    <div class="max-w-7xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Inicio</h1>
                <p class="text-sm text-slate-400">Monitoreo en tiempo real</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm hover:bg-slate-900">
                    Salir
                </button>
            </form>
        </div>

        {{-- Cards superiores --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Eventos críticos</div>
                        <div class="text-xs text-slate-400">Requieren atención inmediata</div>
                    </div>
                    <div class="w-3.5 h-3.5 rounded bg-red-500/80"></div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-critico">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos conectados</div>
                        <div class="text-xs text-slate-400">Comunicación activa</div>
                    </div>
                    <div class="w-3.5 h-3.5 rounded border-2 border-sky-400/80"></div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-online">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivo inactivo</div>
                        <div class="text-xs text-slate-400">Sin comunicación</div>
                    </div>
                    <div class="w-3.5 h-3.5 rounded border-2 border-slate-300/70"></div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-offline">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos sin señal</div>
                        <div class="text-xs text-slate-400">Desconectados</div>
                    </div>
                    <div class="w-3.5 h-3.5 rounded border-2 border-orange-400/80"></div>
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
                    <div class="text-xs text-slate-400">
                        <a class="underline underline-offset-4 hover:text-slate-200" href="{{ route('debug') }}">Debug</a>
                    </div>
                </div>

                <div id="batchbar" class="hidden px-4 py-2 bg-blue-600/90 text-white text-sm flex items-center justify-between">
                    <div><span id="selected-count">0</span> evento(s) seleccionado(s)</div>
                    <div class="flex items-center gap-4">
                        <button id="open-cedular" class="underline underline-offset-4">Cedular eventos</button>
                        <button id="clear-selection" class="underline underline-offset-4 opacity-90 hover:opacity-100">Cancelar</button>
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
                                    <th class="p-2 text-left">Tipo de señal</th>
                                    <th class="p-2 text-left">Fecha y Hora</th>
                                    <th class="p-2 text-left">Objetivo</th>
                                    <th class="p-2 text-left">Zona</th>
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
    </div>

    {{-- Modal cedulación --}}
    <div id="cedular-modal" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 modal-backdrop"></div>
        <div class="relative mx-auto max-w-4xl mt-10 bg-slate-900/95 border border-slate-800 rounded-xl shadow-2xl shadow-black/40 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
                <div>
                    <div class="text-sm text-slate-400">Cedular evento</div>
                    <div class="text-2xl font-semibold" id="cedular-title">—</div>
                    <div class="text-sm text-slate-200 mt-1" id="cedular-objetivo">—</div>
                    <div class="text-xs text-slate-400 mt-2">
                        Fecha y hora: <span id="cedular-fecha">—</span> · Zona: <span id="cedular-zona">—</span>
                    </div>
                </div>
                <button id="cedular-close" class="text-slate-300 hover:text-white text-xl leading-none">×</button>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-blue-600/90 p-4 text-center">
                            <div class="text-2xl font-semibold">911</div>
                            <div class="text-sm">Policía</div>
                        </div>
                        <div class="rounded-lg bg-red-600/90 p-4 text-center">
                            <div class="text-2xl font-semibold">100</div>
                            <div class="text-sm">Bomberos</div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-800 chip p-3">
                        <div class="text-xs text-slate-400 mb-1">Contactos por objetivo</div>
                        <select id="contactos-objetivo" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm">
                            <option value="">—</option>
                        </select>
                        <div id="contactos-empty" class="mt-3 text-slate-300">
                            <div class="text-lg font-medium">No hay contactos</div>
                            <div class="text-sm text-slate-400">No hay contactos cargados para este objetivo.</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Tipo de señal</label>
                        <select id="cedulacion-tipo" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm">
                            <option value="">Cargando…</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Observaciones predefinidas</label>
                        <select id="obs-predef" class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm">
                            <option value="">Cargando…</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Observaciones</label>
                        <textarea id="obs-text" rows="5"
                                  class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400/40"
                                  placeholder="Observaciones del evento"></textarea>
                    </div>
                    <div id="cedular-msg" class="hidden text-sm rounded-lg border p-3"></div>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-slate-800 flex items-center justify-between bg-slate-950/40">
                <button id="cedular-cancel" class="text-slate-300 hover:text-white underline underline-offset-4">Cancelar</button>
                <button id="cedular-save" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold hover:bg-blue-500">
                    Guardar cedulación
                </button>
            </div>
        </div>
    </div>

    <script>
        const INITIAL_EVENTOS = @json($eventos);
        const INITIAL_OBJETIVOS = @json($objetivos);
        const STADIA_KEY = @json($stadiaKey);

        const state = {
            eventos: Array.isArray(INITIAL_EVENTOS) ? INITIAL_EVENTOS : [],
            objetivos: Array.isArray(INITIAL_OBJETIVOS) ? INITIAL_OBJETIVOS : [],
            selected: new Set(),
            cedulacionTipos: null,
            cedulacionObservaciones: null,
        };

        function fmtDate(s) {
            if (!s) return '—';
            return String(s).replace('T', ' ').replace('.000000Z', '');
        }

        function normalizeObjetivo(o) {
            if (!o || typeof o !== 'object') return o;
            // API /objetivos (Resource) trae ubicacion{latitud,longitud}
            if (o.ubicacion && typeof o.ubicacion === 'object') return o;
            // SSE objetivos trae latitud/longitud al tope
            const lat = (typeof o.latitud === 'number') ? o.latitud : (o.latitud ? Number(o.latitud) : null);
            const lon = (typeof o.longitud === 'number') ? o.longitud : (o.longitud ? Number(o.longitud) : null);
            if (Number.isFinite(lat) && Number.isFinite(lon)) {
                return { ...o, ubicacion: { latitud: lat, longitud: lon } };
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

        function renderCounts() {
            const c = computeObjetivosStates(state.objetivos);
            document.getElementById('count-critico').textContent = c.CRITICO ?? 0;
            document.getElementById('count-online').textContent = c.ONLINE ?? 0;
            document.getElementById('count-offline').textContent = c.OFFLINE ?? 0;
            document.getElementById('count-muerto').textContent = c.MUERTO ?? 0;
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

            // Solo mostramos hasta 30 eventos para no desarmar la pantalla
            const filtered = filteredAll.slice(0, 30);

            document.getElementById('eventos-total').textContent = String(filteredAll.length);
            tbody.innerHTML = filtered.map(e => {
                const checked = state.selected.has(e.idEvento) ? 'checked' : '';
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

            hookRowChecks();
            updateBatchBar();
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

        function showMsg(kind, text) {
            msgBox.classList.remove('hidden');
            msgBox.className = 'text-sm rounded-lg border p-3';
            if (kind === 'ok') msgBox.classList.add('border-emerald-900/50','bg-emerald-950/40','text-emerald-200');
            else msgBox.classList.add('border-red-900/50','bg-red-950/40','text-red-200');
            msgBox.textContent = text;
        }

        function hideMsg() { msgBox.classList.add('hidden'); msgBox.textContent=''; }

        async function ensureCedulacionPresets() {
            if (!state.cedulacionTipos) {
                state.cedulacionTipos = await fetch('{{ route('x.cedulacion.tipos') }}').then(r => r.json());
            }
            if (!state.cedulacionObservaciones) {
                state.cedulacionObservaciones = await fetch('{{ route('x.cedulacion.observaciones') }}').then(r => r.json());
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

        async function openCedularModal() {
            hideMsg();
            const ids = Array.from(state.selected.values());
            if (ids.length === 0) return;

            const firstId = ids[0];
            const ev = state.eventos.find(e => Number(e.idEvento) === Number(firstId));
            document.getElementById('cedular-title').textContent = ev?.tipoSenal ?? 'Evento';
            document.getElementById('cedular-objetivo').textContent = ev?.objetivo ?? '—';
            document.getElementById('cedular-fecha').textContent = ev?.fecha ?? '—';
            document.getElementById('cedular-zona').textContent = ev?.zona ?? '—';

            await ensureCedulacionPresets();
            const tipos = state.cedulacionTipos?.data ?? [];
            const obs = state.cedulacionObservaciones?.data ?? [];
            populateSelect(document.getElementById('cedulacion-tipo'), tipos, { valueKey: 'id', labelKey: 'nombre', placeholder: 'Seleccionar' });
            populateSelect(document.getElementById('obs-predef'), obs, { valueKey: 'id', labelKey: 'observacion', placeholder: 'Click para ver opciones' });

            document.getElementById('obs-predef').value = '';
            document.getElementById('obs-text').value = '';

            // contactos (solo para “familiaridad”; MVP muestra empty si no hay)
            document.getElementById('contactos-objetivo').innerHTML = '<option value="">—</option>';
            document.getElementById('contactos-empty').classList.remove('hidden');

            modal.classList.remove('hidden');
        }

        function closeCedularModal() {
            modal.classList.add('hidden');
        }

        document.getElementById('open-cedular').addEventListener('click', openCedularModal);
        document.getElementById('cedular-close').addEventListener('click', closeCedularModal);
        document.getElementById('cedular-cancel').addEventListener('click', closeCedularModal);

        document.getElementById('obs-predef').addEventListener('change', (e) => {
            const id = Number(e.target.value || 0);
            if (!id) return;
            const obs = (state.cedulacionObservaciones?.data ?? []).find(o => Number(o.id) === id);
            if (obs?.observacion) document.getElementById('obs-text').value = obs.observacion;
        });

        document.getElementById('cedular-save').addEventListener('click', async () => {
            hideMsg();
            const ids = Array.from(state.selected.values());
            const cedulacionTipoId = Number(document.getElementById('cedulacion-tipo').value || 0);
            const observaciones = document.getElementById('obs-text').value || null;

            if (!cedulacionTipoId) {
                showMsg('err', 'Seleccioná un tipo de señal.');
                return;
            }

            const res = await fetch('{{ route('x.cedulacion.guardar') }}', {
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
            });

            const data = await res.json().catch(() => null);
            if (res.ok) {
                showMsg('ok', data?.message ?? 'Cedulación guardada.');
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

            // Tiles same-origin (proxy) para evitar bloqueos del navegador a terceros.
            const cartoTpl = @json($cartoTileTemplate);
            const stadiaTpl = @json($stadiaTileTemplate);

            const useCarto = () => {
                if (tilesLayer) {
                    try { map.removeLayer(tilesLayer); } catch {}
                }
                tilesLayer = L.tileLayer(cartoTpl, { maxZoom: 18 }).addTo(map);
            };

            if (STADIA_KEY) {
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
                const marker = L.marker([lat, lon], { icon });
                marker.bindPopup(`<div style="color:#0f172a"><b>${obj.nombre ?? obj.descripcion ?? 'Objetivo'}</b><br/>Estado: ${obj.estado ?? ''}</div>`);
                marker.addTo(markersLayer);
            }
            document.getElementById('map-count').textContent = String(n);

            // Autocentrar si hay marcadores
            if (n > 0) {
                const latLngs = [];
                for (const o of state.objetivos) {
                    const obj = normalizeObjetivo(o);
                    const lat = obj?.ubicacion?.latitud;
                    const lon = obj?.ubicacion?.longitud;
                    if (typeof lat === 'number' && typeof lon === 'number') latLngs.push([lat, lon]);
                }
                if (latLngs.length > 0) {
                    const bounds = L.latLngBounds(latLngs);
                    map.fitBounds(bounds, { padding: [24, 24], maxZoom: 12 });
                }
            }
        }

        // ===== Refresh + SSE =====
        async function refreshEventos() {
            const data = await fetch('{{ route('x.eventos') }}').then(r => r.json());
            state.eventos = Array.isArray(data) ? data : [];
            document.getElementById('eventos-updated').textContent = new Date().toLocaleTimeString();
            renderEventos();
        }

        async function refreshObjetivos() {
            const data = await fetch('{{ route('x.objetivos') }}').then(r => r.json());
            state.objetivos = Array.isArray(data?.data) ? data.data : [];
            renderCounts();
            renderMarkers();
        }

        function startSSE() {
            const es = new EventSource('{{ route('x.sse.dashboard') }}');
            es.onmessage = () => {};
            es.addEventListener('init-eventos', (e) => {
                try { state.eventos = JSON.parse(e.data) ?? []; } catch {}
                document.getElementById('eventos-updated').textContent = new Date().toLocaleTimeString();
                renderEventos();
            });
            es.addEventListener('init-objetivos', (e) => {
                try {
                    const arr = JSON.parse(e.data) ?? [];
                    state.objetivos = Array.isArray(arr) ? arr.map(normalizeObjetivo) : [];
                } catch {}
                renderCounts();
                renderMarkers();
            });
            es.addEventListener('new-eventos', (e) => {
                try {
                    const ev = JSON.parse(e.data);
                    if (ev) state.eventos = [ev, ...state.eventos];
                } catch {}
                document.getElementById('eventos-updated').textContent = new Date().toLocaleTimeString();
                renderEventos();
            });
            es.addEventListener('new-objetivos', (e) => {
                try {
                    const up = normalizeObjetivo(JSON.parse(e.data));
                    if (up?.id) {
                        const idx = state.objetivos.findIndex(o => Number(o.id) === Number(up.id));
                        if (idx >= 0) state.objetivos[idx] = { ...state.objetivos[idx], ...up };
                        else state.objetivos.push(up);
                    }
                } catch {}
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
</body>
</html>

