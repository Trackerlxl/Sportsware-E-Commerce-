// ============================================================
//  SPORTSWARE – detalle.js
//  No requiere cambios de ruta (usa agregarAlCarrito y productos globales)
//  Solo se añade window.baseUrl por si se necesita.
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    const producto = (window.productos || []).find(p => p.id === id);

    if (!producto) {
        const main = document.querySelector('main');
        if (main) {
            main.innerHTML = `<div style="text-align:center;padding:60px 20px;color:#64748b"><i class="fa-solid fa-box-open" style="font-size:48px;opacity:.3;display:block;margin-bottom:16px"></i><h2>Producto no encontrado</h2><p>El producto que buscas no existe o fue eliminado.</p><a href="${window.baseUrl || ''}html/productos.php" class="btn btn-primary" style="margin-top:20px;display:inline-flex;">Ver todos los productos</a></div>`;
        }
        return;
    }

    const elNombre = document.getElementById('detalleNombre');
    const elMarca = document.getElementById('detalleMarca');
    const elPrecio = document.getElementById('detallePrecio');
    const elDescripcion = document.getElementById('detalleDescripcion');
    const elImagen = document.getElementById('detalleImagen');
    const elCategoria = document.getElementById('detalleCategoria');

    if (elNombre) elNombre.textContent = producto.nombre;
    if (elMarca) elMarca.textContent = producto.marca;
    if (elCategoria) elCategoria.textContent = producto.categoria.charAt(0).toUpperCase() + producto.categoria.slice(1);
    if (elDescripcion) elDescripcion.textContent = producto.descripcion;
    if (elImagen) {
        elImagen.src = producto.imagen;
        elImagen.alt = producto.nombre;
        elImagen.onerror = function() { this.onerror = null; this.src = 'https://placehold.co/500x500?text=SPORTSWARE'; };
    }

    if (elPrecio) {
        if (producto.precioAntes && producto.precioAntes > 0) {
            elPrecio.innerHTML = `<span>$${producto.precio.toLocaleString('es-CO')}</span><span class="detalle-precio-antes">$${producto.precioAntes.toLocaleString('es-CO')}</span><span class="detalle-descuento">−${producto.descuento}%</span>`;
        } else { elPrecio.textContent = `$${producto.precio.toLocaleString('es-CO')}`; }
    }

    document.title = `SPORTSWARE | ${producto.nombre}`;

    const tallasContainer = document.querySelector('.tallas');
    if (tallasContainer && producto.tallas && producto.tallas.length > 0) {
        tallasContainer.innerHTML = producto.tallas.map(t => `<span class="talla" data-talla="${t}">${t}</span>`).join('');
        let tallaSeleccionada = '';
        tallasContainer.querySelectorAll('.talla').forEach(el => {
            el.addEventListener('click', () => { tallasContainer.querySelectorAll('.talla').forEach(t => t.classList.remove('active')); el.classList.add('active'); tallaSeleccionada = el.dataset.talla; });
        });
        const btnAnadir = document.getElementById('añadirCarritoBtn');
        if (btnAnadir) {
            btnAnadir.addEventListener('click', () => {
                if (producto.tallas.length > 1 && !tallaSeleccionada && producto.tallas[0] !== 'Único') {
                    if (typeof mostrarToast === 'function') mostrarToast('Por favor selecciona una talla', 'error');
                    else alert('Por favor selecciona una talla');
                    return;
                }
                agregarAlCarrito(producto.id, 1, tallaSeleccionada);
            });
        }
    } else {
        const tallasLabel = document.querySelector('.detalle-tallas-label');
        if (tallasLabel) tallasLabel.style.display = 'none';
    }
});