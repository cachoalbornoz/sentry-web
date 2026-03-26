if (window.__sentryLayoutShellInitialized) {
    // Evita doble binding cuando existe fallback inline en layout.
} else {
    window.__sentryLayoutShellInitialized = true;

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
    const url = window.SENTRY_LAYOUT?.apiStatusUrl;
    if (!url) return;
    try {
        const res = await fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        const data = await res.json().catch(() => null);
        if (res.status === 401 && data?.session_expired) {
            const loginUrl = window.SENTRY_LAYOUT?.loginUrl;
            if (loginUrl) window.location.href = loginUrl;
            return;
        }
        setApiConnectionStatus(res.ok);
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

document.addEventListener('DOMContentLoaded', () => {
    startClock();
    initProfileMenu();
    initLogoutButtons();
    checkApiConnection();
    if (window.SENTRY_LAYOUT?.apiStatusUrl) {
        setInterval(checkApiConnection, 15000);
    }
});
}
