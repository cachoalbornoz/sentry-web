import { escapeHtml } from './shared/html';

function renderCriticalIcon(size = 20) {
    return `
        <svg class="critical-alert-state-icon" width="${size}" height="${size}" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
            <polygon class="state-hex-fill" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
            <path class="state-mark-contrast" d="M32 20v14"></path>
            <circle class="state-mark-solid" cx="32" cy="40" r="3.2" fill="#f8fafc"></circle>
        </svg>
    `;
}

function render({
    container,
    alerts = [],
    title = 'Atención requerida en objetivo crítico',
    actionLabel = 'Ver objetivo',
    getName = (alert) => alert?.objetivoNombre || `Objetivo ${alert?.objetivoId ?? ''}`,
    getDescription = () => 'Se detectó un evento crítico para este objetivo.',
    onClose,
    onAction,
}) {
    if (!container) return;

    const normalizedAlerts = Array.isArray(alerts) ? alerts : [];
    if (normalizedAlerts.length === 0) {
        container.classList.add('hidden');
        container.innerHTML = '';
        window.SENTRY_CRITICAL_SOUND?.syncCriticalAlerts([]);
        return;
    }

    const orderedAlerts = [...normalizedAlerts].reverse();
    container.classList.remove('hidden');
    container.innerHTML = orderedAlerts.map((alert, index) => `
        <div class="critical-alert-card">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-100">
                    <span class="critical-alert-icon">${renderCriticalIcon(20)}</span>
                    ${escapeHtml(title)}
                </div>
                <button class="text-slate-400 hover:text-white text-sm leading-none critical-alert-close" data-alert-index="${index}">×</button>
            </div>
            <div class="mt-1 text-sm text-slate-200">${escapeHtml(getName(alert))}</div>
            <div class="mt-1 text-xs text-slate-400">${escapeHtml(getDescription(alert))}</div>
            <div class="mt-3">
                <button class="critical-alert-action rounded-md hover:bg-slate-800 critical-alert-primary" type="button" data-alert-index="${index}">
                    ${escapeHtml(actionLabel)}
                </button>
            </div>
        </div>
    `).join('');

    container.querySelectorAll('.critical-alert-close').forEach((button) => {
        button.addEventListener('click', () => {
            const alert = orderedAlerts[Number(button.dataset.alertIndex)];
            onClose?.(alert);
        });
    });

    container.querySelectorAll('.critical-alert-primary').forEach((button) => {
        button.addEventListener('click', () => {
            const alert = orderedAlerts[Number(button.dataset.alertIndex)];
            Promise.resolve(onAction?.(alert)).catch((error) => {
                window.console.error('Critical alert action failed:', error);
            });
        });
    });

    window.SENTRY_CRITICAL_SOUND?.syncCriticalAlerts(normalizedAlerts);
}

window.SENTRY_CRITICAL_ALERTS = {
    render,
};
