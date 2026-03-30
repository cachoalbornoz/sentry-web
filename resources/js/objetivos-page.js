import { fetchRequiredJson } from './shared/http';
import {
    countObjetivosByEstado,
    getEventoObjetivoId,
    getObjetivoNameById,
    normalizeText,
    objectiveRoute,
    unwrapCollection,
} from './shared/objetivo-utils';
import { bootWhenReady } from './shared/page-boot';

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
        hasObjetivoScope: String(pageRoot?.dataset.hasObjetivosScope || '') === '1',
        allowedObjetivoIds: JSON.parse(pageRoot?.dataset.allowedObjetivosIds || '[]'),
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
        criticalStack: document.getElementById('global-critical-alerts-stack')
            || document.getElementById('objetivos-critical-alerts'),
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
    // El backend ya filtra por alcance dinámico; evitamos scope local estático.

    function isObjetivoAllowed(objetivoId) {
        return Number(objetivoId || 0) > 0;
    }

    function filterObjetivosByScope(items) {
        if (!Array.isArray(items)) return [];
        return items.filter((item) => isObjetivoAllowed(item?.id));
    }

    function filterEventosByScope(items) {
        if (!Array.isArray(items)) return [];
        return items.filter((event) => isObjetivoAllowed(getEventoObjetivoId(event)));
    }

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
        fetchJson: (url, timeoutMs) => fetchRequiredJson(url, {
            loginUrl: resolvedConfig.loginUrl,
            timeoutMs,
        }),
        objectiveRoute,
        getEstadoInfo,
        renderStateIcon,
        unwrapCollection,
        renderDatosTab,
        renderTable,
        sortEventosByFechaDesc,
    });

    const openObjetivoModal = (objetivoId) => modalController?.openObjetivoModal(objetivoId);

    function updateStats() {
        const counts = countObjetivosByEstado(state.objetivos);
        refs.statTotal.textContent = String(state.objetivos.length);
        refs.statOnline.textContent = String(counts.ONLINE);
        refs.statCritico.textContent = String(counts.CRITICO);
        refs.statOffline.textContent = String(counts.OFFLINE);
        refs.statMuerto.textContent = String(counts.MUERTO);
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
                objetivoNombre: getObjetivoNameById(state.objetivos, item.id),
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
            const payload = await fetchRequiredJson(resolvedConfig.objetivosUrl, {
                loginUrl: resolvedConfig.loginUrl,
                timeoutMs: 12000,
            });
            state.objetivos = filterObjetivosByScope(unwrapCollection(payload));
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
            const payload = await fetchRequiredJson(resolvedConfig.eventosListUrl, {
                loginUrl: resolvedConfig.loginUrl,
                timeoutMs: 12000,
            });
            state.eventos = filterEventosByScope(Array.isArray(payload) ? payload : []);
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

bootWhenReady('__sentryObjetivosPageInitialized', init);
