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
    <title>SPORTSWARE | Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/productos.css">
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
        <?php if ($usuarioLogueado): ?>
            <a href="<?= BASE_URL ?>html/perfil.php" style="color:white;display:flex;align-items:center;gap:6px;text-decoration:none;">
                <i class="fa-solid fa-circle-user"></i>
                <span style="font-size:13px;font-weight:600;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= $nombreUsuario ?>
                </span>
            </a>
        <?php else: ?>
            <a href="#" id="userIcon" style="color:white;cursor:pointer;">
                <i class="fa-solid fa-user"></i>
            </a>
        <?php endif; ?>
    </div>
</header>

<main class="container" style="margin-top: 30px;">
    <h1>Todos los productos</h1>
    <div class="productos-layout">
        <aside class="filtros">
            <h3>Categorías</h3>
            <ul>
                <li><label><input type="checkbox" value="camisetas"> Camisetas</label></li>
                <li><label><input type="checkbox" value="leggings"> Leggings</label></li>
                <li><label><input type="checkbox" value="tenis"> Tenis / Calzado</label></li>
                <li><label><input type="checkbox" value="accesorios"> Accesorios</label></li>
                <li><label><input type="checkbox" value="shorts"> Shorts</label></li>
                <li><label><input type="checkbox" value="suplementos"> Suplementos</label></li>
            </ul>
            <h3>Género</h3>
            <ul>
                <li><label><input type="radio" name="genero" value="hombre"> Hombre</label></li>
                <li><label><input type="radio" name="genero" value="mujer"> Mujer</label></li>
                <li><label><input type="radio" name="genero" value="neutro"> Neutro</label></li>
                <li><label><input type="radio" name="genero" value="todos" checked> Todos</label></li>
            </ul>
            <h3>Ofertas</h3>
            <ul>
                <li><label><input type="checkbox" id="filtroOfertas"> Solo productos en oferta</label></li>
            </ul>
        </aside>
        <div class="productos-grid" id="productosGrid">
            <p style="color:#64748b;grid-column:1/-1;padding:40px 0">Cargando productos...</p>
        </div>
    </div>
</main>

<?php if (!$usuarioLogueado): ?>
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Iniciar Sesión</h2>
        <p class="subtitle">Bienvenido de nuevo a SPORTSWARE</p>
        <form id="loginForm" onsubmit="return false;">
            <label>Correo electrónico</label>
            <input type="email" id="loginEmail" placeholder="tu@email.com" required>
            <label>Contraseña</label>
            <input type="password" id="loginPassword" placeholder="••••••" required>
            <button type="button" class="btn-login" id="loginBtn">Iniciar Sesión</button>
        </form>
        <p class="extra">¿No tienes cuenta? <a href="<?= BASE_URL ?>html/registro.php">Regístrate aquí</a></p>
        <p class="extra"><a href="#">¿Olvidaste tu contraseña?</a></p>
    </div>
</div>
<?php endif; ?>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h2>SPORTSWARE</h2>
            <p>Tu tienda deportiva de confianza. Ofrecemos la mejor calidad en ropa y accesorios deportivos.</p>
            <div class="social">
                <i class="fa-brands fa-facebook"></i>
                <i class="fa-brands fa-instagram"></i>
                <i class="fa-brands fa-x-twitter"></i>
            </div>
        </div>
        <div class="footer-col">
            <h3>Enlaces Rápidos</h3>
            <a href="<?= BASE_URL ?>html/home.php">Inicio</a>
            <a href="<?= BASE_URL ?>html/productos.php">Categorías</a>
            <a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a>
            <a href="<?= BASE_URL ?>html/carrito.php">Carrito</a>
        </div>
        <div class="footer-col">
            <h3>Soporte</h3>
            <p>+57 300 123 4567</p>
            <p>soporte@sportsware.com</p>
            <p>Bogotá, Colombia</p>
        </div>
        <div class="footer-col autor">
            <h3>Acerca del Creador</h3>
            <p><strong>Aun por decidir</strong></p>
            <p>Desarrollador web</p>
            <a href="#">Contáctame</a>
        </div>
    </div>
    <hr>
    <div class="footer-bottom">
        <p>© 2026 SPORTSWARE</p>
        <div class="footer-bottom_links">
            <a href="#">Términos</a>
            <a href="#">Privacidad</a>
            <a href="#">Devoluciones</a>
        </div>
    </div>
</footer>

<script src="../js/productos-data.js"></script>
<script src="../js/carrito.js"></script>
<script src="../js/buscador.js"></script>
<script src="../js/productos.js"></script>
<script src="../js/main.js"></script>
<script>window.usuarioLogueado = <?= $usuarioLogueado ? 'true' : 'false' ?>;</script>
<script>window.baseUrl = '<?= BASE_URL ?>';</script>
</body>
</html>