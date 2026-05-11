<?php
// ============================================================
//  SPORTSWARE – php/carrito_sync.php
//  Sincroniza el carrito de localStorage con la BD.
//
//  Acciones soportadas (campo "accion" en el JSON):
//    "merge"    → fusiona carrito local con BD al hacer login
//    "agregar"  → inserta o incrementa un ítem en BD
//    "actualizar" → actualiza la cantidad de un ítem en BD
//    "eliminar" → elimina un ítem de la BD
//    "vaciar"   → borra todos los ítems del usuario en BD
//    "obtener"  → devuelve el carrito completo desde BD
//
//  Todas las rutas responden con JSON.
//  El guard usa SOLO_VERIFICAR para devolver 401 en AJAX.
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// Guard de sesión en modo silencioso (manejamos el 401 manualmente)
define('SOLO_VERIFICAR', true);
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/config.php';   // $pdo

// ── Si no hay sesión activa devolvemos 401 ─────────────────
if (!$sesionActiva) {
    http_response_code(401);
    echo json_encode([
        'success'     => false,
        'autenticado' => false,
        'mensaje'     => 'Sesión no activa.',
    ]);
    exit;
}

// ── Solo POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

// ── Leer cuerpo JSON ───────────────────────────────────────
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$accion = trim($body['accion'] ?? 'merge');
$uid    = (int) $_SESSION['usuario_id'];

// ============================================================
//  HELPERS INTERNOS
// ============================================================

/**
 * Devuelve el carrito completo del usuario desde la BD,
 * en el mismo formato que usa carrito.js en localStorage.
 */
function obtenerCarritoBD(PDO $pdo, int $uid): array
{
    $stmt = $pdo->prepare(
        'SELECT c.producto_id  AS id,
                c.talla,
                c.cantidad,
                p.nombre,
                p.precio,
                p.imagen
         FROM   carrito c
         JOIN   productos p ON p.id = c.producto_id
         WHERE  c.usuario_id = :uid
         ORDER  BY c.fecha_agregado ASC'
    );
    $stmt->execute([':uid' => $uid]);
    $filas = $stmt->fetchAll();

    // Construimos el array con la misma forma que localStorage
    return array_map(function (array $f): array {
        $clave = $f['talla'] !== '' ? $f['id'] . '-' . $f['talla'] : $f['id'];
        return [
            'clave'    => $clave,
            'id'       => $f['id'],
            'nombre'   => $f['nombre'],
            'precio'   => (float) $f['precio'],
            'imagen'   => $f['imagen'] ?? '',
            'talla'    => $f['talla'],
            'cantidad' => (int) $f['cantidad'],
        ];
    }, $filas);
}

/**
 * INSERT ... ON DUPLICATE KEY UPDATE para un ítem.
 * Si ya existe la combinación (usuario_id, producto_id, talla)
 * suma la cantidad recibida al valor almacenado (comportamiento
 * de merge). Para actualizar la cantidad exacta pasa $sumar=false.
 */
function upsertItem(
    PDO    $pdo,
    int    $uid,
    string $productoId,
    string $talla,
    int    $cantidad,
    bool   $sumar = true
): void {
    if ($sumar) {
        $sql = '
            INSERT INTO carrito (usuario_id, producto_id, talla, cantidad)
            VALUES (:uid, :pid, :talla, :cantidad)
            ON DUPLICATE KEY UPDATE
                cantidad = cantidad + VALUES(cantidad)';
    } else {
        $sql = '
            INSERT INTO carrito (usuario_id, producto_id, talla, cantidad)
            VALUES (:uid, :pid, :talla, :cantidad)
            ON DUPLICATE KEY UPDATE
                cantidad = VALUES(cantidad)';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid'      => $uid,
        ':pid'      => $productoId,
        ':talla'    => $talla,
        ':cantidad' => $cantidad,
    ]);
}

