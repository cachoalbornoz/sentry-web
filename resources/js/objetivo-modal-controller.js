import { escapeHtml } from './shared/html';

function create({
    state,
    refs,
    urls,
    fetchJson,
    objectiveRoute,
    getEstadoInfo,
    renderStateIcon,
    unwrapCollection,
    renderDatosTab,
    renderTable,
    sortEventosByFechaDesc,
}) {
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
            if (cache.eventosLoading) {
                refs.tabContent.innerHTML = '<div class="objetivo-tab-loading">Obteniendo eventos, aguarde...</div>';
                return;
            }

            if (cache.eventosError) {
                refs.tabContent.innerHTML = `<div class="objetivo-tab-error">${escapeHtml(cache.eventosError)}</div>`;
                return;
            }

            refs.tabContent.innerHTML = renderTable(
                [
                    { key: 'tipoSenal', title: 'Tipo de Señal' },
                    { key: 'fechaHora', title: 'Fecha y Hora' },
                    { key: 'zona', title: 'Zona' },
                ],
                sortEventosByFechaDesc(cache.eventos),
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

    async function ensureObjetivoEventos(objetivoId) {
        const cacheKey = String(objetivoId);
        const cache = state.detailsById[cacheKey];
        if (!cache) {
            return null;
        }

        if (cache.eventosLoaded || cache.eventosLoading) {
            return cache;
        }

        cache.eventosLoading = true;
        cache.eventosError = null;
        renderActiveTab();

        try {
            const eventos = await fetchJson(objectiveRoute(urls.eventos, objetivoId), 12000);
            cache.eventos = sortEventosByFechaDesc(Array.isArray(eventos?.eventos) ? eventos.eventos : []);
            cache.eventosLoaded = true;
        } catch (error) {
            cache.eventosError = error?.message || 'No se pudieron obtener los eventos.';
        } finally {
            cache.eventosLoading = false;
            renderActiveTab();
        }

        return cache;
    }

    async function setActiveTab(tab) {
        state.activeTab = tab;
        refs.tabs.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.tab === tab);
        });
        renderActiveTab();

        if (tab === 'eventos' && state.activeObjetivoId) {
            await ensureObjetivoEventos(state.activeObjetivoId);
        }
    }

    async function ensureObjetivoBaseDetails(objetivoId) {
        const cacheKey = String(objetivoId);
        if (state.detailsById[cacheKey] && !state.detailsById[cacheKey].loading) {
            return state.detailsById[cacheKey];
        }

        state.detailsById[cacheKey] = { loading: true };
        renderActiveTab();

        try {
            const [detalle, contactos, zonas] = await Promise.all([
                fetchJson(objectiveRoute(urls.detalle, objetivoId), 12000),
                fetchJson(objectiveRoute(urls.contactos, objetivoId), 12000),
                fetchJson(objectiveRoute(urls.zonas, objetivoId), 12000),
            ]);

            state.detailsById[cacheKey] = {
                loading: false,
                objetivo: detalle?.data || detalle || {},
                contactos: unwrapCollection(contactos),
                eventos: [],
                eventosLoaded: false,
                eventosLoading: false,
                eventosError: null,
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
        await setActiveTab('datos');
        await ensureObjetivoBaseDetails(objetivoId);
        renderActiveTab();
    }

    function closeObjetivoModal() {
        refs.modalBackdrop.classList.add('hidden');
        document.body.style.overflow = '';
        state.activeObjetivoId = null;
    }

    function bindUi() {
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
            button.addEventListener('click', () => {
                void setActiveTab(button.dataset.tab);
            });
        });
    }

    return {
        bindUi,
        renderActiveTab,
        setActiveTab,
        openObjetivoModal,
        closeObjetivoModal,
        ensureObjetivoBaseDetails,
        ensureObjetivoEventos,
    };
}

window.SENTRY_OBJETIVO_MODAL_CONTROLLER = {
    create,
};
