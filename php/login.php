<?php
// ============================================================
//  SPORTSWARE – php/login.php
//  Endpoint POST: autentica al usuario y abre su sesión PHP.
//  Responde siempre con JSON.
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión ANTES de escribir cualquier output
session_start();

// Regenerar ID de sesión al iniciar para prevenir session fixation
// (solo si no hay una sesión activa ya válida)
if (empty($_SESSION['usuario_id'])) {
    session_regenerate_id(true);
}

require_once __DIR__ . '/config.php';   // $pdo disponible

// ------------------------------------------------------------
// 1. LEER DATOS DE ENTRADA  (JSON body o form-data)
// ------------------------------------------------------------
$body = json_decode(file_get_contents('php://input'), true);

$email    = trim($body['email']    ?? $_POST['email']    ?? '');
$password = trim($body['password'] ?? $_POST['password'] ?? '');

// ------------------------------------------------------------
// 2. VALIDACIONES BÁSICAS
// ------------------------------------------------------------
if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Correo y contraseña son obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Formato de correo inválido.']);
    exit;
}

// ------------------------------------------------------------
// 3. BUSCAR USUARIO POR EMAIL
//    Usamos un mensaje genérico de error para no revelar
//    si el email existe o no en la BD (enumeración de usuarios).
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare(
        'SELECT id, nombre, email, password, telefono, direccion
         FROM usuarios
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();   // PDO::FETCH_ASSOC por defecto (config.php)

} catch (PDOException $e) {
    error_log('[SPORTSWARE][login] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error interno. Intenta más tarde.']);
    exit;
}

// ------------------------------------------------------------
// 4. VERIFICAR CONTRASEÑA
//    password_verify() compara la contraseña en texto plano
//    con el hash bcrypt almacenado. Tarda ~ms para dificultar
//    ataques de fuerza bruta.
// ------------------------------------------------------------
if (!$usuario || !password_verify($password, $usuario['password'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Correo o contraseña incorrectos.'
    ]);
    exit;
}

// ------------------------------------------------------------
// 5. ABRIR SESIÓN – guardar solo datos no sensibles
//    NUNCA guardar el hash de contraseña en $_SESSION.
// ------------------------------------------------------------
$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['nombre']     = $usuario['nombre'];
$_SESSION['email']      = $usuario['email'];
$_SESSION['login_time'] = time();

// ------------------------------------------------------------
// 6. RESPUESTA DE ÉXITO
//    Devolvemos los datos del usuario que el frontend necesita
//    para actualizar la UI (nombre en navbar, etc.).
//    No se devuelve el campo `password` en ningún caso.
// ------------------------------------------------------------
echo json_encode([
    'success' => true,
    'mensaje' => '¡Bienvenido de nuevo, ' . htmlspecialchars($usuario['nombre']) . '!',
    'usuario' => [
        'id'        => (int) $usuario['id'],
        'nombre'    => $usuario['nombre'],
        'email'     => $usuario['email'],
        'telefono'  => $usuario['telefono'],
        'direccion' => $usuario['direccion'],
    ],
]);
