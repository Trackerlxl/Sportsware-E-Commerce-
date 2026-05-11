<?php
require_once __DIR__ . '/../php/verificar_sesion.php';
require_once __DIR__ . '/../php/config.php';

$usuario = [];
try {
    $stmt = $pdo->prepare('SELECT nombre, email, telefono, direccion FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch() ?: [];
} catch (PDOException $e) {
    error_log('[SPORTSWARE][checkout] ' . $e->getMessage());
}

function val(array $datos, string $clave): string {
    return htmlspecialchars($datos[$clave] ?? '', ENT_QUOTES, 'UTF-8');
}

$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$flashError    = sesion_flash('flash_error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPORTSWARE | Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/carrito.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;600;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="navbar">
    <a href="<?= BASE_URL ?>html/home.php" class="logo">SPORTSWARE</a>
    <nav class="menu">
        <a href="<?= BASE_URL ?>html/home.php">Inicio</a>
        <a href="<?= BASE_URL ?>html/productos.php">Categorías</a>
        <a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a>
    </nav>
    <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Buscar productos..." id="searchInput">
        <div class="resultados" id="searchResults"></div>
    </div>
    <div class="icons">
        <a href="<?= BASE_URL ?>html/carrito.php" style="color:white;position:relative;">
            <i class="fa-solid fa-cart-shopping"></i>
            <span id="contadorCarrito" class="cart-badge">0</span>
        </a>
        <a href="<?= BASE_URL ?>html/perfil.php" style="color:white;display:flex;align-items:center;gap:6px;text-decoration:none;">
            <i class="fa-solid fa-circle-user"></i>
            <span style="font-size:13px;font-weight:600;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?= $nombreUsuario ?>
            </span>
        </a>
    </div>
</header>

<?php if ($flashError): ?>
    <div style="background:#fef2f2;color:#b91c1c;padding:12px 24px;text-align:center;font-size:14px;border-bottom:1px solid #fecaca;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= $flashError ?>
    </div>
<?php endif; ?>

<main class="carrito-container" style="grid-template-columns:2fr 1fr;margin-top:30px;">
    <div style="background:#fff;border-radius:12px;box-shadow:var(--sombra);padding:28px;">
        <h1 style="margin-bottom:24px">Finalizar compra</h1>
        <form id="checkoutForm" onsubmit="return false;">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" id="chkNombre" placeholder="Juan Pérez" value="<?= val($usuario, 'nombre') ?>" required style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:14px;margin-top:6px">
            </div>
            <div class="form-group" style="margin-top:16px">
                <label>Dirección de envío *</label>
                <input type="text" id="chkDireccion" placeholder="Calle 123 # 45-67, Bogotá" value="<?= val($usuario, 'direccion') ?>" required style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:14px;margin-top:6px">
            </div>
            <div class="form-group" style="margin-top:16px">
                <label>Teléfono</label>
                <input type="tel" id="chkTelefono" placeholder="+57 300 000 0000" value="<?= val($usuario, 'telefono') ?>" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:14px;margin-top:6px">
            </div>
            <div class="form-group" style="margin-top:16px">
                <label>Ciudad</label>
                <select style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:14px;margin-top:6px">
                    <option>Bogotá</option><option>Medellín</option><option>Cali</option><option>Barranquilla</option><option>Otras</option>
                </select>
            </div>
            <div class="form-group" style="margin-top:20px">
                <label style="display:block;margin-bottom:12px">Método de pago *</label>
                <div class="metodos-radio">
                    <label class="metodo-radio-item"><input type="radio" name="pago" value="tarjeta" checked> <span class="metodo-radio-label"><i class="fa-solid fa-credit-card"></i> Tarjeta crédito/débito</span></label>
                    <label class="metodo-radio-item"><input type="radio" name="pago" value="pse"> <span class="metodo-radio-label"><i class="fa-solid fa-building-columns"></i> PSE</span></label>
                    <label class="metodo-radio-item"><input type="radio" name="pago" value="contraentrega"> <span class="metodo-radio-label"><i class="fa-solid fa-handshake"></i> Contraentrega</span></label>
                </div>
            </div>
            <button type="button" id="confirmarPedidoBtn" class="btn-confirmar" style="margin-top:24px"><i class="fa-solid fa-lock"></i> Confirmar pedido</button>
        </form>
    </div>
    <div class="carrito-resumen" id="checkoutResumen">
        <h2>Resumen del pedido</h2>
        <div id="checkoutProductos" style="margin-bottom:16px;border-bottom:1px solid #e2e8f0;padding-bottom:16px"></div>
        <p><span>Subtotal:</span> <span id="checkoutSubtotal">$0</span></p>
        <p><span>Envío:</span> <span>$10.000</span></p>
        <p class="resumen-total"><strong><span>Total:</span> <span id="checkoutTotal">$0</span></strong></p>
    </div>
</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-col"><h2>SPORTSWARE</h2><p>Tu tienda deportiva de confianza.</p><div class="social"><i class="fa-brands fa-facebook"></i><i class="fa-brands fa-instagram"></i><i class="fa-brands fa-x-twitter"></i></div></div>
        <div class="footer-col"><h3>Enlaces Rápidos</h3><a href="<?= BASE_URL ?>html/home.php">Inicio</a><a href="<?= BASE_URL ?>html/productos.php">Categorías</a><a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a><a href="<?= BASE_URL ?>html/carrito.php">Carrito</a></div>
        <div class="footer-col"><h3>Soporte</h3><p>+57 300 123 4567</p><p>soporte@sportsware.com</p><p>Bogotá, Colombia</p></div>
        <div class="footer-col autor"><h3>Acerca del Creador</h3><p><strong>Aun por decidir</strong></p><p>Desarrollador web</p><a href="#">Contáctame</a></div>
    </div>
    <hr>
    <div class="footer-bottom"><p>© 2026 SPORTSWARE</p><div class="footer-bottom_links"><a href="#">Términos</a><a href="#">Privacidad</a><a href="#">Devoluciones</a></div></div>
</footer>

<script src="../js/productos-data.js"></script>
<script src="../js/carrito.js"></script>
<script src="../js/buscador.js"></script>
<script src="../js/main.js"></script>
<script>window.usuarioLogueado = true;</script>
<script>window.baseUrl = '<?= BASE_URL ?>';</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const carrito = typeof obtenerCarrito === 'function' ? obtenerCarrito() : [];
    const totales = typeof calcularTotales === 'function' ? calcularTotales() : { subtotal: 0, envio: 10000, total: 10000 };
    const divProductos = document.getElementById('checkoutProductos');
    if (divProductos) {
        divProductos.innerHTML = carrito.length === 0 ? '<p style="color:#94a3b8;font-size:14px">No hay productos en el carrito.</p>' : carrito.map(item => `<div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px"><span>${item.nombre}${item.talla ? ' ('+item.talla+')' : ''} ×${item.cantidad}</span><span style="font-weight:600">$${(item.precio * item.cantidad).toLocaleString('es-CO')}</span></div>`).join('');
    }
    document.getElementById('checkoutSubtotal').textContent = '$' + totales.subtotal.toLocaleString('es-CO');
    document.getElementById('checkoutTotal').textContent = '$' + totales.total.toLocaleString('es-CO');
    const btn = document.getElementById('confirmarPedidoBtn');
    if (btn) {
        btn.addEventListener('click', async function () {
            const nombre = document.getElementById('chkNombre')?.value.trim();
            const dir = document.getElementById('chkDireccion')?.value.trim();
            const metodo = document.querySelector('input[name="pago"]:checked')?.value;
            if (!nombre || !dir) { mostrarToast('Por favor completa los campos obligatorios (*)', 'error'); return; }
            if (!carrito || carrito.length === 0) { mostrarToast('No hay productos en tu carrito.', 'error'); return; }
            btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando…';
            try {
                const res = await fetch(window.baseUrl + 'php/procesar_pedido.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ carrito: carrito, direccion_envio: dir, metodo_pago: metodo, total: totales.total })
                });
                const data = await res.json();
                if (data.success) {
                    localStorage.removeItem('carrito_sportsware');
                    if (typeof actualizarContadorCarrito === 'function') actualizarContadorCarrito();
                    mostrarToast('¡Pedido confirmado! Redirigiendo… 🎉');
                    setTimeout(() => { window.location.href = window.baseUrl + 'html/pedido-confirmado.php?id=' + data.pedido_id; }, 1400);
                } else {
                    mostrarToast(data.mensaje || 'Error al procesar el pedido.', 'error');
                    btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-lock"></i> Confirmar pedido';
                }
            } catch (err) {
                mostrarToast('Error de conexión. Verifica tu red e intenta de nuevo.', 'error');
                btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-lock"></i> Confirmar pedido';
            }
        });
    }
});
</script>
</body>
</html>