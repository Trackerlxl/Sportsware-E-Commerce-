<?php
// ============================================================
//  SPORTSWARE – php/procesar_pedido.php
//  Endpoint POST: procesa el pedido dentro de una transacción
//  atómica. Si cualquier paso falla, se hace rollback completo.
//  Responde siempre con JSON.
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// ── Guard: solo usuarios logueados ────────────────────────
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/config.php';   // $pdo

// ── Solo POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

$uid = (int) $_SESSION['usuario_id'];

// ============================================================
//  1. LEER Y VALIDAR EL CUERPO JSON
// ============================================================
$body = json_decode(file_get_contents('php://input'), true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Cuerpo de la petición inválido.']);
    exit;
}

$carrito        = $body['carrito']        ?? [];
$direccionEnvio = trim($body['direccion_envio'] ?? '');
$metodoPago     = trim($body['metodo_pago']     ?? '');
$totalFrontend  = isset($body['total']) ? (float) $body['total'] : null;

// ── Validaciones de entrada ────────────────────────────────
$errores = [];

if (!is_array($carrito) || count($carrito) === 0) {
    $errores[] = 'El carrito está vacío.';
}

if ($direccionEnvio === '') {
    $errores[] = 'La dirección de envío es obligatoria.';
}

$metodosPermitidos = ['tarjeta', 'pse', 'contraentrega'];
if (!in_array($metodoPago, $metodosPermitidos, true)) {
    $errores[] = 'Método de pago no válido.';
}

if (!empty($errores)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'mensaje' => implode(' ', $errores)]);
    exit;
}

// ============================================================
//  2. VERIFICAR PRODUCTOS EN LA BD
//     Nunca confiamos en los precios que manda el frontend.
//     Consultamos el precio real desde la tabla productos.
// ============================================================
$idsProducto = array_unique(
    array_map(fn($item) => trim($item['id'] ?? ''), $carrito)
);
$idsProducto = array_filter($idsProducto, fn($id) => $id !== '');

if (count($idsProducto) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'mensaje' => 'No se encontraron productos válidos.']);
    exit;
}

// Construir placeholders para el IN ()  →  :p0, :p1, :p2 …
$placeholders = implode(',', array_map(fn($i) => ":p$i", array_keys($idsProducto)));

try {
    $stmtPrecios = $pdo->prepare(
        "SELECT id, precio FROM productos WHERE id IN ($placeholders)"
    );
    foreach (array_values($idsProducto) as $i => $id) {
        $stmtPrecios->bindValue(":p$i", $id);
    }
    $stmtPrecios->execute();

    // Mapa id → precio_real
    $preciosReales = [];
    foreach ($stmtPrecios->fetchAll() as $fila) {
        $preciosReales[$fila['id']] = (float) $fila['precio'];
    }

} catch (PDOException $e) {
    error_log('[SPORTSWARE][procesar_pedido] precios: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error interno al verificar productos.']);
    exit;
}

// ── Construir líneas de detalle verificadas ────────────────
$lineas  = [];
$subtotal = 0.0;
$ENVIO   = 10000.0;

foreach ($carrito as $item) {
    $productoId = trim($item['id']    ?? '');
    $talla      = trim($item['talla'] ?? '');
    $cantidad   = max(1, (int) ($item['cantidad'] ?? 1));

    if ($productoId === '' || !isset($preciosReales[$productoId])) {
        // Producto no encontrado en BD → rechazamos el pedido completo
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'mensaje' => "El producto '$productoId' no existe en el catálogo.",
        ]);
        exit;
    }

    $precioUnitario = $preciosReales[$productoId];
    $subtotal      += $precioUnitario * $cantidad;

    $lineas[] = [
        'producto_id'     => $productoId,
        'cantidad'        => $cantidad,
        'precio_unitario' => $precioUnitario,
        'talla'           => $talla,
    ];
}

// Total calculado en el servidor (subtotal + envío fijo)
$totalServidor = $subtotal + $ENVIO;

// Alerta si el total del frontend difiere del servidor en más de $1 COP
// (tolerancia por redondeo de flotantes). Registrar pero no bloquear.
if ($totalFrontend !== null && abs($totalFrontend - $totalServidor) > 1) {
    error_log(sprintf(
        '[SPORTSWARE][procesar_pedido] Diferencia de total: frontend=%.2f servidor=%.2f uid=%d',
        $totalFrontend, $totalServidor, $uid
    ));
}

// ============================================================
//  3. TRANSACCIÓN
// ============================================================
try {
    $pdo->beginTransaction();

    // ── 3a. Insertar cabecera del pedido ───────────────────
    $stmtPedido = $pdo->prepare(
        'INSERT INTO pedidos
             (usuario_id, total, estado, direccion_envio, metodo_pago)
         VALUES
             (:uid, :total, :estado, :direccion, :metodo)'
    );
    $stmtPedido->execute([
        ':uid'      => $uid,
        ':total'    => $totalServidor,
        ':estado'   => 'pendiente',
        ':direccion'=> $direccionEnvio,
        ':metodo'   => $metodoPago,
    ]);

    $pedidoId = (int) $pdo->lastInsertId();

    // ── 3b. Insertar líneas de detalle ─────────────────────
    $stmtDetalle = $pdo->prepare(
        'INSERT INTO detalles_pedido
             (pedido_id, producto_id, cantidad, precio_unitario, talla)
         VALUES
             (:pedido_id, :producto_id, :cantidad, :precio_unitario, :talla)'
    );

    foreach ($lineas as $linea) {
        $stmtDetalle->execute([
            ':pedido_id'      => $pedidoId,
            ':producto_id'    => $linea['producto_id'],
            ':cantidad'       => $linea['cantidad'],
            ':precio_unitario'=> $linea['precio_unitario'],
            ':talla'          => $linea['talla'],
        ]);
    }

    // ── 3c. Vaciar carrito persistente del usuario ─────────
    $stmtVaciar = $pdo->prepare(
        'DELETE FROM carrito WHERE usuario_id = :uid'
    );
    $stmtVaciar->execute([':uid' => $uid]);

    // ── 3d. Confirmar transacción ──────────────────────────
    $pdo->commit();

} catch (PDOException $e) {
    // Revertir todo si cualquier paso falla
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[SPORTSWARE][procesar_pedido] transacción: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo procesar el pedido. Intenta de nuevo.',
    ]);
    exit;
}

// ============================================================
//  4. RESPUESTA DE ÉXITO
// ============================================================
echo json_encode([
    'success'   => true,
    'mensaje'   => '¡Pedido confirmado exitosamente!',
    'pedido_id' => $pedidoId,
    'total'     => $totalServidor,
]);
