import { escapeHtml } from './shared/html';

function getEstadoInfo(estado) {
    const key = String(estado || '').toUpperCase();
    const map = {
        ONLINE: { label: 'En linea', className: 'estado-online', iconType: 'activo' },
        CRITICO: { label: 'Critico', className: 'estado-critico', iconType: 'critico' },
        OFFLINE: { label: 'Inactivo', className: 'estado-offline', iconType: 'inactivo' },
        MUERTO: { label: 'Sin senal', className: 'estado-muerto', iconType: 'apagado' },
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

function render(objetivo) {
    const info = getEstadoInfo(objetivo?.estado);
    const jurisdiccion = objetivo?.jurisdiccion?.nombre || 'Sin jurisdiccion';
    const cliente = objetivo?.cliente?.nombre || 'Sin cliente';

    return `
        <button class="objetivo-card" type="button" data-objetivo-id="${escapeHtml(objetivo?.id)}">
            <div class="objetivo-card-top">
                <div class="objetivo-icon ${info.className}">${renderStateIcon(info.iconType, 42)}</div>
                <div class="objetivo-status ${info.className}">
                    <span class="objetivo-status-dot"></span>
                    <span>${escapeHtml(info.label)}</span>
                </div>
            </div>
            <div class="objetivo-card-body">
                <div class="objetivo-name">${escapeHtml(objetivo?.nombre || objetivo?.descripcion || `Objetivo ${objetivo?.id ?? ''}`)}</div>
                <div class="objetivo-meta">Codigo: SG - ${escapeHtml(objetivo?.codigo ?? '—')}</div>
                <div class="objetivo-meta">${escapeHtml(cliente)} · ${escapeHtml(jurisdiccion)}</div>
            </div>
        </button>
    `;
}

window.SENTRY_OBJETIVO_CARD = {
    getEstadoInfo,
    renderStateIcon,
    render,
};
