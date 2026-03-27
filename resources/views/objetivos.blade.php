@extends('layouts.app', ['activeNav' => 'objetivos'])

@section('title', 'Objetivos')

@push('styles')
    <style>
        #objetivos-page {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .objetivos-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: flex-end;
        }
        .objetivos-search {
            width: min(360px, 100%);
            height: 42px;
            border: 1px solid #2f3a4a;
            background: rgba(15, 23, 42, 0.5);
            color: #fff;
            padding: 0 14px;
        }
        .objetivos-search::placeholder {
            color: #64748b;
        }
        .objetivos-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }
        .objetivos-stat {
            border: 1px solid #1e293b;
            background: rgba(15, 23, 42, 0.8);
            padding: 14px 16px;
        }
        .objetivos-stat-label {
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .objetivos-stat-value {
            margin-top: 8px;
            color: #f8fafc;
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }
        .objetivos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 12px;
        }
        .objetivo-card {
            border: 1px solid #242c38;
            background: #232529;
            min-height: 144px;
            padding: 18px;
            color: #fff;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: border-color .15s ease, transform .15s ease, background .15s ease;
        }
        .objetivo-card:hover {
            border-color: #3b82f6;
            background: #272b31;
            transform: translateY(-1px);
        }
        .objetivo-card-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .objetivo-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .state-icon-svg {
            display: block;
            overflow: visible;
        }
        .state-icon-svg .state-hex {
            fill: none;
            stroke: currentColor;
            stroke-width: 4;
            stroke-linejoin: round;
        }
        .state-icon-svg .state-hex-fill {
            fill: currentColor;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linejoin: round;
        }
        .state-icon-svg .state-badge-bg {
            fill: rgba(15, 23, 42, .96);
            stroke: currentColor;
            stroke-width: 2;
        }
        .state-icon-svg .state-mark {
            fill: none;
            stroke: currentColor;
            stroke-width: 3.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .state-icon-svg .state-mark-solid {
            fill: currentColor;
            stroke: none;
        }
        .state-icon-svg .state-mark-contrast {
            fill: none;
            stroke: #f8fafc;
            stroke-width: 4.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .objetivo-card-body {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }
        .objetivo-name {
            font-size: 17px;
            font-weight: 600;
            line-height: 1.25;
            color: #f8fafc;
        }
        .objetivo-meta {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.45;
        }
        .objetivo-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            border: 1px solid rgba(148, 163, 184, .2);
            padding: 5px 10px;
            font-size: 12px;
            color: #cbd5e1;
            background: rgba(15, 23, 42, .44);
        }
        .objetivo-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
        }
        .estado-online { color: #4589ff; }
        .estado-critico { color: #da1e28; }
        .estado-offline { color: #f4f4f4; }
        .estado-muerto { color: #ff832b; }
        .estado-desconocido { color: #94a3b8; }
        .objetivos-empty,
        .objetivos-loading {
            border: 1px solid #1e293b;
            background: rgba(15, 23, 42, 0.8);
            padding: 32px 20px;
            text-align: center;
            color: #cbd5e1;
        }
        .objetivos-loading-spinner {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,.18);
            border-top-color: #60a5fa;
            animation: objetivos-spin .9s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes objetivos-spin {
            to { transform: rotate(360deg); }
        }
        #objetivos-critical-alerts {
            position: fixed;
            left: 18px;
            bottom: 18px;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: min(320px, calc(100vw - 36px));
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }
        #objetivos-critical-alerts.hidden {
            display: none;
        }
        .critical-alert-card {
            border: 1px solid rgba(248, 113, 113, 0.28);
            border-left: 3px solid rgba(248, 113, 113, 0.75);
            background: rgba(15, 23, 42, 0.96);
            box-shadow: 0 10px 24px rgb(2 6 23 / 0.45), 0 0 0 1px rgba(239, 68, 68, 0.08) inset;
            border-radius: 12px;
            padding: 10px 12px;
        }
        .critical-alert-icon {
            display: inline-flex;
            width: 20px;
            height: 20px;
            align-items: center;
            justify-content: center;
            color: #da1e28;
            flex: 0 0 auto;
        }
        .critical-alert-action {
            border: 1px solid #475569;
            background: rgba(15, 23, 42, .78);
            color: #fff;
            padding: 7px 12px;
            font-size: 13px;
        }
        .objetivo-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.72);
            z-index: 1200;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 18px;
            overflow-y: auto;
        }
        .objetivo-modal-backdrop.hidden {
            display: none;
        }
        .objetivo-modal {
            width: min(1120px, 100%);
            background: #2b2b2b;
            border: 1px solid #3b3b3b;
            color: #fff;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .45);
        }
        .objetivo-modal-header {
            padding: 20px 22px 10px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .objetivo-modal-title-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .objetivo-modal-title-text h2 {
            margin: 0;
            font-size: 18px;
            color: #e2e8f0;
        }
        .objetivo-modal-title-text .objetivo-headline {
            margin-top: 8px;
            font-size: 20px;
            font-weight: 600;
            color: #f8fafc;
        }
        .objetivo-modal-close {
            width: 34px;
            height: 34px;
            border: 0;
            background: transparent;
            color: #cbd5e1;
            font-size: 24px;
            line-height: 1;
        }
        .objetivo-modal-tabs {
            display: flex;
            gap: 0;
            padding: 0 22px;
            border-bottom: 1px solid #424242;
        }
        .objetivo-modal-tab {
            border: 0;
            border-right: 1px solid #4b5563;
            background: #3b3b3b;
            color: #e2e8f0;
            padding: 12px 18px;
            font-size: 14px;
        }
        .objetivo-modal-tab.is-active {
            background: #4a4a4a;
            color: #fff;
        }
        .objetivo-modal-body {
            padding: 18px 22px 24px;
            background: #343434;
        }
        .objetivo-tab-panel {
            min-height: 260px;
            max-height: 65vh;
            overflow: auto;
        }
        .objetivo-section-title {
            margin: 0 0 14px;
            font-size: 16px;
            font-weight: 600;
            color: #f8fafc;
        }
        .objetivo-data-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px;
        }
        .objetivo-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .objetivo-field.span-2 { grid-column: span 2; }
        .objetivo-field.span-3 { grid-column: span 3; }
        .objetivo-field.span-4 { grid-column: span 4; }
        .objetivo-field.span-6 { grid-column: span 6; }
        .objetivo-field-label {
            font-size: 12px;
            color: #cbd5e1;
        }
        .objetivo-field-value {
            min-height: 46px;
            border: 1px solid #4b5563;
            background: rgba(15, 23, 42, .18);
            color: #fff;
            padding: 12px 14px;
            display: flex;
            align-items: center;
        }
        .objetivo-subsection {
            margin-top: 24px;
        }
        .objetivo-table {
            width: 100%;
            border-collapse: collapse;
        }
        .objetivo-table th,
        .objetivo-table td {
            border-bottom: 1px solid #475569;
            padding: 12px 10px;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }
        .objetivo-table th {
            color: #cbd5e1;
            font-weight: 600;
            background: rgba(15, 23, 42, .18);
            position: sticky;
            top: 0;
        }
        .objetivo-table td {
            color: #f8fafc;
        }
        .objetivo-table-empty {
            border: 1px solid #475569;
            background: rgba(15, 23, 42, .14);
            padding: 24px 18px;
            color: #cbd5e1;
        }
        .objetivo-tab-loading {
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .objetivo-tab-loading::before {
            content: "";
            width: 14px;
            height: 14px;
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,.18);
            border-top-color: #60a5fa;
            animation: objetivos-spin .9s linear infinite;
        }
        .objetivo-tab-error {
            border: 1px solid rgba(248, 113, 113, .35);
            background: rgba(127, 29, 29, .25);
            color: #fecaca;
            padding: 14px 16px;
        }
        @media (max-width: 900px) {
            .objetivos-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .objetivo-data-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .objetivo-field.span-2,
            .objetivo-field.span-3,
            .objetivo-field.span-4,
            .objetivo-field.span-6 {
                grid-column: span 2;
            }
        }
        @media (max-width: 640px) {
            .objetivos-stats {
                grid-template-columns: 1fr;
            }
            .objetivo-modal-title-wrap {
                align-items: flex-start;
            }
            .objetivo-modal-tabs {
                overflow-x: auto;
            }
            .objetivo-data-grid {
                grid-template-columns: 1fr;
            }
            .objetivo-field.span-2,
            .objetivo-field.span-3,
            .objetivo-field.span-4,
            .objetivo-field.span-6 {
                grid-column: span 1;
            }
        }
    </style>
@endpush

@section('content')
    <section id="objetivos-page">
        <div class="rounded-xl border border-slate-800 bg-slate-900/25 p-4">
            <div class="objetivos-toolbar">
                <input id="objetivos-search" class="objetivos-search" type="search" placeholder="Buscar por nombre o descripción">
            </div>
        </div>

        <section class="objetivos-stats" id="objetivos-stats">
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Total</div>
                <div class="objetivos-stat-value" id="stat-total">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">En línea</div>
                <div class="objetivos-stat-value" id="stat-online">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Críticos</div>
                <div class="objetivos-stat-value" id="stat-critico">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Inactivos</div>
                <div class="objetivos-stat-value" id="stat-offline">0</div>
            </article>
            <article class="objetivos-stat">
                <div class="objetivos-stat-label">Sin señal</div>
                <div class="objetivos-stat-value" id="stat-muerto">0</div>
            </article>
        </section>

        <div id="objetivos-loading" class="objetivos-loading">
            <div class="objetivos-loading-spinner"></div>
            Cargando objetivos...
        </div>

        <div id="objetivos-empty" class="objetivos-empty hidden"></div>
        <div id="objetivos-grid" class="objetivos-grid hidden"></div>
    </section>

    <div id="objetivos-critical-alerts" class="hidden"></div>

    <div id="objetivo-modal-backdrop" class="objetivo-modal-backdrop hidden">
        <div class="objetivo-modal" role="dialog" aria-modal="true" aria-labelledby="objetivo-modal-headline">
            <div class="objetivo-modal-header">
                <div class="objetivo-modal-title-wrap">
                    <div id="objetivo-modal-icon" class="objetivo-icon estado-desconocido"></div>
                    <div class="objetivo-modal-title-text">
                        <h2>Detalle del Objetivo</h2>
                        <div id="objetivo-modal-headline" class="objetivo-headline">—</div>
                        <div id="objetivo-modal-status" class="objetivo-status mt-2">
                            <span class="objetivo-status-dot"></span>
                            <span>—</span>
                        </div>
                    </div>
                </div>
                <button id="objetivo-modal-close" class="objetivo-modal-close" type="button" aria-label="Cerrar detalle">×</button>
            </div>

            <div class="objetivo-modal-tabs">
                <button type="button" class="objetivo-modal-tab is-active" data-tab="datos">Datos</button>
                <button type="button" class="objetivo-modal-tab" data-tab="contactos">Contactos</button>
                <button type="button" class="objetivo-modal-tab" data-tab="eventos">Eventos</button>
                <button type="button" class="objetivo-modal-tab" data-tab="zonas">Zonas</button>
            </div>

            <div class="objetivo-modal-body">
                <div id="objetivo-tab-content" class="objetivo-tab-panel"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const OBJETIVOS_URL = @json(route('x.objetivos'));
            const EVENTOS_LIST_URL = @json(route('x.eventos'));
            const DETALLE_URL = @json(route('x.objetivos.detalle', ['objetivo' => '__OBJETIVO__']));
            const CONTACTOS_URL = @json(route('x.objetivos.contactos', ['objetivo' => '__OBJETIVO__']));
            const EVENTOS_URL = @json(route('x.objetivos.eventos', ['objetivo' => '__OBJETIVO__']));
            const ZONAS_URL = @json(route('x.objetivos.zonas', ['objetivo' => '__OBJETIVO__']));
            const LOGIN_URL = @json(route('login.form'));

            const state = {
                objetivos: [],
                eventos: [],
                filtered: [],
                activeObjetivoId: null,
                activeTab: 'datos',
                searchTimer: null,
                detailsById: {},
                criticalAlerts: [],
            };

            const refs = {
                search: document.getElementById('objetivos-search'),
                loading: document.getElementById('objetivos-loading'),
                empty: document.getElementById('objetivos-empty'),
                grid: document.getElementById('objetivos-grid'),
                modalBackdrop: document.getElementById('objetivo-modal-backdrop'),
                modalClose: document.getElementById('objetivo-modal-close'),
                modalHeadline: document.getElementById('objetivo-modal-headline'),
                modalStatus: document.getElementById('objetivo-modal-status'),
                modalIcon: document.getElementById('objetivo-modal-icon'),
                tabContent: document.getElementById('objetivo-tab-content'),
                tabs: Array.from(document.querySelectorAll('.objetivo-modal-tab')),
                statTotal: document.getElementById('stat-total'),
                statOnline: document.getElementById('stat-online'),
                statCritico: document.getElementById('stat-critico'),
                statOffline: document.getElementById('stat-offline'),
                statMuerto: document.getElementById('stat-muerto'),
                criticalStack: document.getElementById('objetivos-critical-alerts'),
            };

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function normalizeText(value) {
                return String(value ?? '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim();
            }

            function objectiveRoute(template, objetivoId) {
                return template.replace('__OBJETIVO__', String(objetivoId));
            }

            async function fetchJson(url, timeoutMs = 10000) {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), timeoutMs);
                try {
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                        signal: controller.signal,
                    });
                    const data = await res.json().catch(() => null);
                    if (res.status === 401 && data?.session_expired) {
                        window.location.href = LOGIN_URL;
                        return null;
                    }
                    if (!res.ok) {
                        throw new Error(data?.message || `HTTP ${res.status}`);
                    }
                    return data;
                } finally {
                    clearTimeout(timeout);
                }
            }

            function getEstadoInfo(estado) {
                const key = String(estado || '').toUpperCase();
                const map = {
                    ONLINE: { label: 'En línea', className: 'estado-online', iconType: 'activo' },
                    CRITICO: { label: 'Crítico', className: 'estado-critico', iconType: 'critico' },
                    OFFLINE: { label: 'Inactivo', className: 'estado-offline', iconType: 'inactivo' },
                    MUERTO: { label: 'Sin señal', className: 'estado-muerto', iconType: 'apagado' },
                };
                return map[key] || { label: 'Desconocido', className: 'estado-desconocido', iconType: 'desconocido' };
            }

            function renderStateIcon(type, size = 48) {
                const icons = {
                    activo: `
                        <polygon class="state-hex" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                        <circle class="state-badge-bg" cx="49" cy="46" r="8"></circle>
                        <path class="state-mark" d="M45 46l3 3 6-6"></path>
                    `,
                    apagado: `
                        <polygon class="state-hex" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                        <circle class="state-badge-bg" cx="49" cy="46" r="8"></circle>
                        <path class="state-mark" d="M45 42l8 8"></path>
                        <path class="state-mark" d="M53 42l-8 8"></path>
                    `,
                    inactivo: `
                        <polygon class="state-hex" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                        <circle class="state-badge-bg" cx="49" cy="46" r="8"></circle>
                        <path class="state-mark" d="M44 46h10"></path>
                    `,
                    critico: `
                        <polygon class="state-hex-fill" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                        <path class="state-mark-contrast" d="M32 20v14"></path>
                        <circle class="state-mark-solid" cx="32" cy="40" r="3.2" fill="#f8fafc"></circle>
                    `,
                    desconocido: `
                        <polygon class="state-hex" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                        <circle class="state-mark-solid" cx="32" cy="29" r="4"></circle>
                    `,
                };

                return `
                    <svg class="state-icon-svg" width="${size}" height="${size}" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                        ${icons[type] || icons.desconocido}
                    </svg>
                `;
            }

            function countEstados(objetivos) {
                const counts = { ONLINE: 0, CRITICO: 0, OFFLINE: 0, MUERTO: 0 };
                for (const objetivo of objetivos) {
                    const key = String(objetivo?.estado || '').toUpperCase();
                    if (Object.prototype.hasOwnProperty.call(counts, key)) {
                        counts[key] += 1;
                    }
                }
                return counts;
            }

            function unwrapCollection(payload) {
                if (Array.isArray(payload)) return payload;
                if (Array.isArray(payload?.data)) return payload.data;
                return [];
            }

            function updateStats() {
                const counts = countEstados(state.objetivos);
                refs.statTotal.textContent = String(state.objetivos.length);
                refs.statOnline.textContent = String(counts.ONLINE);
                refs.statCritico.textContent = String(counts.CRITICO);
                refs.statOffline.textContent = String(counts.OFFLINE);
                refs.statMuerto.textContent = String(counts.MUERTO);
            }

            function getEventoObjetivoId(ev) {
                return Number(ev?.idObjetivo ?? ev?.objetivoId ?? ev?.objetivo_id ?? 0);
            }

            function getObjetivoNameById(objetivoId) {
                const objetivo = state.objetivos.find((item) => Number(item.id) === Number(objetivoId));
                return objetivo?.nombre || objetivo?.descripcion || `Objetivo ${objetivoId}`;
            }

            function renderCriticalAlerts() {
                if (!Array.isArray(state.criticalAlerts) || state.criticalAlerts.length === 0) {
                    refs.criticalStack.classList.add('hidden');
                    refs.criticalStack.innerHTML = '';
                    return;
                }

                refs.criticalStack.classList.remove('hidden');
                refs.criticalStack.innerHTML = [...state.criticalAlerts].reverse().map((alert) => `
                    <div class="critical-alert-card">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-100">
                                <span class="critical-alert-icon">${renderStateIcon('critico', 20)}</span>
                                Atención requerida en objetivo crítico
                            </div>
                            <button class="text-slate-400 hover:text-white text-sm leading-none critical-alert-close" data-id="${alert.id}">×</button>
                        </div>
                        <div class="mt-1 text-sm text-slate-200">${escapeHtml(alert.objetivoNombre)}</div>
                        <div class="mt-1 text-xs text-slate-400">Se detectó un evento crítico para este objetivo.</div>
                        <div class="mt-3">
                            <button class="critical-alert-action critical-alert-open" type="button" data-objetivo-id="${alert.objetivoId}">
                                Ver objetivo
                            </button>
                        </div>
                    </div>
                `).join('');

                refs.criticalStack.querySelectorAll('.critical-alert-close').forEach((button) => {
                    button.addEventListener('click', () => {
                        const id = String(button.dataset.id || '');
                        state.criticalAlerts = state.criticalAlerts.filter((item) => String(item.id) !== id);
                        renderCriticalAlerts();
                    });
                });

                refs.criticalStack.querySelectorAll('.critical-alert-open').forEach((button) => {
                    button.addEventListener('click', () => {
                        const objetivoId = Number(button.dataset.objetivoId || 0);
                        if (objetivoId) {
                            openObjetivoModal(objetivoId);
                        }
                    });
                });
            }

            function syncCriticalAlerts() {
                const criticalObjetivos = state.objetivos.filter((item) => String(item?.estado || '').toUpperCase() === 'CRITICO');
                const eventosObjetivoIds = new Set(
                    (state.eventos || [])
                        .map((event) => getEventoObjetivoId(event))
                        .filter((id) => Number.isFinite(id) && id > 0)
                );

                const nextAlerts = criticalObjetivos
                    .filter((item) => eventosObjetivoIds.has(Number(item.id)))
                    .map((item) => ({
                        id: `critical-${item.id}`,
                        objetivoId: Number(item.id),
                        objetivoNombre: getObjetivoNameById(item.id),
                    }));

                state.criticalAlerts = nextAlerts;
                renderCriticalAlerts();
            }

            function renderGrid() {
                if (!state.filtered.length) {
                    refs.grid.classList.add('hidden');
                    refs.empty.classList.remove('hidden');
                    refs.empty.textContent = state.objetivos.length === 0
                        ? 'No se encontraron objetivos disponibles.'
                        : 'No hay objetivos que coincidan con la búsqueda.';
                    return;
                }

                refs.empty.classList.add('hidden');
                refs.grid.classList.remove('hidden');
                refs.grid.innerHTML = state.filtered.map((objetivo) => {
                    const info = getEstadoInfo(objetivo.estado);
                    const jurisdiccion = objetivo?.jurisdiccion?.nombre || 'Sin jurisdicción';
                    const cliente = objetivo?.cliente?.nombre || 'Sin cliente';
                    return `
                        <button class="objetivo-card" type="button" data-objetivo-id="${escapeHtml(objetivo.id)}">
                            <div class="objetivo-card-top">
                                <div class="objetivo-icon ${info.className}">${renderStateIcon(info.iconType, 42)}</div>
                                <div class="objetivo-status ${info.className}">
                                    <span class="objetivo-status-dot"></span>
                                    <span>${escapeHtml(info.label)}</span>
                                </div>
                            </div>
                            <div class="objetivo-card-body">
                                <div class="objetivo-name">${escapeHtml(objetivo.nombre || objetivo.descripcion || `Objetivo ${objetivo.id}`)}</div>
                                <div class="objetivo-meta">Código: SG - ${escapeHtml(objetivo.codigo ?? '—')}</div>
                                <div class="objetivo-meta">${escapeHtml(cliente)} · ${escapeHtml(jurisdiccion)}</div>
                            </div>
                        </button>
                    `;
                }).join('');

                refs.grid.querySelectorAll('[data-objetivo-id]').forEach((button) => {
                    button.addEventListener('click', () => openObjetivoModal(Number(button.dataset.objetivoId)));
                });
            }

            function filterObjetivos() {
                const query = normalizeText(refs.search.value);
                if (!query) {
                    state.filtered = [...state.objetivos];
                } else {
                    state.filtered = state.objetivos.filter((objetivo) => {
                        const haystack = normalizeText(`${objetivo.nombre || ''} ${objetivo.descripcion || ''}`);
                        return haystack.includes(query);
                    });
                }
                renderGrid();
            }

            function formatValue(value, fallback = '—') {
                const normalized = value === null || value === undefined || value === '' ? fallback : String(value);
                return escapeHtml(normalized);
            }

            function renderDatosTab(objetivo) {
                const ubicacion = objetivo?.ubicacion || {};
                const localidad = objetivo?.localidad?.nombre || '—';
                return `
                    <section>
                        <h3 class="objetivo-section-title">Datos</h3>
                        <div class="objetivo-data-grid">
                            <div class="objetivo-field span-2">
                                <div class="objetivo-field-label">Código</div>
                                <div class="objetivo-field-value">SG - ${formatValue(objetivo?.codigo)}</div>
                            </div>
                            <div class="objetivo-field span-2">
                                <div class="objetivo-field-label">Cliente</div>
                                <div class="objetivo-field-value">${formatValue(objetivo?.cliente?.nombre)}</div>
                            </div>
                            <div class="objetivo-field span-2">
                                <div class="objetivo-field-label">Jurisdicción</div>
                                <div class="objetivo-field-value">${formatValue(objetivo?.jurisdiccion?.nombre)}</div>
                            </div>
                            <div class="objetivo-field span-6">
                                <div class="objetivo-field-label">Descripción</div>
                                <div class="objetivo-field-value">${formatValue(objetivo?.nombre || objetivo?.descripcion)}</div>
                            </div>
                        </div>

                        <div class="objetivo-subsection">
                            <h3 class="objetivo-section-title">Ubicación</h3>
                            <div class="objetivo-data-grid">
                                <div class="objetivo-field span-6">
                                    <div class="objetivo-field-label">Localidad</div>
                                    <div class="objetivo-field-value">${formatValue(localidad)}</div>
                                </div>
                                <div class="objetivo-field span-3">
                                    <div class="objetivo-field-label">Dirección</div>
                                    <div class="objetivo-field-value">${formatValue(objetivo?.direccion)}</div>
                                </div>
                                <div class="objetivo-field span-1">
                                    <div class="objetivo-field-label">Número</div>
                                    <div class="objetivo-field-value">${formatValue(objetivo?.numero)}</div>
                                </div>
                                <div class="objetivo-field span-1">
                                    <div class="objetivo-field-label">Piso</div>
                                    <div class="objetivo-field-value">${formatValue(objetivo?.piso)}</div>
                                </div>
                                <div class="objetivo-field span-1">
                                    <div class="objetivo-field-label">Departamento</div>
                                    <div class="objetivo-field-value">${formatValue(objetivo?.depto)}</div>
                                </div>
                                <div class="objetivo-field span-3">
                                    <div class="objetivo-field-label">Entre calles</div>
                                    <div class="objetivo-field-value">${formatValue(objetivo?.entre_calles)}</div>
                                </div>
                                <div class="objetivo-field span-1">
                                    <div class="objetivo-field-label">Latitud</div>
                                    <div class="objetivo-field-value">${formatValue(ubicacion?.latitud)}</div>
                                </div>
                                <div class="objetivo-field span-2">
                                    <div class="objetivo-field-label">Longitud</div>
                                    <div class="objetivo-field-value">${formatValue(ubicacion?.longitud)}</div>
                                </div>
                            </div>
                        </div>
                    </section>
                `;
            }

            function renderTable(headers, rows, emptyTitle, emptyDescription) {
                if (!Array.isArray(rows) || rows.length === 0) {
                    return `
                        <div class="objetivo-table-empty">
                            <div class="font-medium">${escapeHtml(emptyTitle)}</div>
                            <div class="mt-1 text-sm text-slate-300">${escapeHtml(emptyDescription)}</div>
                        </div>
                    `;
                }

                return `
                    <table class="objetivo-table">
                        <thead>
                            <tr>${headers.map((header) => `<th>${escapeHtml(header.title)}</th>`).join('')}</tr>
                        </thead>
                        <tbody>
                            ${rows.map((row) => `
                                <tr>
                                    ${headers.map((header) => `<td>${formatValue(row[header.key])}</td>`).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            function renderActiveTab() {
                const cache = state.detailsById[String(state.activeObjetivoId)];
                if (!cache) {
                    refs.tabContent.innerHTML = '';
                    return;
                }

                if (cache.loading) {
                    refs.tabContent.innerHTML = '<div class="objetivo-tab-loading">Cargando información del objetivo...</div>';
                    return;
                }

                if (cache.error) {
                    refs.tabContent.innerHTML = `<div class="objetivo-tab-error">${escapeHtml(cache.error)}</div>`;
                    return;
                }

                if (state.activeTab === 'datos') {
                    refs.tabContent.innerHTML = renderDatosTab(cache.objetivo);
                    return;
                }

                if (state.activeTab === 'contactos') {
                    refs.tabContent.innerHTML = renderTable(
                        [
                            { key: 'nombre', title: 'Nombre' },
                            { key: 'email', title: 'Email' },
                            { key: 'movil', title: 'Celular' },
                            { key: 'telefono', title: 'Teléfono' },
                        ],
                        cache.contactos,
                        'No hay contactos disponibles',
                        'No se encontraron contactos para este objetivo.'
                    );
                    return;
                }

                if (state.activeTab === 'eventos') {
                    refs.tabContent.innerHTML = renderTable(
                        [
                            { key: 'tipoSenal', title: 'Tipo de Señal' },
                            { key: 'fechaHora', title: 'Fecha y Hora' },
                            { key: 'zona', title: 'Zona' },
                        ],
                        cache.eventos,
                        'No hay eventos disponibles',
                        'No se encontraron eventos para este objetivo.'
                    );
                    return;
                }

                refs.tabContent.innerHTML = renderTable(
                    [
                        { key: 'zona_nro', title: 'Número de zona' },
                        { key: 'nombre', title: 'Nombre' },
                        { key: 'descripcion', title: 'Descripción' },
                    ],
                    cache.zonas,
                    'No hay zonas disponibles',
                    'No se encontraron zonas para este objetivo.'
                );
            }

            function setActiveTab(tab) {
                state.activeTab = tab;
                refs.tabs.forEach((button) => {
                    button.classList.toggle('is-active', button.dataset.tab === tab);
                });
                renderActiveTab();
            }

            async function ensureObjetivoDetails(objetivoId) {
                const cacheKey = String(objetivoId);
                if (state.detailsById[cacheKey] && !state.detailsById[cacheKey].loading) {
                    return state.detailsById[cacheKey];
                }

                state.detailsById[cacheKey] = { loading: true };
                renderActiveTab();

                try {
                    const [detalle, contactos, eventos, zonas] = await Promise.all([
                        fetchJson(objectiveRoute(DETALLE_URL, objetivoId), 12000),
                        fetchJson(objectiveRoute(CONTACTOS_URL, objetivoId), 12000),
                        fetchJson(objectiveRoute(EVENTOS_URL, objetivoId), 12000),
                        fetchJson(objectiveRoute(ZONAS_URL, objetivoId), 12000),
                    ]);

                    state.detailsById[cacheKey] = {
                        loading: false,
                        objetivo: detalle?.data || detalle || {},
                        contactos: unwrapCollection(contactos),
                        eventos: Array.isArray(eventos?.eventos) ? eventos.eventos : [],
                        zonas: unwrapCollection(zonas),
                    };
                } catch (error) {
                    state.detailsById[cacheKey] = {
                        loading: false,
                        error: error?.message || 'No se pudo cargar el detalle del objetivo.',
                    };
                }

                return state.detailsById[cacheKey];
            }

            async function openObjetivoModal(objetivoId) {
                const objetivo = state.objetivos.find((item) => Number(item.id) === Number(objetivoId));
                if (!objetivo) return;

                state.activeObjetivoId = objetivoId;
                state.activeTab = 'datos';

                const info = getEstadoInfo(objetivo.estado);
                refs.modalHeadline.textContent = objetivo.nombre || objetivo.descripcion || `Objetivo ${objetivo.id}`;
                refs.modalStatus.className = `objetivo-status ${info.className}`;
                refs.modalStatus.innerHTML = `<span class="objetivo-status-dot"></span><span>${escapeHtml(info.label)}</span>`;
                refs.modalIcon.className = `objetivo-icon ${info.className}`;
                refs.modalIcon.innerHTML = renderStateIcon(info.iconType, 76);
                refs.modalBackdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                setActiveTab('datos');
                await ensureObjetivoDetails(objetivoId);
                renderActiveTab();
            }

            function closeObjetivoModal() {
                refs.modalBackdrop.classList.add('hidden');
                document.body.style.overflow = '';
                state.activeObjetivoId = null;
            }

            async function loadObjetivos(showLoading = true) {
                if (showLoading) {
                    refs.loading.classList.remove('hidden');
                    refs.empty.classList.add('hidden');
                    refs.grid.classList.add('hidden');
                }
                try {
                    const payload = await fetchJson(OBJETIVOS_URL, 12000);
                    state.objetivos = unwrapCollection(payload);
                    updateStats();
                    filterObjetivos();
                    syncCriticalAlerts();
                } catch (error) {
                    refs.empty.classList.remove('hidden');
                    refs.empty.textContent = error?.message || 'No se pudieron cargar los objetivos.';
                } finally {
                    if (showLoading) {
                        refs.loading.classList.add('hidden');
                    }
                }
            }

            async function loadEventosResumen() {
                try {
                    const payload = await fetchJson(EVENTOS_LIST_URL, 12000);
                    state.eventos = Array.isArray(payload) ? payload : [];
                    syncCriticalAlerts();
                } catch (_) {
                    state.eventos = [];
                    syncCriticalAlerts();
                }
            }

            refs.search?.addEventListener('input', () => {
                clearTimeout(state.searchTimer);
                state.searchTimer = window.setTimeout(filterObjetivos, 220);
            });

            refs.modalClose?.addEventListener('click', closeObjetivoModal);
            refs.modalBackdrop?.addEventListener('click', (event) => {
                if (event.target === refs.modalBackdrop) {
                    closeObjetivoModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !refs.modalBackdrop.classList.contains('hidden')) {
                    closeObjetivoModal();
                }
            });
            refs.tabs.forEach((button) => {
                button.addEventListener('click', () => setActiveTab(button.dataset.tab));
            });

            loadObjetivos();
            loadEventosResumen();
            window.setInterval(() => {
                loadObjetivos(false);
                loadEventosResumen();
            }, 15000);
        })();
    </script>
@endpush
