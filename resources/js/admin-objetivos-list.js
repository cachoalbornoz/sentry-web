/**
 * Listado de objetivos admin: búsqueda y orden por columnas.
 */
function getCell(o, key) {
    switch (key) {
        case 'codigo':
            return o.codigo;
        case 'descripcion':
            return (o.descripcion ?? '') === '' ? (o.nombre ?? '') : o.descripcion;
        case 'estado':
            return o.estado ?? '';
        case 'cliente':
            return o.cliente && o.cliente.nombre ? o.cliente.nombre : '';
        case 'jurisdiccion':
            return o.jurisdiccion && o.jurisdiccion.nombre ? o.jurisdiccion.nombre : '';
        default:
            return '';
    }
}

function displayCell(o, key) {
    const v = getCell(o, key);
    if (v == null || v === '') {
        return '—';
    }
    return String(v);
}

function sortRows(rows, key, dir) {
    const m = dir === 'asc' ? 1 : -1;
    return [...rows].sort((ra, rb) => {
        if (key === 'codigo') {
            const ca = ra.codigo;
            const cb = rb.codigo;
            if (ca == null && cb == null) {
                return 0;
            }
            if (ca == null) {
                return 1;
            }
            if (cb == null) {
                return -1;
            }
            return m * (Number(ca) - Number(cb));
        }
        const sa = String(getCell(ra, key)).toLowerCase();
        const sb = String(getCell(rb, key)).toLowerCase();
        return m * sa.localeCompare(sb, 'es', { numeric: true });
    });
}

function filterRows(rows, q) {
    const s = (q || '').trim().toLowerCase();
    if (s === '') {
        return rows;
    }
    return rows.filter((o) => {
        const text = [getCell(o, 'codigo'), getCell(o, 'descripcion'), getCell(o, 'estado'), getCell(o, 'cliente')]
            .map((v) => (v == null ? '' : String(v).toLowerCase()))
            .join(' ');
        return text.includes(s);
    });
}

function buildRow(o, baseUrl, csrf) {
    const id = o.id;
    const verUrl = `${baseUrl}/${id}`;
    const editUrl = `${baseUrl}/${id}/editar`;
    return `
    <tr class="border-b border-slate-800/80 hover:bg-slate-900/50" data-objetivo-id="${id}">
        <td class="px-2 py-2.5 pl-3 text-slate-200 md:px-3">${escapeHtml(displayCell(o, 'codigo'))}</td>
        <td class="max-w-[9rem] px-1 py-2.5 text-slate-100 sm:max-w-none md:px-3">
            <span class="line-clamp-2 sm:line-clamp-none" title="${escapeHtml(displayCell(o, 'descripcion'))}">${escapeHtml(displayCell(o, 'descripcion'))}</span>
        </td>
        <td class="hidden px-3 py-2.5 text-slate-300 md:table-cell">${escapeHtml(displayCell(o, 'estado'))}</td>
        <td class="hidden px-3 py-2.5 text-slate-300 md:table-cell">${escapeHtml(displayCell(o, 'cliente'))}</td>
        <td class="hidden px-3 py-2.5 text-slate-300 md:table-cell">${escapeHtml(displayCell(o, 'jurisdiccion'))}</td>
        <td class="px-2 py-2 pr-3 text-sm align-top md:px-3">
            <div class="hidden flex-wrap items-center justify-end gap-x-1 text-right md:flex">
                <a href="${verUrl}" class="text-sky-400 hover:text-sky-300">Ver</a>
                <span class="text-slate-600" aria-hidden="true">·</span>
                <a href="${editUrl}" class="text-slate-300 hover:text-white">Editar</a>
                <form action="${baseUrl}/${id}" method="post" class="inline" onsubmit="return confirm('¿Dar de baja lógicamente este objetivo?');">
                    <input type="hidden" name="_token" value="${escapeHtml(csrf)}" />
                    <input type="hidden" name="_method" value="DELETE" />
                    <button type="submit" class="ml-1 text-rose-400/90 hover:text-rose-300">Eliminar</button>
                </form>
            </div>
            <details class="md:hidden">
                <summary
                    class="ml-auto w-fit cursor-pointer list-none rounded-md border border-slate-600 bg-slate-800/90 px-2.5 py-1.5 text-left text-xs font-medium text-slate-200 hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
                >Acciones <span class="text-slate-500" aria-hidden="true">▾</span></summary>
                <div class="mt-1.5 min-w-[10rem] space-y-0.5 rounded-lg border border-slate-600 bg-slate-900/98 p-1.5 shadow-lg ring-1 ring-slate-700/50">
                    <a href="${verUrl}" class="block rounded-md px-2 py-2 text-sky-400 hover:bg-slate-800">Ver</a>
                    <a href="${editUrl}" class="block rounded-md px-2 py-2 text-slate-200 hover:bg-slate-800">Editar</a>
                    <form action="${baseUrl}/${id}" method="post" onsubmit="return confirm('¿Dar de baja lógicamente este objetivo?');">
                        <input type="hidden" name="_token" value="${escapeHtml(csrf)}" />
                        <input type="hidden" name="_method" value="DELETE" />
                        <button type="submit" class="w-full rounded-md px-2 py-2 text-left text-rose-400 hover:bg-slate-800">Eliminar</button>
                    </form>
                </div>
            </details>
        </td>
    </tr>`;
}

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function init() {
    const root = document.getElementById('admin-objetivos-list-root');
    if (!root) return;

    const raw = document.getElementById('admin-objetivos-data');
    if (!raw) return;

    let rows;
    try {
        rows = JSON.parse(raw.textContent);
    } catch {
        return;
    }
    if (!Array.isArray(rows)) {
        return;
    }

    const baseUrl = root.dataset.baseUrl || '';
    const csrf = root.dataset.csrf || '';
    const searchInput = document.getElementById('admin-objetivos-search');
    const tbody = document.getElementById('admin-objetivos-tbody');
    const thSort = root.querySelectorAll('[data-sort-key]');

    let sortKey = 'descripcion';
    let sortDir = 'asc';

    function setSortIndicators() {
        thSort.forEach((th) => {
            const k = th.getAttribute('data-sort-key');
            const ind = th.querySelector('.sort-ind');
            if (!ind) return;
            if (k === sortKey) {
                ind.textContent = sortDir === 'asc' ? ' ▲' : ' ▼';
            } else {
                ind.textContent = '';
            }
        });
    }

    function render() {
        if (!tbody) return;
        if (rows.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No hay objetivos para listar.</td></tr>';
            return;
        }
        const filtered = filterRows(rows, searchInput ? searchInput.value : '');
        const sorted = sortRows(filtered, sortKey, sortDir);
        if (sorted.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Ningún objetivo coincide con la búsqueda.</td></tr>';
            return;
        }
        tbody.innerHTML = sorted.map((o) => buildRow(o, baseUrl, csrf)).join('');
    }

    thSort.forEach((th) => {
        th.addEventListener('click', () => {
            const k = th.getAttribute('data-sort-key');
            if (!k) return;
            if (k === sortKey) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortKey = k;
                sortDir = 'asc';
            }
            setSortIndicators();
            render();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => render());
    }

    setSortIndicators();
    render();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
