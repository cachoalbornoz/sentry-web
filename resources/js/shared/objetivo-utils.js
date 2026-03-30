export function normalizeText(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

export function objectiveRoute(template, objetivoId) {
    return String(template || '').replace('__OBJETIVO__', String(objetivoId));
}

function estadoKeyNormalized(estado) {
    return String(estado ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .trim();
}

export function countObjetivosByEstado(objetivos) {
    const counts = { ONLINE: 0, CRITICO: 0, OFFLINE: 0, MUERTO: 0 };
    for (const objetivo of objetivos || []) {
        const key = estadoKeyNormalized(objetivo?.estado);
        if (Object.prototype.hasOwnProperty.call(counts, key)) {
            counts[key] += 1;
        }
    }
    return counts;
}

export function unwrapCollection(payload) {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
}

export function getEventoObjetivoId(evento) {
    return Number(evento?.idObjetivo ?? evento?.objetivoId ?? evento?.objetivo_id ?? 0);
}

export function getEventoObjetivoNombre(evento) {
    return String(
        evento?.objetivo
        ?? evento?.objetivoNombre
        ?? evento?.nombreObjetivo
        ?? `Objetivo ${getEventoObjetivoId(evento)}`
    );
}

export function getObjetivoNameById(objetivos, objetivoId) {
    const objetivo = (objetivos || []).find((item) => Number(item.id) === Number(objetivoId));
    return objetivo?.nombre || objetivo?.descripcion || `Objetivo ${objetivoId}`;
}
