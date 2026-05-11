<?php
// ============================================================
//  SPORTSWARE – php/registro.php
//  Endpoint POST: registra un nuevo usuario en la BD.
//  Responde siempre con JSON.
// ============================================================

// Solo aceptamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';   // $pdo disponible

// ------------------------------------------------------------
// 1. LEER Y SANEAR DATOS DE ENTRADA
//    Soporta tanto form-data (fetch FormData) como JSON body.
// ------------------------------------------------------------
$body = json_decode(file_get_contents('php://input'), true);

// Si viene como JSON usa eso; si no, usa $_POST
$nombre    = trim($body['nombre']    ?? $_POST['nombre']    ?? '');
$email     = trim($body['email']     ?? $_POST['email']     ?? '');
$password  = trim($body['password']  ?? $_POST['password']  ?? '');
$telefono  = trim($body['telefono']  ?? $_POST['telefono']  ?? '');
$direccion = trim($body['direccion'] ?? $_POST['direccion'] ?? '');

// ------------------------------------------------------------
// 2. VALIDACIONES DE SERVIDOR
//    (el frontend ya valida, pero nunca confíes solo en el cliente)
// ------------------------------------------------------------
$errores = [];

if ($nombre === '') {
    $errores[] = 'El nombre es obligatorio.';
}

if ($email === '') {
    $errores[] = 'El correo electrónico es obligatorio.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo electrónico no tiene un formato válido.';
}

if ($password === '') {
    $errores[] = 'La contraseña es obligatoria.';
} elseif (strlen($password) < 6) {
    $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
}

if (!empty($errores)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'mensaje' => implode(' ', $errores)]);
    exit;
}

// ------------------------------------------------------------
// 3. VERIFICAR QUE EL EMAIL NO ESTÉ YA REGISTRADO
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        http_response_code(409);  // 409 Conflict
        echo json_encode([
            'success' => false,
            'mensaje' => 'Ya existe una cuenta registrada con ese correo electrónico.'
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log('[SPORTSWARE][registro] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error interno. Intenta más tarde.']);
    exit;
}

// ------------------------------------------------------------
// 4. HASHEAR CONTRASEÑA E INSERTAR USUARIO
//    PASSWORD_DEFAULT usa bcrypt con cost=10 (ajustable).
//    El hash resultante tiene ~60 chars; la columna es VARCHAR(255).
// ------------------------------------------------------------
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password, telefono, direccion)
         VALUES (:nombre, :email, :password, :telefono, :direccion)'
    );
    $stmt->execute([
        ':nombre'    => $nombre,
        ':email'     => $email,
        ':password'  => $hash,
        ':telefono'  => $telefono  !== '' ? $telefono  : null,
        ':direccion' => $direccion !== '' ? $direccion : null,
    ]);

    $nuevoId = (int) $pdo->lastInsertId();

} catch (PDOException $e) {
    error_log('[SPORTSWARE][registro] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'No se pudo crear la cuenta. Intenta más tarde.']);
    exit;
}

// ------------------------------------------------------------
// 5. RESPUESTA DE ÉXITO
// ------------------------------------------------------------
http_response_code(201);   // 201 Created
echo json_encode([
    'success'    => true,
    'mensaje'    => '¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.',
    'usuario_id' => $nuevoId,
]);
