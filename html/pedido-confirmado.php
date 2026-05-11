<?php
require_once __DIR__ . '/../php/verificar_sesion.php';
require_once __DIR__ . '/../php/config.php';

$uid = (int) $_SESSION['usuario_id'];
$pedidoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pedidoId || $pedidoId < 1) { header('Location: ' . BASE_URL . 'html/perfil.php'); exit; }

$pedido = [];
try {
    $stmt = $pdo->prepare('SELECT id, fecha, total, estado, direccion_envio, metodo_pago FROM pedidos WHERE id = :pid AND usuario_id = :uid LIMIT 1');
    $stmt->execute([':pid' => $pedidoId, ':uid' => $uid]);
    $pedido = $stmt->fetch() ?: [];
} catch (PDOException $e) { error_log('[SPORTSWARE][pedido-confirmado] cabecera: ' . $e->getMessage()); }
if (empty($pedido)) { header('Location: ' . BASE_URL . 'html/perfil.php'); exit; }

$detalles = [];
try {
    $stmtDet = $pdo->prepare('SELECT dp.cantidad, dp.precio_unitario, dp.talla, p.id AS producto_id, p.nombre, p.imagen, p.categoria, p.marca FROM detalles_pedido dp JOIN productos p ON p.id = dp.producto_id WHERE dp.pedido_id = :pid ORDER BY dp.id ASC');
    $stmtDet->execute([':pid' => $pedidoId]);
    $detalles = $stmtDet->fetchAll();
} catch (PDOException $e) { error_log('[SPORTSWARE][pedido-confirmado] detalles: ' . $e->getMessage()); }

