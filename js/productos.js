// ============================================================
//  SPORTSWARE – productos.js
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('productosGrid');
    if (!grid) return;

    const params = new URLSearchParams(window.location.search);
    const filtroURL = params.get('filtro');
    const categoria = params.get('categoria');
    const buscar = params.get('buscar');
    const genero = params.get('genero');

    const h1 = document.querySelector('main h1');
    if (filtroURL === 'oferta' && h1) h1.textContent = '🔥 Productos en Oferta';
    else if (categoria && h1) h1.textContent = `Categoría: ${capitalize(categoria)}`;
    else if (buscar && h1) h1.textContent = `Resultados para: "${buscar}"`;
    else if (genero && h1) h1.textContent = `Género: ${genero === 'hombre' ? 'Hombre' : 'Mujer'}`;

    let filtroActivo = null;
    if (filtroURL === 'oferta') { filtroActivo = 'oferta'; const cbOfertas = document.getElementById('filtroOfertas'); if (cbOfertas) cbOfertas.checked = true; }

    if (categoria) { const mapCat = { masculino: 'camisetas', femenino: 'leggings', calzado: 'tenis', accesorios: 'accesorios' }; const cat = mapCat[categoria] || categoria; const cb = document.querySelector(`input[type="checkbox"][value="${cat}"]`); if (cb) cb.checked = true; }
    if (genero) { const radio = document.querySelector(`.filtros input[type="radio"][value="${genero}"]`); if (radio) radio.checked = true; }

    renderizarProductos();
    document.querySelectorAll('.filtros input[type="checkbox"], .filtros input[type="radio"]').forEach(input => input.addEventListener('change', renderizarProductos));

    function renderizarProductos() {
        const productos = window.productos || [];
        const cats = Array.from(document.querySelectorAll('.filtros input[type="checkbox"]:checked')).filter(cb => cb.id !== 'filtroOfertas').map(cb => cb.value);
        const generoRadio = document.querySelector('.filtros input[type="radio"]:checked'); const generoSel = generoRadio ? generoRadio.value : 'todos';
        const cbOfertas = document.getElementById('filtroOfertas'); const soloOferta = (filtroURL === 'oferta') || (cbOfertas && cbOfertas.checked);

        let resultado = productos.filter(p => {
            const passCategoria = cats.length === 0 || cats.includes(p.categoria);
            const passGenero = generoSel === 'todos' || p.genero === generoSel;
            const passOferta = !soloOferta || p.esOferta === true;
            const passBuscar = !buscar || p.nombre.toLowerCase().includes(buscar.toLowerCase()) || p.categoria.toLowerCase().includes(buscar.toLowerCase()) || p.marca.toLowerCase().includes(buscar.toLowerCase());
            return passCategoria && passGenero && passOferta && passBuscar;
        });

        if (resultado.length === 0) {
            grid.innerHTML = `<div class="productos-vacio" style="grid-column:1/-1"><i class="fa-solid fa-box-open"></i><p>${soloOferta ? 'No hay ofertas disponibles en este momento.' : 'No se encontraron productos con los filtros seleccionados.'}</p><a href="${window.baseUrl || ''}html/productos.php" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">Ver todos los productos</a></div>`;
            return;
        }

        grid.innerHTML = resultado.map(p => crearTarjeta(p)).join('');
    }

    function crearTarjeta(p) {
        const precioAntes = p.precioAntes ? `<span class="producto-precio-antes">$${p.precioAntes.toLocaleString('es-CO')}</span>` : '';
        const badge = p.esOferta ? `<span class="producto-badge">Oferta −${p.descuento}%</span>` : '';
        const base = window.baseUrl || '';
        return `<div class="producto-card" onclick="location.href='${base}html/producto-detalle.php?id=${p.id}'"><div class="producto-img-wrap"><img src="${p.imagen}" alt="${p.nombre}" onerror="this.onerror=null;this.src='https://placehold.co/400x400?text=SPORTSWARE'">${badge}</div><div class="producto-info"><span class="producto-categoria">${capitalize(p.categoria)}</span><h3 class="producto-nombre">${p.nombre}</h3><p class="producto-marca">${p.marca}</p><div class="producto-precios"><span class="producto-precio">$${p.precio.toLocaleString('es-CO')}</span>${precioAntes}</div><button class="btn-detalle" onclick="event.stopPropagation();location.href='${base}html/producto-detalle.php?id=${p.id}'">Ver detalle</button></div></div>`;
    }
});

function capitalize(str) { return str ? str.charAt(0).toUpperCase() + str.slice(1) : ''; }