<?php
session_start();
require_once __DIR__ . '/../php/config.php';
$usuarioLogueado = isset($_SESSION['usuario_id']);
$nombreUsuario   = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPORTSWARE | Detalle Producto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/detalle.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;600;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="navbar">
    <a href="<?= BASE_URL ?>html/home.php" class="logo">SPORTSWARE</a>
    <nav class="menu"><a href="<?= BASE_URL ?>html/home.php">Inicio</a><a href="<?= BASE_URL ?>html/productos.php">Categorías</a><a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a></nav>
    <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" placeholder="Buscar productos..." id="searchInput"><div class="resultados" id="searchResults"></div></div>
    <div class="icons"><a href="<?= BASE_URL ?>html/carrito.php" style="color:white;position:relative;"><i class="fa-solid fa-cart-shopping"></i><span id="contadorCarrito" class="cart-badge">0</span></a><?php if ($usuarioLogueado): ?><a href="<?= BASE_URL ?>html/perfil.php" style="color:white;display:flex;align-items:center;gap:6px;text-decoration:none;"><i class="fa-solid fa-circle-user"></i><span style="font-size:13px;font-weight:600;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $nombreUsuario ?></span></a><?php else: ?><a href="#" id="userIcon" style="color:white;cursor:pointer;"><i class="fa-solid fa-user"></i></a><?php endif; ?></div>
</header>

<nav class="breadcrumb"><a href="<?= BASE_URL ?>html/home.php">Inicio</a><span class="sep">/</span><a href="<?= BASE_URL ?>html/productos.php">Productos</a><span class="sep">/</span><span class="actual" id="breadcrumbNombre">Detalle</span></nav>

<main class="detalle-container">
    <div class="detalle-imagen"><img id="detalleImagen" src="../images/placeholders/placeholder.jpg" alt="Producto" class="img-principal" onerror="this.onerror=null;this.src='https://placehold.co/500x500?text=SPORTSWARE'"></div>
    <div class="detalle-info">
        <span class="detalle-categoria" id="detalleCategoria">Categoría</span>
        <h1 id="detalleNombre">Nombre del producto</h1>
        <p id="detalleMarca" class="detalle-marca">Marca</p>
        <p id="detallePrecio" class="detalle-precio">$0</p>
        <hr class="detalle-divider">
        <p id="detalleDescripcion" class="detalle-descripcion">Descripción del producto.</p>
        <div class="detalle-tallas"><label class="detalle-tallas-label">Selecciona tu talla</label><div class="tallas" id="tallasContainer"><span class="talla">S</span><span class="talla">M</span><span class="talla">L</span><span class="talla">XL</span></div></div>
        <div class="detalle-acciones"><button id="añadirCarritoBtn" class="btn-primary"><i class="fa-solid fa-cart-plus"></i> Añadir al carrito</button></div>
        <div class="detalle-beneficios"><div class="beneficio-item"><i class="fa-solid fa-truck"></i><span>Envío a todo Colombia desde $10.000</span></div><div class="beneficio-item"><i class="fa-solid fa-rotate-left"></i><span>Devoluciones gratuitas en 30 días</span></div><div class="beneficio-item"><i class="fa-solid fa-shield-halved"></i><span>Compra 100% segura y protegida</span></div></div>
    </div>
</main>

<?php if (!$usuarioLogueado): ?>
<div id="loginModal" class="modal"><div class="modal-content"><span class="close">&times;</span><h2>Iniciar Sesión</h2><p class="subtitle">Bienvenido de nuevo a SPORTSWARE</p><form id="loginForm" onsubmit="return false;"><label>Correo electrónico</label><input type="email" id="loginEmail" placeholder="tu@email.com" required><label>Contraseña</label><input type="password" id="loginPassword" placeholder="••••••" required><button type="button" class="btn-login" id="loginBtn">Iniciar Sesión</button></form><p class="extra">¿No tienes cuenta? <a href="<?= BASE_URL ?>html/registro.php">Regístrate aquí</a></p><p class="extra"><a href="#">¿Olvidaste tu contraseña?</a></p></div></div>
<?php endif; ?>

<footer class="footer">
    <div class="footer-container"><div class="footer-col"><h2>SPORTSWARE</h2><p>Tu tienda deportiva de confianza. Ofrecemos la mejor calidad en ropa y accesorios deportivos.</p><div class="social"><i class="fa-brands fa-facebook"></i><i class="fa-brands fa-instagram"></i><i class="fa-brands fa-x-twitter"></i></div></div><div class="footer-col"><h3>Enlaces Rápidos</h3><a href="<?= BASE_URL ?>html/home.php">Inicio</a><a href="<?= BASE_URL ?>html/productos.php">Categorías</a><a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a><a href="<?= BASE_URL ?>html/carrito.php">Carrito</a></div><div class="footer-col"><h3>Soporte</h3><p>+57 300 123 4567</p><p>soporte@sportsware.com</p><p>Bogotá, Colombia</p></div><div class="footer-col autor"><h3>Acerca del Creador</h3><p><strong>Aun por decidir</strong></p><p>Desarrollador web</p><a href="#">Contáctame</a></div></div>
    <hr><div class="footer-bottom"><p>© 2026 SPORTSWARE</p><div class="footer-bottom_links"><a href="#">Términos</a><a href="#">Privacidad</a><a href="#">Devoluciones</a></div></div>
</footer>

<script src="../js/productos-data.js"></script>
<script src="../js/carrito.js"></script>
<script src="../js/buscador.js"></script>
<script src="../js/detalle.js"></script>
<script src="../js/main.js"></script>
<script>window.usuarioLogueado = <?= $usuarioLogueado ? 'true' : 'false' ?>;</script>
<script>window.baseUrl = '<?= BASE_URL ?>';</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const nombre = document.getElementById('detalleNombre');
    const bc = document.getElementById('breadcrumbNombre');
    if (bc && nombre) { const observer = new MutationObserver(() => { bc.textContent = nombre.textContent; }); observer.observe(nombre, { childList: true, subtree: true, characterData: true }); bc.textContent = nombre.textContent; }
});
</script>
</body>
</html>