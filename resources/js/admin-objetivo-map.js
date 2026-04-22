import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import iconRetina from 'leaflet/dist/images/marker-icon-2x.png';
import icon from 'leaflet/dist/images/marker-icon.png';
import iconShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: iconRetina,
    iconUrl: icon,
    shadowUrl: iconShadow,
});

const DEFAULT_VIEW = { lat: -32.06, lng: -59.23, zoom: 7 };

/**
 * Heurística: muchas cargas erróneas intercambian lat/lon. En el Cono Sur:
 * la latitud suele ser > -50 y la longitud < -40 (más "negativa").
 * Un par tipo lat=-58, lon=-34 coloca el punto en el mar (p. ej. frente a la Antártida
 * visto desde el cono) — corresponde a -34, -58.
 * Si el primer valor "parece" longitud (|v|>42 en cono) y el segundo "latitud" (|v|<42), intercambiamos.
 */
function normalizeLatLngPair(lat, lng) {
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return { lat, lng, swapped: false };
    }
    if (Math.abs(lat) > 42 && Math.abs(lat) < 90 && Math.abs(lng) < 42) {
        return { lat: lng, lng: lat, swapped: true };
    }
    return { lat, lng, swapped: false };
}

function readFloat(input) {
    if (!input) return null;
    const n = parseFloat(String(input.value).replace(',', '.'));
    return Number.isFinite(n) ? n : null;
}

function initViewMode(el, template) {
    const la = parseFloat(String(el.dataset.initialLat ?? '').replace(',', '.'));
    const lo = parseFloat(String(el.dataset.initialLng ?? '').replace(',', '.'));
    let lat = Number.isFinite(la) ? la : DEFAULT_VIEW.lat;
    let lng = Number.isFinite(lo) ? lo : DEFAULT_VIEW.lng;

    const n = normalizeLatLngPair(lat, lng);
    lat = n.lat;
    lng = n.lng;

    const map = L.map(el, { zoomControl: true, scrollWheelZoom: true });
    L.tileLayer(template, { maxZoom: 18, attribution: '&copy; CARTO' }).addTo(map);
    map.setView([lat, lng], 14);
    L.marker([lat, lng], { draggable: false }).addTo(map);
    if (n.swapped) {
        const wrap = el.parentElement;
        if (wrap && !wrap.querySelector('.map-coords-note')) {
            const p = document.createElement('p');
            p.className = 'map-coords-note mt-2 text-xs text-amber-200/90';
            p.textContent =
                'Las coordenadas parecían invertidas: se ajustó la posición en el mapa. Si en base siguen al revés, corregilas con Editar.';
            wrap.appendChild(p);
        }
    }

    setTimeout(() => {
        try {
            map.invalidateSize();
        } catch {
            // ignore
        }
    }, 200);
    window.addEventListener('resize', () => {
        try {
            map.invalidateSize();
        } catch {
            // ignore
        }
    });
}

/**
 * Formulario: sincroniza marcador con #latitud / #longitud; corrige posible inversión al iniciar
 */
function initFormMode(el, template) {
    const latIn = document.getElementById('latitud');
    const lngIn = document.getElementById('longitud');
    if (!latIn || !lngIn) {
        return;
    }

    let lat = readFloat(latIn);
    let lng = readFloat(lngIn);
    if (lat == null) lat = DEFAULT_VIEW.lat;
    if (lng == null) lng = DEFAULT_VIEW.lng;

    const n = normalizeLatLngPair(lat, lng);
    lat = n.lat;
    lng = n.lng;
    if (n.swapped) {
        latIn.value = String(Math.round(lat * 1e6) / 1e6);
        lngIn.value = String(Math.round(lng * 1e6) / 1e6);
        const help = document.getElementById('admin-map-coords-hint');
        if (help) {
            help.classList.remove('hidden');
            help.textContent =
                'Se detectaron posibles coordenadas intercambiadas; se ajustó latitud y longitud. Revisá el mapa y guardá si es correcto.';
        }
    }

    const map = L.map(el, { zoomControl: true });
    L.tileLayer(template, { maxZoom: 18, attribution: '&copy; CARTO' }).addTo(map);
    map.setView([lat, lng], 14);

    const marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    function writeInputs(ll) {
        const la = Math.round(ll.lat * 1e6) / 1e6;
        const lo = Math.round(ll.lng * 1e6) / 1e6;
        latIn.value = String(la);
        lngIn.value = String(lo);
    }

    marker.on('dragend', (e) => {
        const ll = e.target.getLatLng();
        writeInputs(ll);
    });

    const onInput = () => {
        const a = readFloat(latIn);
        const b = readFloat(lngIn);
        if (a == null || b == null) return;
        // Sin heurística en cada tecla: el usuario controla; el mapa refleja los valores tipeados
        marker.setLatLng([a, b]);
        map.panTo([a, b], { animate: false });
    };
    latIn.addEventListener('change', onInput);
    latIn.addEventListener('input', onInput);
    lngIn.addEventListener('change', onInput);
    lngIn.addEventListener('input', onInput);

    map.on('click', (e) => {
        const { lat: clat, lng: clng } = e.latlng;
        marker.setLatLng([clat, clng]);
        writeInputs(e.latlng);
    });

    setTimeout(() => {
        try {
            map.invalidateSize();
        } catch {
            // ignore
        }
    }, 200);

    window.addEventListener('resize', () => {
        try {
            map.invalidateSize();
        } catch {
            // ignore
        }
    });
}

function init() {
    const el = document.getElementById('admin-objetivo-map');
    if (!el) return;

    const template = el.dataset.tileTemplate;
    if (!template) return;

    const mode = el.dataset.mapMode || 'form';
    if (mode === 'view') {
        initViewMode(el, template);
    } else {
        initFormMode(el, template);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
