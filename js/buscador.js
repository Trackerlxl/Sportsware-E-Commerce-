// ============================================================
//  SPORTSWARE – buscador.js
//  Buscador en tiempo real con autocompletado.
//  MIGRACIÓN: ahora usa fetch a BASE_URL + 'php/buscar_sugerencias.php'
// ============================================================

const DEBOUNCE_MS = 280;

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchInput');
    const resultados = document.getElementById('searchResults');
    if (!input || !resultados) return;

    let debounceTimer = null;
    let controladorActual = null;

    input.addEventListener('input', () => {
        const query = input.value.trim();
        if (query.length < 2) { cerrarResultados(); return; }
        mostrarCargando();
        clearTimeout(debounceTimer);
        if (controladorActual) controladorActual.abort();
        debounceTimer = setTimeout(() => buscar(query), DEBOUNCE_MS);
    });

    document.addEventListener('click', (e) => { if (!e.target.closest('.search-box')) cerrarResultados(); });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { cerrarResultados(); input.blur(); return; }
        if (e.key === 'Enter') {
            const query = input.value.trim();
            if (query) { cerrarResultados(); window.location.href = (window.baseUrl || '') + 'html/productos.php?buscar=' + encodeURIComponent(query); }
        }
    });

    input.addEventListener('focus', () => { if (input.value.trim().length >= 2 && resultados.innerHTML !== '') resultados.classList.add('activo'); });

    async function buscar(query) {
        controladorActual = new AbortController();
        try {
            const base = window.baseUrl || '';
            const url = `${base}php/buscar_sugerencias.php?q=${encodeURIComponent(query)}`;
            const res = await fetch(url, { signal: controladorActual.signal });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.success) { mostrarError('No se pudo conectar al buscador.'); return; }
            renderizarResultados(data.resultados, query);
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.warn('[buscador]', err.message);
            mostrarError('Error al buscar. Intenta de nuevo.');
        }
    }

    function renderizarResultados(lista, query) {
        if (!lista || lista.length === 0) {
            resultados.innerHTML = `<div class="resultado-item" style="color:#94a3b8;cursor:default">Sin resultados para "<strong>${escaparHTML(query)}</strong>"</div>`;
            resultados.classList.add('activo');
            return;
        }
        resultados.innerHTML = lista.map(p => `
            <div class="resultado-item" onclick="irADetalle('${escaparHTML(p.id)}')">
                <div class="resultado-img-wrap"><img src="${escaparHTML(p.imagen)}" alt="${escaparHTML(p.nombre)}" onerror="this.onerror=null;this.src='https://placehold.co/48x48?text=SW'"></div>
                <div class="resultado-texto"><span class="resultado-nombre">${resaltarCoincidencia(escaparHTML(p.nombre), query)}</span><span class="resultado-meta">${escaparHTML(p.categoria)} · ${escaparHTML(p.marca)}</span></div>
                <span class="resultado-precio">$${p.precio.toLocaleString('es-CO')}</span>
            </div>`).join('');
        resultados.classList.add('activo');
    }

    function mostrarCargando() {
        resultados.innerHTML = `<div class="resultado-item resultado-cargando" style="color:#94a3b8;cursor:default;gap:10px"><span class="spinner-busqueda"></span> Buscando…</div>`;
        resultados.classList.add('activo');
    }

    function mostrarError(msg) {
        resultados.innerHTML = `<div class="resultado-item" style="color:#ef4444;cursor:default;font-size:13px"><i class="fa-solid fa-circle-exclamation" style="margin-right:6px"></i>${escaparHTML(msg)}</div>`;
        resultados.classList.add('activo');
    }

    function cerrarResultados() { resultados.classList.remove('activo'); }
});

function escaparHTML(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function resaltarCoincidencia(textoEscapado, query) {
    if (!query) return textoEscapado;
    const queryEscapada = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${queryEscapada})`, 'gi');
    return textoEscapado.replace(regex, '<mark style="background:#dbeafe;color:#1d4ed8;border-radius:2px">$1</mark>');
}

function irADetalle(id) {
    window.location.href = (window.baseUrl || '') + 'html/producto-detalle.php?id=' + encodeURIComponent(id);
}
window.irADetalle = irADetalle;