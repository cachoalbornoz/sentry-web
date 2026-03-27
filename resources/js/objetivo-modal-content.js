function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
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

function parseEventoDate(value) {
    const raw = String(value ?? '').trim();
    const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2}):(\d{2})$/);
    if (!match) return Number.NEGATIVE_INFINITY;

    const [, dd, mm, yyyy, hh, mi, ss] = match;

    return new Date(
        Number(yyyy),
        Number(mm) - 1,
        Number(dd),
        Number(hh),
        Number(mi),
        Number(ss)
    ).getTime();
}

function sortEventosByFechaDesc(eventos) {
    if (!Array.isArray(eventos)) return [];
    return [...eventos].sort((a, b) => parseEventoDate(b?.fechaHora) - parseEventoDate(a?.fechaHora));
}

window.SENTRY_OBJETIVO_MODAL_CONTENT = {
    renderDatosTab,
    renderTable,
    sortEventosByFechaDesc,
};
