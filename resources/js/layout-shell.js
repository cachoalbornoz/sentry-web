import { fetchJsonWithSession } from './shared/http';
import { getEventoObjetivoId, getObjetivoNameById } from './shared/objetivo-utils';
import { bootWhenReady } from './shared/page-boot';

function getLayoutConfig() {
    const body = document.body;
    return {
        apiStatusUrl: body?.dataset.apiStatusUrl || window.SENTRY_LAYOUT?.apiStatusUrl || '',
        loginUrl: body?.dataset.loginUrl || window.SENTRY_LAYOUT?.loginUrl || '',
        objetivosUrl: body?.dataset.objetivosUrl || '',
        eventosUrl: body?.dataset.eventosUrl || '',
        dashboardUrl: body?.dataset.dashboardUrl || '',
    };
}

function hasLocalCriticalController() {
    return Boolean(document.getElementById('inicio-page-config') || document.getElementById('objetivos-page'));
}

function syncGlobalCriticalAlertsWithSound(alerts) {
    window.SENTRY_CRITICAL_SOUND?.syncCriticalAlerts(Array.isArray(alerts) ? alerts : []);
}

function renderGlobalCriticalAlerts(alerts, dashboardUrl) {
    const stack = document.getElementById('global-critical-alerts-stack');
    if (!stack) return;

    window.SENTRY_CRITICAL_ALERTS?.render({
        container: stack,
        alerts,
        actionLabel: 'Ir a Inicio',
        getName: (alert) => alert?.objetivoNombre || `Objetivo ${alert?.objetivoId ?? ''}`,
        getDescription: () => 'Se detectó un evento crítico sin cedular.',
        onClose: () => {
            // El stack global no descarta alertas de forma local;
            // la fuente de verdad son objetivos/eventos del backend.
        },
        onAction: (alert) => {
            const target = dashboardUrl || '/dashboard';
            const id = Number(alert?.objetivoId || 0);
            if (id > 0) {
                window.location.href = `${target}#objetivo-${id}`;
                return;
            }
            window.location.href = target;
        },
    });

    // Defensa extra por si algún renderer falla: mantener el sonido sincronizado.
    syncGlobalCriticalAlertsWithSound(alerts);
}

function buildCriticalAlerts(objetivos, eventos) {
    const objetivosConEvento = new Set(
        (eventos || [])
            .map((event) => getEventoObjetivoId(event))
            .filter((id) => Number.isFinite(id) && id > 0)
    );

    const objetivosCriticos = (objetivos || []).filter(
        (item) => String(item?.estado || '').toUpperCase() === 'CRITICO'
    );

    return objetivosCriticos
        .map((item) => Number(item?.id || 0))
        .filter((id) => Number.isFinite(id) && id > 0 && objetivosConEvento.has(id))
        .map((objetivoId) => ({
            id: `global-critical-${objetivoId}`,
            objetivoId,
            objetivoNombre: getObjetivoNameById(objetivos, objetivoId),
        }));
}

function initGlobalCriticalAlerts() {
    if (hasLocalCriticalController()) return;

    const { objetivosUrl, eventosUrl, loginUrl, dashboardUrl } = getLayoutConfig();
    if (!objetivosUrl || !eventosUrl) return;

    let inFlight = false;

    const refresh = async () => {
        if (inFlight) return;
        inFlight = true;
        try {
            const [objetivosRes, eventosRes] = await Promise.all([
                fetchJsonWithSession(objetivosUrl, {
                    loginUrl,
                    timeoutMs: 10000,
                    options: { method: 'GET' },
                }),
                fetchJsonWithSession(eventosUrl, {
                    loginUrl,
                    timeoutMs: 10000,
                    options: { method: 'GET' },
                }),
            ]);

            if (!objetivosRes.ok || !eventosRes.ok) {
                renderGlobalCriticalAlerts([], dashboardUrl);
                return;
            }

            const objetivos = Array.isArray(objetivosRes.data?.data) ? objetivosRes.data.data : [];
            const eventos = Array.isArray(eventosRes.data) ? eventosRes.data : [];
            const alerts = buildCriticalAlerts(objetivos, eventos);
            renderGlobalCriticalAlerts(alerts, dashboardUrl);
        } catch (_) {
            renderGlobalCriticalAlerts([], dashboardUrl);
        } finally {
            inFlight = false;
        }
    };

    void refresh();
    window.setInterval(() => {
        void refresh();
    }, 15000);
}

function startClock() {
    const clockEl = document.getElementById('topbar-clock');
    if (!clockEl) return;
    const render = () => {
        clockEl.textContent = new Date().toLocaleTimeString('es-AR');
    };
    render();
    setInterval(render, 1000);
}

function setApiConnectionStatus(isConnected) {
    const dot = document.getElementById('api-status-dot');
    const text = document.getElementById('api-status-text');
    if (!dot || !text) return;
    if (isConnected) {
        dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-emerald-400';
        text.style.color = 'rgba(167, 243, 208, 0.85)';
        text.textContent = 'Conectado';
        return;
    }
    dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-red-400';
    text.style.color = 'rgba(254, 205, 211, 0.85)';
    text.textContent = 'Desconectado';
}

async function checkApiConnection() {
    const { apiStatusUrl, loginUrl } = getLayoutConfig();
    const url = apiStatusUrl;
    if (!url) return;
    try {
        const result = await fetchJsonWithSession(url, {
            loginUrl,
            timeoutMs: 8000,
            options: { method: 'GET' },
        });
        setApiConnectionStatus(result.ok);
    } catch (_) {
        setApiConnectionStatus(false);
    }
}

function initProfileMenu() {
    const toggle = document.getElementById('profile-menu-toggle');
    const panel = document.getElementById('profile-menu-panel');
    if (!toggle || !panel) return;
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.toggle('hidden');
    });
    panel.addEventListener('click', (e) => e.stopPropagation());
    document.addEventListener('click', () => panel.classList.add('hidden'));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') panel.classList.add('hidden');
    });
}

function initLogoutButtons() {
    document.querySelectorAll('.js-logout-form').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('.js-logout-btn');
            const label = form.querySelector('.js-logout-label');
            const loading = form.querySelector('.js-logout-loading');
            if (!btn || !label || !loading) return;
            btn.disabled = true;
            label.classList.add('hidden');
            loading.classList.remove('hidden');
            loading.classList.add('inline-flex');
            label.style.display = 'none';
            loading.style.display = 'inline-flex';
        });
    });
}

function init() {
    startClock();
    initProfileMenu();
    initLogoutButtons();
    initGlobalCriticalAlerts();
    checkApiConnection();
    if (getLayoutConfig().apiStatusUrl) {
        setInterval(checkApiConnection, 15000);
    }
}

bootWhenReady('__sentryLayoutShellInitialized', init);
