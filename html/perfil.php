<?php
require_once __DIR__ . '/../php/verificar_sesion.php';
require_once __DIR__ . '/../php/config.php';

$uid = (int) $_SESSION['usuario_id'];
$usuario = [];
try {
    $stmt = $pdo->prepare('SELECT id, nombre, email, telefono, direccion, fecha_registro FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $uid]);
    $usuario = $stmt->fetch() ?: [];
} catch (PDOException $e) { error_log('[SPORTSWARE][perfil] usuario: ' . $e->getMessage()); }

$pedidos = [];
try {
    $stmtP = $pdo->prepare('SELECT p.id, p.fecha, p.total, p.estado, p.direccion_envio, p.metodo_pago, COUNT(dp.id) AS num_items FROM pedidos p LEFT JOIN detalles_pedido dp ON dp.pedido_id = p.id WHERE p.usuario_id = :uid GROUP BY p.id ORDER BY p.fecha DESC');
    $stmtP->execute([':uid' => $uid]);
    $pedidos = $stmtP->fetchAll();
} catch (PDOException $e) { error_log('[SPORTSWARE][perfil] pedidos: ' . $e->getMessage()); }

$miniaturasMap = [];
if (!empty($pedidos)) {
    $pedidoIds = array_column($pedidos, 'id');
    $inClause = implode(',', array_fill(0, count($pedidoIds), '?'));
    try {
        $stmtMini = $pdo->prepare("SELECT dp.pedido_id, p.nombre, p.imagen FROM (SELECT pedido_id, producto_id, ROW_NUMBER() OVER (PARTITION BY pedido_id ORDER BY id ASC) AS rn FROM detalles_pedido WHERE pedido_id IN ($inClause)) dp JOIN productos p ON p.id = dp.producto_id WHERE dp.rn <= 3");
        $stmtMini->execute($pedidoIds);
        foreach ($stmtMini->fetchAll() as $row) { $miniaturasMap[(int)$row['pedido_id']][] = $row; }
    } catch (PDOException $e) { error_log('[SPORTSWARE][perfil] miniaturas: ' . $e->getMessage()); }
}

function campo(array $datos, string $clave, string $defecto = '—'): string { $valor = trim($datos[$clave] ?? ''); return htmlspecialchars($valor !== '' ? $valor : $defecto, ENT_QUOTES, 'UTF-8'); }
function estadoBadge(string $estado): string { $mapa = ['pendiente'=>['class'=>'procesando','label'=>'Pendiente'],'pagado'=>['class'=>'procesando','label'=>'Pagado'],'enviado'=>['class'=>'enviado','label'=>'En camino'],'entregado'=>['class'=>'entregado','label'=>'Entregado'],'cancelado'=>['class'=>'cancelado','label'=>'Cancelado']]; $info = $mapa[$estado] ?? ['class'=>'procesando','label'=>ucfirst($estado)]; return sprintf('<span class="pedido-estado %s">%s</span>', htmlspecialchars($info['class'], ENT_QUOTES, 'UTF-8'), htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8')); }
$metodosLabel = ['tarjeta'=>'Tarjeta','pse'=>'PSE','contraentrega'=>'Contraentrega'];
$nombreUsuario = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$inicialesAvatar = mb_strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1));
$flashError = sesion_flash('flash_error');
$flashSuccess = sesion_flash('flash_success');
$totalPedidos = count($pedidos);
$totalGastado = array_sum(array_column($pedidos, 'total'));
$pedidosActivos = count(array_filter($pedidos, fn($p) => in_array($p['estado'], ['pendiente','pagado','enviado'])));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPORTSWARE | Mi Perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/formularios.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;600;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .perfil-hero { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 40px 20px 60px; text-align: center; }
        .perfil-avatar-circle { width: 80px; height: 80px; border-radius: 50%; background: #2563eb; color: #fff; font-size: 32px; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; border: 3px solid rgba(255,255,255,.15); }
        .perfil-hero h2 { color: #fff; font-size: 22px; margin-bottom: 4px; }
        .perfil-hero p { color: #94a3b8; font-size: 14px; }
        .perfil-stats { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; max-width: 600px; margin: 24px auto 0; }
        .stat-card { background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1); border-radius: 12px; padding: 14px 22px; text-align: center; flex: 1; min-width: 120px; }
        .stat-card .stat-num { font-size: 22px; font-weight: 800; color: #fff; display: block; }
        .stat-card .stat-label { font-size: 12px; color: #94a3b8; }
        .perfil-body { max-width: 740px; margin: -24px auto 60px; padding: 0 20px; position: relative; z-index: 1; }
        .perfil-section { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.08); margin-bottom: 24px; overflow: hidden; }
        .perfil-section-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; }
        .perfil-section-header h2 { font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .perfil-section-header h2 i { color: #2563eb; }
        .perfil-section-body { padding: 20px 24px; }
        .datos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .dato-item .dato-label { font-size: 11px; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; font-weight: 600; margin-bottom: 4px; }
        .dato-item .dato-valor { font-size: 14px; font-weight: 600; color: #1e293b; }
        .pedido-card { border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 14px; overflow: hidden; }
        .pedido-card:last-child { margin-bottom: 0; }
        .pedido-card-header { display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
        .pedido-card-num { font-size: 14px; font-weight: 700; color: #1e293b; }
        .pedido-card-fecha { font-size: 12px; color: #94a3b8; }
        .pedido-card-header .spacer { flex: 1; }
        .pedido-card-body { display: flex; align-items: center; gap: 14px; padding: 14px 16px; flex-wrap: wrap; }
        .pedido-miniaturas { display: flex; gap: 6px; flex-shrink: 0; }
        .pedido-miniatura { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; background: #f1f5f9; }
        .pedido-miniatura-mas { width: 48px; height: 48px; border-radius: 8px; border: 1px dashed #cbd5e1; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #64748b; }
        .pedido-card-info { flex: 1; min-width: 0; }
        .pedido-card-meta { font-size: 12px; color: #64748b; display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 4px; }
        .pedido-card-meta span { display: flex; align-items: center; gap: 4px; }
        .pedido-card-meta i { color: #94a3b8; }
        .pedido-card-total { font-size: 16px; font-weight: 800; color: #2563eb; }
        .btn-ver-detalle { background: none; border: 1px solid #e2e8f0; border-radius: 20px; padding: 7px 16px; font-family: inherit; font-size: 13px; font-weight: 600; color: #2563eb; cursor: pointer; transition: all .2s; white-space: nowrap; flex-shrink: 0; text-decoration: none; display: inline-block; }
        .btn-ver-detalle:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
        .sin-pedidos-empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .sin-pedidos-empty i { font-size: 48px; display: block; margin-bottom: 14px; opacity: .3; }
        .sin-pedidos-empty a { display: inline-block; margin-top: 14px; color: #2563eb; font-weight: 700; text-decoration: none; }
        .btn-cerrar-sesion { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px; background: none; border: 1px solid #e2e8f0; border-radius: 30px; font-family: inherit; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; transition: all .2s; }
        .btn-cerrar-sesion:hover { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        @media (max-width: 580px) { .datos-grid { grid-template-columns: 1fr; } .perfil-stats { gap: 10px; } .pedido-card-body { gap: 10px; } .pedido-miniaturas { display: none; } }
    </style>
</head>
<body>

<header class="navbar">
    <a href="<?= BASE_URL ?>html/home.php" class="logo">SPORTSWARE</a>
    <nav class="menu"><a href="<?= BASE_URL ?>html/home.php">Inicio</a><a href="<?= BASE_URL ?>html/productos.php">Categorías</a><a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a></nav>
    <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" placeholder="Buscar productos..." id="searchInput"><div class="resultados" id="searchResults"></div></div>
    <div class="icons"><a href="<?= BASE_URL ?>html/carrito.php" style="color:white;position:relative;"><i class="fa-solid fa-cart-shopping"></i><span id="contadorCarrito" class="cart-badge">0</span></a><a href="<?= BASE_URL ?>html/perfil.php" style="color:white;display:flex;align-items:center;gap:6px;text-decoration:none;"><i class="fa-solid fa-circle-user"></i><span style="font-size:13px;font-weight:600;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $nombreUsuario ?></span></a></div>
</header>

<?php if ($flashError): ?><div style="background:#fef2f2;color:#b91c1c;padding:12px 24px;text-align:center;font-size:14px;border-bottom:1px solid #fecaca;"><i class="fa-solid fa-circle-exclamation"></i> <?= $flashError ?></div><?php endif; ?>
<?php if ($flashSuccess): ?><div style="background:#f0fdf4;color:#15803d;padding:12px 24px;text-align:center;font-size:14px;border-bottom:1px solid #bbf7d0;"><i class="fa-solid fa-circle-check"></i> <?= $flashSuccess ?></div><?php endif; ?>

<div class="perfil-hero">
    <div class="perfil-avatar-circle"><?= $inicialesAvatar ?></div>
    <h2><?= $nombreUsuario ?></h2>
    <p><?= campo($usuario, 'email') ?></p>
    <div class="perfil-stats">
        <div class="stat-card"><span class="stat-num"><?= $totalPedidos ?></span><span class="stat-label">Pedidos</span></div>
        <div class="stat-card"><span class="stat-num">$<?= number_format($totalGastado, 0, ',', '.') ?></span><span class="stat-label">Total gastado</span></div>
        <div class="stat-card"><span class="stat-num"><?= $pedidosActivos ?></span><span class="stat-label">En proceso</span></div>
    </div>
</div>

<div class="perfil-body">
    <div class="perfil-section">
        <div class="perfil-section-header"><h2><i class="fa-solid fa-id-card"></i> Datos personales</h2></div>
        <div class="perfil-section-body">
            <div class="datos-grid">
                <div class="dato-item"><div class="dato-label">Nombre completo</div><div class="dato-valor"><?= campo($usuario, 'nombre') ?></div></div>
                <div class="dato-item"><div class="dato-label">Correo electrónico</div><div class="dato-valor"><?= campo($usuario, 'email') ?></div></div>
                <div class="dato-item"><div class="dato-label">Teléfono</div><div class="dato-valor"><?= campo($usuario, 'telefono', 'No registrado') ?></div></div>
                <div class="dato-item"><div class="dato-label">Dirección</div><div class="dato-valor"><?= campo($usuario, 'direccion', 'No registrada') ?></div></div>
                <div class="dato-item"><div class="dato-label">Miembro desde</div><div class="dato-valor"><?= !empty($usuario['fecha_registro']) ? (new DateTime($usuario['fecha_registro']))->format('d/m/Y') : '—' ?></div></div>
            </div>
        </div>
    </div>

    <div class="perfil-section">
        <div class="perfil-section-header"><h2><i class="fa-solid fa-bag-shopping"></i> Historial de pedidos</h2><?php if ($totalPedidos > 0): ?><span style="font-size:13px;color:#64748b"><?= $totalPedidos ?> <?= $totalPedidos === 1 ? 'pedido' : 'pedidos' ?></span><?php endif; ?></div>
        <div class="perfil-section-body" style="padding:16px 20px">
            <?php if (empty($pedidos)): ?>
                <div class="sin-pedidos-empty"><i class="fa-solid fa-box-open"></i><p>Aún no has realizado ningún pedido.</p><a href="<?= BASE_URL ?>html/productos.php">Explorar el catálogo →</a></div>
            <?php else: ?>
                <?php foreach ($pedidos as $pedido): $pid = (int)$pedido['id']; $minis = $miniaturasMap[$pid] ?? []; $numItems = (int)$pedido['num_items']; $metLabel = $metodosLabel[$pedido['metodo_pago'] ?? ''] ?? ucfirst($pedido['metodo_pago'] ?? '—'); $fecha = (new DateTime($pedido['fecha']))->format('d/m/Y'); ?>
                <div class="pedido-card">
                    <div class="pedido-card-header"><span class="pedido-card-num">Pedido #<?= str_pad($pid, 3, '0', STR_PAD_LEFT) ?></span><span class="pedido-card-fecha"><?= $fecha ?></span><span class="spacer"></span><?= estadoBadge($pedido['estado']) ?></div>
                    <div class="pedido-card-body">
                        <?php if (!empty($minis)): ?><div class="pedido-miniaturas"><?php foreach (array_slice($minis, 0, 3) as $mini): ?><img class="pedido-miniatura" src="<?= htmlspecialchars($mini['imagen'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($mini['nombre'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.onerror=null;this.src='https://placehold.co/48x48?text=SW'"><?php endforeach; ?><?php if ($numItems > 3): ?><div class="pedido-miniatura-mas">+<?= $numItems - 3 ?></div><?php endif; ?></div><?php endif; ?>
                        <div class="pedido-card-info">
                            <div class="pedido-card-meta"><span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars(mb_strimwidth($pedido['direccion_envio'], 0, 35, '…'), ENT_QUOTES, 'UTF-8') ?></span><span><i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars($metLabel, ENT_QUOTES, 'UTF-8') ?></span><span><i class="fa-solid fa-box"></i> <?= $numItems ?> ítem<?= $numItems !== 1 ? 's' : '' ?></span></div>
                            <div class="pedido-card-total">$<?= number_format((float)$pedido['total'], 0, ',', '.') ?></div>
                        </div>
                        <a href="<?= BASE_URL ?>html/pedido-confirmado.php?id=<?= $pid ?>" class="btn-ver-detalle">Ver detalle</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="perfil-section" style="padding:0"><div class="perfil-section-body"><button class="btn-cerrar-sesion" onclick="cerrarSesion()"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</button></div></div>
</div>

<footer class="footer">
    <div class="footer-container"><div class="footer-col"><h2>SPORTSWARE</h2><p>Tu tienda deportiva de confianza.</p><div class="social"><i class="fa-brands fa-facebook"></i><i class="fa-brands fa-instagram"></i><i class="fa-brands fa-x-twitter"></i></div></div><div class="footer-col"><h3>Enlaces Rápidos</h3><a href="<?= BASE_URL ?>html/home.php">Inicio</a><a href="<?= BASE_URL ?>html/productos.php">Categorías</a><a href="<?= BASE_URL ?>html/productos.php?filtro=oferta">Ofertas</a><a href="<?= BASE_URL ?>html/carrito.php">Carrito</a></div><div class="footer-col"><h3>Soporte</h3><p>+57 300 123 4567</p><p>soporte@sportsware.com</p><p>Bogotá, Colombia</p></div><div class="footer-col autor"><h3>Acerca del Creador</h3><p><strong>Aun por decidir</strong></p><p>Desarrollador web</p><a href="#">Contáctame</a></div></div>
    <hr><div class="footer-bottom"><p>© 2026 SPORTSWARE</p><div class="footer-bottom_links"><a href="#">Términos</a><a href="#">Privacidad</a><a href="#">Devoluciones</a></div></div>
</footer>

<script src="../js/productos-data.js"></script>
<script src="../js/carrito.js"></script>
<script src="../js/buscador.js"></script>
<script src="../js/main.js"></script>
<script>window.usuarioLogueado = true;</script>
<script>window.baseUrl = '<?= BASE_URL ?>';</script>
<script>
function cerrarSesion() {
    fetch(window.baseUrl + 'php/logout.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(data => {
        if (typeof mostrarToast === 'function') mostrarToast('Sesión cerrada. ¡Hasta pronto!');
        localStorage.removeItem('carrito_sportsware');
        setTimeout(() => { window.location.href = data.redirect || window.baseUrl + 'html/home.php'; }, 1500);
    })
    .catch(() => { window.location.href = window.baseUrl + 'php/logout.php'; });
}
</script>
</body>
</html>