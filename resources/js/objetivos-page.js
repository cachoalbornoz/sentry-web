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

async function fetchJson(url, loginUrl, timeoutMs = 10000) {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), timeoutMs);

    try {
        const res = await fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            cache: 'no-store',
            signal: controller.signal,
        });

        const data = await res.json().catch(() => null);

        if (res.status === 401 && data?.session_expired) {
            window.location.href = loginUrl;
            return null;
        }

        if (!res.ok) {
            throw new Error(data?.message || `HTTP ${res.status}`);
        }

        return data;
    } finally {
        window.clearTimeout(timeout);
    }
}

function init(config) {
    const pageRoot = document.getElementById('objetivos-page');
    const resolvedConfig = config || {
        objetivosUrl: pageRoot?.dataset.objetivosUrl || '',
        eventosListUrl: pageRoot?.dataset.eventosListUrl || '',
        detalleUrl: pageRoot?.dataset.detalleUrl || '',
        contactosUrl: pageRoot?.dataset.contactosUrl || '',
        eventosUrl: pageRoot?.dataset.eventosUrl || '',
        zonasUrl: pageRoot?.dataset.zonasUrl || '',
        loginUrl: pageRoot?.dataset.loginUrl || '',
    };
    if (!pageRoot || !resolvedConfig) return;

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

    const getEstadoInfo = (estado) => window.SENTRY_OBJETIVO_CARD?.getEstadoInfo(estado)
        || { label: 'Desconocido', className: 'estado-desconocido', iconType: 'desconocido' };
    const renderStateIcon = (type, size = 48) => window.SENTRY_OBJETIVO_CARD?.renderStateIcon(type, size) || '';
    const renderDatosTab = (objetivo) => window.SENTRY_OBJETIVO_MODAL_CONTENT?.renderDatosTab(objetivo) || '';
    const renderTable = (headers, rows, emptyTitle, emptyDescription) =>
        window.SENTRY_OBJETIVO_MODAL_CONTENT?.renderTable(headers, rows, emptyTitle, emptyDescription) || '';
    const sortEventosByFechaDesc = (eventos) => window.SENTRY_OBJETIVO_MODAL_CONTENT?.sortEventosByFechaDesc(eventos) || [];

    const modalController = window.SENTRY_OBJETIVO_MODAL_CONTROLLER?.create({
        state,
        refs,
        urls: {
            detalle: resolvedConfig.detalleUrl,
            contactos: resolvedConfig.contactosUrl,
            eventos: resolvedConfig.eventosUrl,
            zonas: resolvedConfig.zonasUrl,
        },
        fetchJson: (url, timeoutMs) => fetchJson(url, resolvedConfig.loginUrl, timeoutMs),
        objectiveRoute,
        getEstadoInfo,
        renderStateIcon,
        escapeHtml,
        unwrapCollection,
        renderDatosTab,
        renderTable,
        sortEventosByFechaDesc,
    });

    const openObjetivoModal = (objetivoId) => modalController?.openObjetivoModal(objetivoId);

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
        window.SENTRY_CRITICAL_ALERTS?.render({
            container: refs.criticalStack,
            alerts: state.criticalAlerts,
            actionLabel: 'Ver objetivo',
            getName: (alert) => alert?.objetivoNombre || `Objetivo ${alert?.objetivoId ?? ''}`,
            getDescription: () => 'Se detectó un evento crítico para este objetivo.',
            onClose: (alert) => {
                const id = String(alert?.id || '');
                state.criticalAlerts = state.criticalAlerts.filter((item) => String(item.id) !== id);
                renderCriticalAlerts();
            },
            onAction: (alert) => {
                const objetivoId = Number(alert?.objetivoId || 0);
                if (objetivoId) {
                    openObjetivoModal(objetivoId);
                }
            },
        });
    }

    function syncCriticalAlerts() {
        const criticalObjetivos = state.objetivos.filter((item) => String(item?.estado || '').toUpperCase() === 'CRITICO');
        const eventosObjetivoIds = new Set(
            (state.eventos || [])
                .map((event) => getEventoObjetivoId(event))
                .filter((id) => Number.isFinite(id) && id > 0)
        );

        state.criticalAlerts = criticalObjetivos
            .filter((item) => eventosObjetivoIds.has(Number(item.id)))
            .map((item) => ({
                id: `critical-${item.id}`,
                objetivoId: Number(item.id),
                objetivoNombre: getObjetivoNameById(item.id),
            }));

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
        refs.grid.innerHTML = state.filtered
            .map((objetivo) => window.SENTRY_OBJETIVO_CARD?.render(objetivo) || '')
            .join('');

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

    async function loadObjetivos(showLoading = true) {
        if (showLoading) {
            refs.loading.classList.remove('hidden');
            refs.empty.classList.add('hidden');
            refs.grid.classList.add('hidden');
        }

        try {
            const payload = await fetchJson(resolvedConfig.objetivosUrl, resolvedConfig.loginUrl, 12000);
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
            const payload = await fetchJson(resolvedConfig.eventosListUrl, resolvedConfig.loginUrl, 12000);
            state.eventos = Array.isArray(payload) ? payload : [];
            syncCriticalAlerts();
        } catch (_) {
            state.eventos = [];
            syncCriticalAlerts();
        }
    }

    refs.search?.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(filterObjetivos, 220);
    });

    modalController?.bindUi();

    void loadObjetivos();
    void loadEventosResumen();
    window.setInterval(() => {
        void loadObjetivos(false);
        void loadEventosResumen();
    }, 15000);
}

window.SENTRY_OBJETIVOS_PAGE = {
    init,
};

document.addEventListener('DOMContentLoaded', () => {
    init();
});
