<?php
// ============================================================
//  SPORTSWARE – php/buscar_sugerencias.php
//  Endpoint GET: devuelve hasta 6 sugerencias de productos
//  que coincidan con el parámetro ?q= en nombre, marca o
//  categoría. Responde siempre con JSON.
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/config.php';   // $pdo

// ------------------------------------------------------------
// 1. LEER Y VALIDAR EL PARÁMETRO ?q=
// ------------------------------------------------------------
$q = trim($_GET['q'] ?? '');

// Mínimo 2 caracteres para evitar consultas demasiado amplias
if (mb_strlen($q) < 2) {
    echo json_encode(['success' => true, 'resultados' => []]);
    exit;
}

// Longitud máxima para evitar queries absurdamente largas
if (mb_strlen($q) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Búsqueda demasiado larga.']);
    exit;
}

// ------------------------------------------------------------
// 2. CONSULTA PREPARADA CON LIKE
//    Buscamos en nombre, marca y categoria.
//    El wildcard (%) se añade aquí en PHP, no en el bind,
//    para que PDO trate el valor entero como un literal seguro.
//    Orden: primero coincidencias en nombre (más relevante),
//    luego el resto. Limitamos a 6 filas.
// ------------------------------------------------------------
$termino = '%' . $q . '%';   // seguro: PDO escapa el valor completo

try {
    $stmt = $pdo->prepare(
        'SELECT   id,
                  nombre,
                  precio,
                  imagen,
                  categoria,
                  marca
         FROM     productos
         WHERE    nombre    LIKE :q1
            OR    marca     LIKE :q2
            OR    categoria LIKE :q3
         ORDER BY
                  -- Coincidencia exacta al inicio del nombre → primero
                  CASE WHEN nombre LIKE :q4 THEN 0 ELSE 1 END,
                  nombre ASC
         LIMIT    6'
    );

    // Necesitamos un bind por cada placeholder (PDO no permite reusar)
    $stmt->execute([
        ':q1' => $termino,
        ':q2' => $termino,
        ':q3' => $termino,
        ':q4' => $q . '%',   // coincidencia al inicio del nombre
    ]);

    $filas = $stmt->fetchAll();   // PDO::FETCH_ASSOC por config.php

} catch (PDOException $e) {
    error_log('[SPORTSWARE][buscar_sugerencias] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error interno.']);
    exit;
}

// ------------------------------------------------------------
// 3. FORMATEAR RESPUESTA
//    Solo devolvemos los campos que necesita el frontend.
//    Casteamos precio a float para que JSON no lo envíe
//    como string (MySQL devuelve DECIMAL como string en PHP).
// ------------------------------------------------------------
$resultados = array_map(fn(array $f): array => [
    'id'        => $f['id'],
    'nombre'    => $f['nombre'],
    'precio'    => (float) $f['precio'],
    'imagen'    => $f['imagen'] ?? '',
    'categoria' => $f['categoria'] ?? '',
    'marca'     => $f['marca'] ?? '',
], $filas);

echo json_encode([
    'success'    => true,
    'resultados' => $resultados,
    'total'      => count($resultados),
]);