$metodosLabel = ['tarjeta' => 'Tarjeta crédito/débito', 'pse' => 'PSE', 'contraentrega' => 'Contraentrega'];
$estadosConfig = ['pendiente' => ['class' => 'procesando', 'label' => 'Pendiente', 'icon' => 'fa-clock'], 'pagado' => ['class' => 'procesando', 'label' => 'Pagado', 'icon' => 'fa-circle-check'], 'enviado' => ['class' => 'enviado', 'label' => 'En camino', 'icon' => 'fa-truck'], 'entregado' => ['class' => 'entregado', 'label' => 'Entregado', 'icon' => 'fa-house-circle-check'], 'cancelado' => ['class' => 'cancelado', 'label' => 'Cancelado', 'icon' => 'fa-ban']];
$estadoActual = $estadosConfig[$pedido['estado']] ?? $estadosConfig['pendiente'];
$metodoPagoLabel = $metodosLabel[$pedido['metodo_pago'] ?? ''] ?? ucfirst($pedido['metodo_pago'] ?? '—');
$fechaFormato = (new DateTime($pedido['fecha']))->format('d \d\e F \d\e Y \a \l\a\s H:i');
$numeroPedido = str_pad($pedido['id'], 3, '0', STR_PAD_LEFT);
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$subtotal = array_reduce($detalles, fn($carry, $det) => $carry + (float)$det['precio_unitario'] * (int)$det['cantidad'], 0.0);
$envio = (float)$pedido['total'] - $subtotal;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPORTSWARE | Pedido #<?= $numeroPedido ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/carrito.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;600;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .confirmacion-wrapper { max-width: 680px; margin: 36px auto 60px; padding: 0 20px; }
        .confirmacion-header { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 36px 28px 28px; text-align: center; margin-bottom: 20px; }
        .check-circle { width: 76px; height: 76px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; animation: pop-in .45s cubic-bezier(.175,.885,.32,1.275) both; }
        .check-circle i { font-size: 34px; color: #16a34a; }
        @keyframes pop-in { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .confirmacion-header h1 { font-size: 24px; color: #1e293b; margin-bottom: 6px; }
        .confirmacion-header .subtitulo { color: #64748b; font-size: 14px; line-height: 1.6; }
        .estado-badge-grande { display: inline-flex; align-items: center; gap: 7px; margin-top: 16px; padding: 7px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; }
        .seccion-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.08); margin-bottom: 20px; overflow: hidden; }
        .seccion-card-header { padding: 15px 22px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .seccion-card-header i { color: #2563eb; }
        .seccion-card-body { padding: 0 22px; }
        .dato-fila { display: flex; justify-content: space-between; align-items: flex-start; padding: 13px 0; border-bottom: 1px solid #f8fafc; font-size: 14px; gap: 16px; }
        .dato-fila:last-child { border-bottom: none; }
        .dato-fila .dato-key { color: #64748b; flex-shrink: 0; display: flex; align-items: center; gap: 7px; }
        .dato-fila .dato-val { font-weight: 600; color: #1e293b; text-align: right; }
        .producto-linea { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f8fafc; }
        .producto-linea:last-child { border-bottom: none; }
        .producto-linea-img { width: 58px; height: 58px; border-radius: 10px; object-fit: cover; background: #f1f5f9; flex-shrink: 0; border: 1px solid #e2e8f0; cursor: pointer; }
        .producto-linea-info { flex: 1; min-width: 0; }
        .producto-linea-nombre { font-size: 14px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .producto-linea-subtotal { font-size: 14px; font-weight: 800; color: #2563eb; white-space: nowrap; flex-shrink: 0; }
        .totales-bloque { padding: 4px 0 8px; }
        .total-fila { display: flex; justify-content: space-between; padding: 9px 0; font-size: 14px; color: #64748b; border-bottom: 1px solid #f8fafc; }
        .total-fila.principal { border-top: 2px solid #e2e8f0; border-bottom: none; margin-top: 6px; padding-top: 14px; font-size: 17px; font-weight: 800; color: #1e293b; }
        .total-fila.principal span:last-child { color: #2563eb; font-size: 20px; }
        .acciones-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .btn-accion { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px 18px; border-radius: 30px; font-family: inherit; font-size: 14px; font-weight: 700; text-decoration: none; transition: all .2s; border: none; cursor: pointer; }
        .btn-accion-oscuro { background: #1e293b; color: #fff; }
        .btn-accion-oscuro:hover { background: #0f172a; }
        .btn-accion-azul { background: #2563eb; color: #fff; }
        .btn-accion-azul:hover { background: #1d4ed8; }
        @media (max-width: 520px) { .confirmacion-wrapper { margin-top: 20px; } .confirmacion-header { padding: 28px 18px 22px; } .acciones-grid { grid-template-columns: 1fr; } .producto-linea-img { width: 44px; height: 44px; } }
    </style>
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
        <a href="<?= BASE_URL ?>html/carrito.php" style="color:white;position:relative;"><i class="fa-solid fa-cart-shopping"></i><span id="contadorCarrito" class="cart-badge">0</span></a>
        <a href="<?= BASE_URL ?>html/perfil.php" style="color:white;display:flex;align-items:center;gap:6px;text-decoration:none;"><i class="fa-solid fa-circle-user"></i><span style="font-size:13px;font-weight:600;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $nombreUsuario ?></span></a>
    </div>
</header>

<div class="confirmacion-wrapper">
    <div class="confirmacion-header">
        <div class="check-circle"><i class="fa-solid fa-check"></i></div>
        <h1>¡Pedido confirmado!</h1>
        <p class="subtitulo">Gracias, <strong><?= $nombreUsuario ?></strong>. Tu pedido <strong>#<?= $numeroPedido ?></strong> fue recibido correctamente.<br>Te notificaremos cuando sea despachado.</p>
        <div class="estado-badge-grande pedido-estado <?= $estadoActual['class'] ?>"><i class="fa-solid <?= $estadoActual['icon'] ?>"></i> <?= $estadoActual['label'] ?></div>
    </div>
    <div class="seccion-card">
        <div class="seccion-card-header"><i class="fa-solid fa-receipt"></i> Información del pedido</div>
        <div class="seccion-card-body">
            <div class="dato-fila"><span class="dato-key"><i class="fa-solid fa-hashtag"></i> Número de pedido</span><span class="dato-val">#<?= $numeroPedido ?></span></div>
            <div class="dato-fila"><span class="dato-key"><i class="fa-regular fa-calendar"></i> Fecha</span><span class="dato-val"><?= htmlspecialchars($fechaFormato, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="dato-fila"><span class="dato-key"><i class="fa-solid fa-location-dot"></i> Dirección de envío</span><span class="dato-val"><?= htmlspecialchars($pedido['direccion_envio'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="dato-fila"><span class="dato-key"><i class="fa-solid fa-credit-card"></i> Método de pago</span><span class="dato-val"><?= htmlspecialchars($metodoPagoLabel, ENT_QUOTES, 'UTF-8') ?></span></div>
        </div>
    </div>
    <?php if (!empty($detalles)): ?>
    <div class="seccion-card">
        <div class="seccion-card-header"><i class="fa-solid fa-box-open"></i> Productos (<?= count($detalles) ?>)</div>
        <div class="seccion-card-body">
            <?php foreach ($detalles as $det): $lineaTotal = (float)$det['precio_unitario'] * (int)$det['cantidad']; ?>
            <div class="producto-linea">
                <img class="producto-linea-img" src="<?= htmlspecialchars($det['imagen'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($det['nombre'], ENT_QUOTES, 'UTF-8') ?>" onclick="location.href='<?= BASE_URL ?>html/producto-detalle.php?id=<?= urlencode($det['producto_id']) ?>'" onerror="this.onerror=null;this.src='https://placehold.co/58x58?text=SW'">
                <div class="producto-linea-info">
                    <div class="producto-linea-nombre"><?= htmlspecialchars($det['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="producto-linea-meta"><?= htmlspecialchars(ucfirst($det['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($det['marca'] ?? '', ENT_QUOTES, 'UTF-8') ?><?= !empty($det['talla']) ? ' · Talla '.htmlspecialchars($det['talla'], ENT_QUOTES, 'UTF-8') : '' ?> · ×<?= (int)$det['cantidad'] ?></div>
                </div>
                <span class="producto-linea-subtotal">$<?= number_format($lineaTotal, 0, ',', '.') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="seccion-card">
        <div class="seccion-card-header"><i class="fa-solid fa-coins"></i> Resumen de pago</div>
        <div class="seccion-card-body">
            <div class="totales-bloque">
                <div class="total-fila"><span>Subtotal</span><span>$<?= number_format($subtotal, 0, ',', '.') ?></span></div>
                <div class="total-fila"><span>Envío</span><span>$<?= number_format($envio, 0, ',', '.') ?></span></div>
                <div class="total-fila principal"><span>Total</span><span>$<?= number_format((float)$pedido['total'], 0, ',', '.') ?></span></div>
            </div>
        </div>
    </div>
    <div class="acciones-grid">
        <a href="<?= BASE_URL ?>html/perfil.php" class="btn-accion btn-accion-oscuro"><i class="fa-solid fa-clock-rotate-left"></i> Mis pedidos</a>
        <a href="<?= BASE_URL ?>html/productos.php" class="btn-accion btn-accion-azul"><i class="fa-solid fa-bag-shopping"></i> Seguir comprando</a>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-col"><h2>SPORTSWARE</h2><p>Tu tienda deportiva de confianza.</p><div class="social"><i class="fa-brands fa-facebook"></i><i class="fa-brands fa-instagram"></i><i class="fa-brands fa-x-twitter"></i></div></div>
        <div class="footer-col"><h3>Enlaces Rápidos</h3><a href="<?= BASE_URL ?>html/home.php">Inicio</a><a href="<?= BASE_URL ?>html/productos.php">Categorías</a><a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a><a href="<?= BASE_URL ?>html/carrito.php">Carrito</a></div>
        <div class="footer-col"><h3>Soporte</h3><p>+57 300 123 4567</p><p>soporte@sportsware.com</p><p>Bogotá, Colombia</p></div>
        <div class="footer-col autor"><h3>Acerca del Creador</h3><p><strong>Aun por decidir</strong></p><p>Desarrollador web</p><a href="#">Contáctame</a></div>
    </div>
    <hr><div class="footer-bottom"><p>© 2026 SPORTSWARE</p><div class="footer-bottom_links"><a href="#">Términos</a><a href="#">Privacidad</a><a href="#">Devoluciones</a></div></div>
</footer>

<script src="../js/productos-data.js"></script>
<script src="../js/carrito.js"></script>
<script src="../js/buscador.js"></script>
<script src="../js/main.js"></script>
<script>window.usuarioLogueado = true;</script>
<script>window.baseUrl = '<?= BASE_URL ?>';</script>
</body>
</html>