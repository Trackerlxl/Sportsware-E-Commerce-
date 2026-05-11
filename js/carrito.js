// ============================================================
//  SPORTSWARE – carrito.js
//  Gestión del carrito con soporte para BASE_URL
// ============================================================

const CLAVE_CARRITO   = 'carrito_sportsware';
const ENDPOINT_SYNC   = (window.baseUrl || '') + 'php/carrito_sync.php';

function estaLogueado() { return window.usuarioLogueado === true; }

function obtenerCarrito() { return JSON.parse(localStorage.getItem(CLAVE_CARRITO) || '[]'); }
function guardarCarrito(carrito) { localStorage.setItem(CLAVE_CARRITO, JSON.stringify(carrito)); if (typeof actualizarContadorCarrito === 'function') actualizarContadorCarrito(); }

async function _syncBD(payload) {
    try {
        const res = await fetch(ENDPOINT_SYNC, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        if (!res.ok) { if (res.status === 401) return null; console.warn('[carrito_sync] HTTP', res.status); return null; }
        return await res.json();
    } catch (err) { console.warn('[carrito_sync] Error de red:', err.message); return null; }
}

async function sincronizarCarritoConBackend() {
    if (!estaLogueado()) return;
    const carritoLocal = obtenerCarrito();
    const data = await _syncBD({ accion: 'merge', carrito: carritoLocal });
    if (data && data.success && Array.isArray(data.carrito)) { guardarCarrito(data.carrito); if (typeof renderizarCarrito === 'function') renderizarCarrito(); }
}

function agregarAlCarrito(id, cantidad = 1, talla = '') {
    const producto = (window.productos || []).find(p => p.id === id);
    if (!producto) return;
    const carrito = obtenerCarrito();
    const clave = talla ? `${id}-${talla}` : id;
    const existente = carrito.find(item => item.clave === clave);
    if (existente) existente.cantidad += cantidad;
    else carrito.push({ clave, id, nombre: producto.nombre, precio: producto.precio, imagen: producto.imagen, talla, cantidad });
    guardarCarrito(carrito);
    if (typeof mostrarToast === 'function') mostrarToast(`"${producto.nombre}" añadido al carrito ✓`);
    if (estaLogueado()) _syncBD({ accion: 'agregar', id, talla, cantidad });
}

function eliminarDelCarrito(clave) {
    const carrito = obtenerCarrito();
    const item = carrito.find(i => i.clave === clave);
    const productoId = item ? item.id : null;
    const talla = item ? item.talla : null;
    const nuevo = carrito.filter(i => i.clave !== clave);
    guardarCarrito(nuevo);
    if (typeof renderizarCarrito === 'function') renderizarCarrito();
    if (estaLogueado() && productoId) _syncBD({ accion: 'eliminar', id: productoId, talla: talla ?? '' });
}

function actualizarCantidad(clave, nuevaCantidad) {
    const carrito = obtenerCarrito();
    const item = carrito.find(i => i.clave === clave);
    const cantidad = Math.max(1, parseInt(nuevaCantidad) || 1);
    if (item) item.cantidad = cantidad;
    guardarCarrito(carrito);
    if (typeof renderizarCarrito === 'function') renderizarCarrito();
    if (estaLogueado() && item) _syncBD({ accion: 'actualizar', id: item.id, talla: item.talla ?? '', cantidad });
}

function vaciarCarrito() {
    guardarCarrito([]);
    if (typeof renderizarCarrito === 'function') renderizarCarrito();
    if (estaLogueado()) _syncBD({ accion: 'vaciar' });
}

function calcularTotales() {
    const carrito = obtenerCarrito();
    const subtotal = carrito.reduce((sum, item) => sum + item.precio * item.cantidad, 0);
    const envio = carrito.length > 0 ? 10000 : 0;
    return { subtotal, envio, total: subtotal + envio };
}

function renderizarCarrito() {
    const contenedor = document.getElementById('carritoContenido');
    if (!contenedor) return;
    const carrito = obtenerCarrito();
    if (carrito.length === 0) {
        contenedor.innerHTML = `<div class="carrito-vacio" style="grid-column:1/-1"><i class="fa-solid fa-cart-shopping"></i><h2>Tu carrito está vacío</h2><p>Agrega productos para continuar comprando</p><a href="${window.baseUrl || ''}html/productos.php" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">Seguir comprando</a></div>`;
        return;
    }
    const { subtotal, envio, total } = calcularTotales();
    const filasHTML = carrito.map(item => `
        <tr><td><div class="carrito-producto"><img class="carrito-producto-img" src="${item.imagen}" alt="${item.nombre}" onerror="this.onerror=null;this.src='https://placehold.co/200x200?text=SW'"><div class="carrito-producto-info"><span class="carrito-producto-nombre">${item.nombre}</span>${item.talla ? `<span class="carrito-producto-meta">Talla: ${item.talla}</span>` : ''}</div></div></td>
        <td class="carrito-precio">$${item.precio.toLocaleString('es-CO')}</td>
        <td><div class="carrito-cantidad"><button onclick="actualizarCantidad('${item.clave}', ${item.cantidad - 1})">−</button><input type="number" value="${item.cantidad}" min="1" onchange="actualizarCantidad('${item.clave}', this.value)"><button onclick="actualizarCantidad('${item.clave}', ${item.cantidad + 1})">+</button></div></td>
        <td class="carrito-subtotal">$${(item.precio * item.cantidad).toLocaleString('es-CO')}</td>
        <td><button class="btn-eliminar" onclick="eliminarDelCarrito('${item.clave}')" title="Eliminar"><i class="fa-solid fa-trash"></i></button></td>
        </tr>`).join('');
    contenedor.innerHTML = `
        <div class="carrito-items"><div class="carrito-items-header"><h2>Mi carrito (${carrito.length} ${carrito.length === 1 ? 'producto' : 'productos'})</h2><button class="btn-vaciar" onclick="vaciarCarrito()"><i class="fa-solid fa-trash"></i> Vaciar carrito</button></div>
        <table class="carrito-tabla"><thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr></thead><tbody>${filasHTML}</tbody></table></div>
        <div class="carrito-resumen"><h2>Resumen del pedido</h2><p><span>Subtotal:</span><span>$${subtotal.toLocaleString('es-CO')}</span></p><p><span>Envío:</span><span>$${envio.toLocaleString('es-CO')}</span></p><p class="resumen-total"><span>Total:</span><span>$${total.toLocaleString('es-CO')}</span></p>
        <a href="${window.baseUrl || ''}html/checkout.php" class="btn-pagar"><i class="fa-solid fa-lock"></i> Proceder al pago</a>
        <div class="metodos-pago"><p>Métodos aceptados</p><div class="metodos-pago-icons"><span class="metodo-icon">VISA</span><span class="metodo-icon">MC</span><span class="metodo-icon">PSE</span><span class="metodo-icon">CASH</span></div></div></div>`;
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('carritoContenido')) renderizarCarrito();
    if (estaLogueado()) { _syncBD({ accion: 'obtener' }).then(data => { if (data && data.success && Array.isArray(data.carrito) && data.carrito.length > 0 && obtenerCarrito().length === 0) { guardarCarrito(data.carrito); if (document.getElementById('carritoContenido')) renderizarCarrito(); } }); }
});

window.agregarAlCarrito = agregarAlCarrito;
window.eliminarDelCarrito = eliminarDelCarrito;
window.actualizarCantidad = actualizarCantidad;
window.vaciarCarrito = vaciarCarrito;
window.obtenerCarrito = obtenerCarrito;
window.calcularTotales = calcularTotales;
window.sincronizarCarritoConBackend = sincronizarCarritoConBackend;