// ============================================================
//  DISPATCH DE ACCIONES
// ============================================================
try {

    // ── MERGE: fusión al hacer login ───────────────────────
    // Recibe: { "accion": "merge", "carrito": [...] }
    // Para cada ítem del carrito local:
    //   - Si NO existe en BD → inserta con la cantidad local
    //   - Si YA existe en BD → conserva la cantidad de BD
    //     (no sobreescribimos para no perder compras anteriores)
    // Devuelve el carrito fusionado completo.
    if ($accion === 'merge') {

        $carritoLocal = $body['carrito'] ?? [];

        // Validación mínima
        if (!is_array($carritoLocal)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'Formato de carrito inválido.']);
            exit;
        }

        foreach ($carritoLocal as $item) {
            $productoId = trim($item['id']      ?? '');
            $talla      = trim($item['talla']   ?? '');
            $cantidad   = max(1, (int) ($item['cantidad'] ?? 1));

            if ($productoId === '') continue;

            // INSERT solo si NO existe (ON DUPLICATE KEY → no hace nada)
            // Así respetamos la cantidad que ya estaba en BD.
            $stmt = $pdo->prepare('
                INSERT IGNORE INTO carrito
                    (usuario_id, producto_id, talla, cantidad)
                VALUES
                    (:uid, :pid, :talla, :cantidad)
            ');
            $stmt->execute([
                ':uid'      => $uid,
                ':pid'      => $productoId,
                ':talla'    => $talla,
                ':cantidad' => $cantidad,
            ]);
        }

        $carritoFusionado = obtenerCarritoBD($pdo, $uid);

        echo json_encode([
            'success' => true,
            'accion'  => 'merge',
            'carrito' => $carritoFusionado,
        ]);
        exit;
    }

    // ── AGREGAR: añadir / incrementar un ítem ─────────────
    // Recibe: { "accion": "agregar", "id": "CAM-H-01",
    //           "talla": "M", "cantidad": 1 }
    if ($accion === 'agregar') {

        $productoId = trim($body['id']       ?? '');
        $talla      = trim($body['talla']    ?? '');
        $cantidad   = max(1, (int) ($body['cantidad'] ?? 1));

        if ($productoId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'ID de producto requerido.']);
            exit;
        }

        // Verificar que el producto exista en la tabla productos
        $check = $pdo->prepare('SELECT id FROM productos WHERE id = :pid LIMIT 1');
        $check->execute([':pid' => $productoId]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado en catálogo.']);
            exit;
        }

        upsertItem($pdo, $uid, $productoId, $talla, $cantidad, true);

        echo json_encode([
            'success' => true,
            'accion'  => 'agregar',
            'carrito' => obtenerCarritoBD($pdo, $uid),
        ]);
        exit;
    }

    // ── ACTUALIZAR: establecer cantidad exacta ─────────────
    // Recibe: { "accion": "actualizar", "id": "CAM-H-01",
    //           "talla": "M", "cantidad": 3 }
    if ($accion === 'actualizar') {

        $productoId = trim($body['id']    ?? '');
        $talla      = trim($body['talla'] ?? '');
        $cantidad   = max(1, (int) ($body['cantidad'] ?? 1));

        if ($productoId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'ID de producto requerido.']);
            exit;
        }

        upsertItem($pdo, $uid, $productoId, $talla, $cantidad, false);

        echo json_encode([
            'success' => true,
            'accion'  => 'actualizar',
            'carrito' => obtenerCarritoBD($pdo, $uid),
        ]);
        exit;
    }

    // ── ELIMINAR: quitar un ítem del carrito ───────────────
    // Recibe: { "accion": "eliminar", "id": "CAM-H-01",
    //           "talla": "M" }
    if ($accion === 'eliminar') {

        $productoId = trim($body['id']    ?? '');
        $talla      = trim($body['talla'] ?? '');

        if ($productoId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'mensaje' => 'ID de producto requerido.']);
            exit;
        }

        $stmt = $pdo->prepare('
            DELETE FROM carrito
            WHERE  usuario_id  = :uid
              AND  producto_id = :pid
              AND  talla       = :talla
        ');
        $stmt->execute([
            ':uid'   => $uid,
            ':pid'   => $productoId,
            ':talla' => $talla,
        ]);

        echo json_encode([
            'success' => true,
            'accion'  => 'eliminar',
            'carrito' => obtenerCarritoBD($pdo, $uid),
        ]);
        exit;
    }

    // ── VACIAR: limpiar todo el carrito del usuario ────────
    // Recibe: { "accion": "vaciar" }
    if ($accion === 'vaciar') {

        $stmt = $pdo->prepare('DELETE FROM carrito WHERE usuario_id = :uid');
        $stmt->execute([':uid' => $uid]);

        echo json_encode([
            'success' => true,
            'accion'  => 'vaciar',
            'carrito' => [],
        ]);
        exit;
    }

    // ── OBTENER: leer el carrito desde BD ─────────────────
    // Recibe: { "accion": "obtener" }
    if ($accion === 'obtener') {

        echo json_encode([
            'success' => true,
            'accion'  => 'obtener',
            'carrito' => obtenerCarritoBD($pdo, $uid),
        ]);
        exit;
    }

    // ── Acción desconocida ─────────────────────────────────
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => "Acción '$accion' no reconocida."]);

} catch (PDOException $e) {
    error_log('[SPORTSWARE][carrito_sync] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error interno del servidor.']);
}
