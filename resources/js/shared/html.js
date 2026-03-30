export function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

export function formatValue(value, fallback = '—') {
    const normalized = value === null || value === undefined || value === '' ? fallback : String(value);
    return escapeHtml(normalized);
}
