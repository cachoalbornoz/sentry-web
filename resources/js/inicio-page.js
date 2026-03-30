import { fetchJsonWithSession } from './shared/http';
import {
    countObjetivosByEstado,
    getEventoObjetivoId,
    getEventoObjetivoNombre,
    getObjetivoNameById,
} from './shared/objetivo-utils';
import { bootWhenReady } from './shared/page-boot';

function readInicioConfig() {
    const configEl = document.getElementById('inicio-page-config');
    if (!configEl) return null;

    try {
        return JSON.parse(configEl.dataset.config || '{}');
    } catch (_) {
        return null;
    }
}

function init() {
    const config = readInicioConfig();
    if (!config) return;

    const INITIAL_EVENTOS = Array.isArray(config.initialEventos) ? config.initialEventos : [];
    const INITIAL_OBJETIVOS = Array.isArray(config.initialObjetivos) ? config.initialObjetivos : [];
    const STADIA_KEY = config.stadiaKey || '';
    const USE_STADIA_BASEMAP = Boolean(config.useStadiaBasemap);
    const CONTACTOS_ROUTE_TEMPLATE = config.contactosRouteTemplate || '';
    const OBJETIVO_DETALLE_ROUTE_TEMPLATE = config.objetivoDetalleRouteTemplate || '';

    const state = {
        eventos: INITIAL_EVENTOS,
        objetivos: INITIAL_OBJETIVOS,
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

    function hasCoords(obj) {
        return !!(obj?.ubicacion && typeof obj.ubicacion.latitud === 'number' && typeof obj.ubicacion.longitud === 'number');
    }

    function mergeObjetivosPreservingCoords(current, incoming) {
        const currentById = new Map((current || []).map((o) => [Number(o.id), normalizeObjetivo(o)]));
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

    function renderCriticalAlerts() {
        const stack = document.getElementById('critical-alerts-stack');
        window.SENTRY_CRITICAL_ALERTS?.render({
            container: stack,
            alerts: state.criticalAlerts,
            actionLabel: 'Cedular evento',
            getName: (alert) => alert?.objetivoNombre || `Objetivo ${alert?.objetivoId ?? ''}`,
            getDescription: () => 'Se detectó un evento crítico para este objetivo.',
            onClose: (alert) => {
                const id = String(alert?.id || '');
                state.criticalAlerts = state.criticalAlerts.filter((item) => String(item.id) !== id);
                if (state.criticalAlerts.length > 0) {
                    state.lastFocusedCriticalObjetivoId = state.criticalAlerts[state.criticalAlerts.length - 1].objetivoId;
                } else {
                    state.lastFocusedCriticalObjetivoId = null;
                }
                renderCriticalAlerts();
            },
            onAction: async (alert) => {
                const objetivoId = Number(alert?.objetivoId || 0);
                if (!objetivoId) return;

                const ev = state.eventos.find((e) => Number(getEventoObjetivoId(e)) === objetivoId);
                if (!ev?.idEvento) return;
                state.selected.clear();
                state.selected.add(Number(ev.idEvento));
                updateBatchBar();
                await openCedularModal();
            },
        });
    }

    function addCriticalAlert(objetivoId) {
        if (!objetivoId) return;
        const hasEvento = state.eventos.some((e) => Number(getEventoObjetivoId(e)) === Number(objetivoId));
        if (!hasEvento) return;

        if (state.criticalAlerts.some((a) => Number(a.objetivoId) === Number(objetivoId))) {
            state.lastFocusedCriticalObjetivoId = objetivoId;
            focusMapOnObjetivo(objetivoId);
            return;
        }

        const alert = {
            id: `${Date.now()}-${objetivoId}`,
            objetivoId,
            objetivoNombre: getObjetivoNameById(state.objetivos, objetivoId),
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
                renderMarkers();
            }
            renderCriticalAlerts();
        }
    }

    function clearCriticalAlertsIfNoCriticalObjetivos() {
        const hasAnyCritical = state.objetivos.some((o) => String(o?.estado || '').toUpperCase() === 'CRITICO');
        if (!hasAnyCritical) {
            state.criticalAlerts = [];
            state.lastFocusedCriticalObjetivoId = null;
            renderCriticalAlerts();
            renderMarkers();
        }
    }

    function syncCriticalAlertsWithEventos() {
        if (!Array.isArray(state.eventos) || state.eventos.length === 0) {
            state.criticalAlerts = [];
            state.lastFocusedCriticalObjetivoId = null;
            renderCriticalAlerts();
            return;
        }

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

    let map;
    let markersLayer;
    let tilesLayer;

    function focusMapOnObjetivo(objetivoId) {
        if (!map) return;
        const obj = state.objetivos.find((o) => Number(o.id) === Number(objetivoId));
        const n = normalizeObjetivo(obj);
        const lat = n?.ubicacion?.latitud;
        const lon = n?.ubicacion?.longitud;
        if (typeof lat !== 'number' || typeof lon !== 'number') return;

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
        const c = countObjetivosByEstado(state.objetivos);
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

        const filteredAll = state.eventos.filter((e) => {
            const hay = [e.idEvento, e.tipoSenal, e.fecha, e.objetivo, e.zona].filter(Boolean).join(' ').toLowerCase();
            return q === '' || hay.includes(q);
        });

        const sorted = sortEventos(filteredAll);
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
        bar.classList.toggle('flex', n > 0);
    }

    function hookRowChecks() {
        document.querySelectorAll('.row-check').forEach((el) => {
            el.addEventListener('change', () => {
                const id = Number(el.dataset.id);
                if (el.checked) state.selected.add(id);
                else state.selected.delete(id);
                updateBatchBar();
            });
        });
    }

    document.getElementById('select-all')?.addEventListener('change', (e) => {
        const checked = e.target.checked;
        document.querySelectorAll('.row-check').forEach((el) => {
            el.checked = checked;
            const id = Number(el.dataset.id);
            if (checked) state.selected.add(id);
            else state.selected.delete(id);
        });
        updateBatchBar();
    });

    document.getElementById('clear-selection')?.addEventListener('click', () => {
        state.selected.clear();
        document.getElementById('select-all').checked = false;
        renderEventos();
    });

    document.getElementById('eventos-search')?.addEventListener('input', () => renderEventos());

    const modal = document.getElementById('cedular-modal');
    const msgBox = document.getElementById('cedular-msg');

    function showMsg(kind, text) {
        msgBox.classList.remove('hidden');
        msgBox.className = 'text-sm rounded-lg border p-3';
        if (kind === 'ok') msgBox.classList.add('border-emerald-900', 'bg-emerald-950', 'text-emerald-200');
        else msgBox.classList.add('border-red-900', 'bg-red-950', 'text-red-200');
        msgBox.textContent = text;
    }

    function hideMsg() {
        msgBox.classList.add('hidden');
        msgBox.textContent = '';
    }

    function setCedularSaving(isSaving) {
        const btn = document.getElementById('cedular-save');
        const label = document.getElementById('cedular-save-label');
        const loading = document.getElementById('cedular-save-loading');
        if (!btn || !label || !loading) return;

        btn.disabled = isSaving;
        btn.classList.toggle('opacity-70', isSaving);
        btn.classList.toggle('cursor-not-allowed', isSaving);
        label.classList.toggle('hidden', isSaving);
        loading.classList.toggle('hidden', !isSaving);
        loading.classList.toggle('inline-flex', isSaving);
    }

    async function fetchJsonWithTimeout(url, options = {}, timeoutMs = 7000) {
        return fetchJsonWithSession(url, {
            loginUrl: config.loginUrl,
            timeoutMs,
            options,
        });
    }

    async function ensureCedulacionPresets() {
        const hasUsableRows = (payload) => Array.isArray(payload?.data) && payload.data.length > 0;
        const warnings = [];

        if (!state.cedulacionTipos) {
            const tiposRes = await fetchJsonWithTimeout(config.cedulacionTiposUrl, {}, 6000);
            if (tiposRes.ok && (hasUsableRows(tiposRes.data) || !tiposRes.data?.stale)) {
                state.cedulacionTipos = tiposRes.data ?? { data: [] };
            } else if (!hasUsableRows(state.cedulacionTipos)) {
                warnings.push(tiposRes.data?.message || 'No se pudieron cargar los tipos de señal.');
            }
        }
        if (!state.cedulacionObservaciones) {
            const obsRes = await fetchJsonWithTimeout(config.cedulacionObservacionesUrl, {}, 6000);
            if (obsRes.ok && (hasUsableRows(obsRes.data) || !obsRes.data?.stale)) {
                state.cedulacionObservaciones = obsRes.data ?? { data: [] };
            } else if (!hasUsableRows(state.cedulacionObservaciones)) {
                warnings.push(obsRes.data?.message || 'No se pudieron cargar las observaciones predefinidas.');
            }
        }

        if (warnings.length > 0) {
            throw new Error(warnings.join(' '));
        }
    }

    function populateSelect(selectEl, items, { valueKey = 'id', labelKey = 'nombre', placeholder = 'Seleccionar' } = {}) {
        selectEl.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = placeholder;
        selectEl.appendChild(opt0);
        for (const item of items || []) {
            const opt = document.createElement('option');
            opt.value = item[valueKey];
            opt.textContent = item[labelKey] ?? String(item[valueKey]);
            selectEl.appendChild(opt);
        }
    }

    async function fetchContactosByObjetivo(objetivoId) {
        if (!objetivoId) return [];
        const cacheKey = String(objetivoId);
        if (Array.isArray(state.contactosByObjetivo[cacheKey])) {
            return state.contactosByObjetivo[cacheKey];
        }

        const url = CONTACTOS_ROUTE_TEMPLATE.replace('__OBJETIVO__', String(objetivoId));
        const data = await fetchJsonWithTimeout(url, {}, 6000).then((r) => r.data).catch(() => ({}));
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
        const ev = state.eventos.find((e) => Number(e.idEvento) === Number(firstId));
        const selectedEvents = state.eventos.filter((e) => ids.includes(Number(e.idEvento)));
        const isMultiple = selectedEvents.length > 1;

        document.getElementById('cedular-subtitle').textContent = isMultiple ? `Cedular ${selectedEvents.length} eventos` : 'Cedular evento';
        document.getElementById('cedular-title').textContent = isMultiple ? (selectedEvents[0]?.tipoSenal ?? 'Eventos seleccionados') : (ev?.tipoSenal ?? 'Evento');
        document.getElementById('cedular-objetivo').textContent = isMultiple ? (selectedEvents[0]?.objetivo ?? '—') : (ev?.objetivo ?? '—');
        document.getElementById('cedular-fecha').textContent = isMultiple ? (selectedEvents[0]?.fecha ?? '—') : (ev?.fecha ?? '—');
        document.getElementById('cedular-zona').textContent = isMultiple ? (selectedEvents[0]?.zona ?? '—') : (ev?.zona ?? '—');
        renderSelectedEventsPreview(selectedEvents);

        modal.classList.remove('hidden');

        try {
            await ensureCedulacionPresets();
        } catch (error) {
            if (!state.cedulacionTipos) state.cedulacionTipos = { data: [] };
            if (!state.cedulacionObservaciones) state.cedulacionObservaciones = { data: [] };
            showMsg('err', error?.message || 'No se pudieron cargar tipos/observaciones. Reintentá en unos segundos.');
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

    document.getElementById('open-cedular')?.addEventListener('click', openCedularModal);
    document.getElementById('cedular-close')?.addEventListener('click', closeCedularModal);
    document.getElementById('cedular-cancel')?.addEventListener('click', closeCedularModal);
    document.getElementById('obs-predef')?.addEventListener('change', (e) => {
        const id = Number(e.target.value || 0);
        if (!id) {
            document.getElementById('obs-text').value = '';
            return;
        }
        const obs = (state.cedulacionObservaciones?.data ?? []).find((o) => Number(o.id) === id);
        const texto = obs?.nombre ?? obs?.observacion ?? '';
        document.getElementById('obs-text').value = texto;
    });
    document.getElementById('contactos-objetivo')?.addEventListener('change', async (e) => {
        const objetivoId = Number(e.target.value || 0);
        const contactos = await fetchContactosByObjetivo(objetivoId);
        renderContactos(contactos);
    });
    document.getElementById('cedular-save')?.addEventListener('click', async () => {
        hideMsg();
        const ids = Array.from(state.selected.values());
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

        setCedularSaving(true);
        try {
            const result = await fetchJsonWithTimeout(config.cedulacionGuardarUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify({
                    eventos: ids,
                    cedulacion_tipo_id: cedulacionTipoId,
                    observaciones,
                }),
            }, 9000).catch(() => ({ ok: false, data: null }));

            const data = result.data;
            if (result.ok) {
                showMsg('ok', data?.message ?? 'Cedulación guardada.');
                objetivosSeleccionados.forEach((id) => removeCriticalAlertByObjetivoId(id));
                await refreshEventos();
                state.selected.clear();
                document.getElementById('select-all').checked = false;
                renderEventos();
                setTimeout(() => closeCedularModal(), 600);
            } else {
                showMsg('err', data?.message ?? 'No se pudo guardar la cedulación.');
                await refreshEventos();
                renderEventos();
            }
        } finally {
            setCedularSaving(false);
        }
    });

    function markerColorByEstado(estado) {
        const st = String(estado || '').toUpperCase();
        if (st === 'CRITICO') return '#ef4444';
        if (st === 'ONLINE') return '#38bdf8';
        if (st === 'OFFLINE') return '#e5e7eb';
        if (st === 'MUERTO') return '#fb923c';
        return '#a78bfa';
    }

    function initMap() {
        map = L.map('map', { zoomControl: true }).setView([-32.06, -59.23], 7);

        const tilePane = map.getPane('tilePane');
        const markerPane = map.getPane('markerPane');
        if (tilePane) tilePane.style.zIndex = '200';
        if (markerPane) markerPane.style.zIndex = '650';

        const cartoTpl = config.cartoTileTemplate;
        const stadiaTpl = config.stadiaTileTemplate;

        const useCarto = () => {
            if (tilesLayer) {
                try { map.removeLayer(tilesLayer); } catch {}
            }
            tilesLayer = L.tileLayer(cartoTpl, { maxZoom: 18 }).addTo(map);
        };

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
            const marker = L.marker([lat, lon], { icon, pane: 'markerPane' });
            marker.bindPopup(`<div style="color:#0f172a"><b>${obj.nombre ?? obj.descripcion ?? 'Objetivo'}</b><br/>Estado: ${obj.estado ?? ''}</div>`);
            marker.addTo(markersLayer);
        }
        document.getElementById('map-count').textContent = String(n);

        if (state.lastFocusedCriticalObjetivoId) {
            focusMapOnObjetivo(state.lastFocusedCriticalObjetivoId);
        } else if (n > 0) {
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

    async function refreshEventos() {
        const result = await fetchJsonWithTimeout(config.eventosUrl, {}, 6000).catch(() => ({ ok: false, data: null }));
        const data = result.data;
        state.eventos = Array.isArray(data) ? data : (Array.isArray(state.eventos) ? state.eventos : []);
        document.getElementById('eventos-updated').textContent = new Date().toLocaleTimeString();
        syncCriticalAlertsWithEventos();
        renderEventos();
    }

    async function refreshObjetivos() {
        const result = await fetchJsonWithTimeout(config.objetivosUrl, {}, 6000).catch(() => ({ ok: false, data: null }));
        const data = result.data ?? {};
        const incoming = Array.isArray(data?.data) ? data.data : [];
        state.objetivos = mergeObjetivosPreservingCoords(state.objetivos, incoming);
        await hydrateObjetivosCoordsIfNeeded();
        renderCounts();
        renderMarkers();
    }

    async function hydrateObjetivosCoordsIfNeeded() {
        const now = Date.now();
        if (state.coordsHydrationInFlight) return;
        if (now - state.lastCoordsHydrationAt < 20000) return;

        const sinCoords = state.objetivos.filter((o) => {
            const n = normalizeObjetivo(o);
            return !(n?.ubicacion && typeof n.ubicacion.latitud === 'number' && typeof n.ubicacion.longitud === 'number');
        });

        if (sinCoords.length === 0) return;

        state.coordsHydrationInFlight = true;
        state.lastCoordsHydrationAt = now;
        try {
            const target = sinCoords.slice(0, 8);
            await Promise.allSettled(target.map(async (o) => {
                const id = Number(o?.id || 0);
                if (!id) return;
                const url = OBJETIVO_DETALLE_ROUTE_TEMPLATE.replace('__OBJETIVO__', String(id));
                const detail = await fetchJsonWithTimeout(url, {}, 5000).then((r) => r.ok ? r.data : null).catch(() => null);
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
        const es = new EventSource(config.sseDashboardUrl);
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
                    const idx = state.objetivos.findIndex((o) => Number(o.id) === Number(up.id));
                    if (idx >= 0) {
                        const prev = normalizeObjetivo(state.objetivos[idx]);
                        const merged = { ...prev, ...up };
                        if (!hasCoords(merged) && hasCoords(prev)) merged.ubicacion = prev.ubicacion;
                        state.objetivos[idx] = merged;
                        const prevState = String(prev?.estado || '').toUpperCase();
                        const newState = String(merged?.estado || '').toUpperCase();
                        if (newState === 'CRITICO' && prevState !== 'CRITICO') addCriticalAlert(Number(merged.id));
                    } else {
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

    hookSortHeaders();
    hookGroupToggle();
    renderEventos();
    renderCounts();
    waitForLeafletReady(() => {
        initMap();
        Promise.allSettled([refreshObjetivos(), refreshEventos()]);
        startSSE();
    });
}

bootWhenReady('__sentryInicioPageInitialized', init);
