<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php'; // BASE_URL

$sesionActiva = isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] > 0;
define('SESION_MAX_INACTIVIDAD', 7200);

if ($sesionActiva && isset($_SESSION['login_time'])) {
    $inactividad = time() - $_SESSION['login_time'];
    if ($inactividad > SESION_MAX_INACTIVIDAD) {
        $_SESSION = []; session_destroy(); $sesionActiva = false;
        session_start(); $_SESSION['flash_error'] = 'Tu sesión expiró por inactividad. Por favor, vuelve a iniciar sesión.';
    } else { $_SESSION['login_time'] = time(); }
}

if (!$sesionActiva && !defined('SOLO_VERIFICAR')) {
    $esAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    if ($esAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'autenticado' => false, 'mensaje' => 'Debes iniciar sesión para continuar.', 'redirect' => BASE_URL . 'html/home.php']);
        exit;
    } else {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? BASE_URL . 'html/home.php';
        $_SESSION['flash_error'] = 'Debes iniciar sesión para acceder a esta página.';
        header('Location: ' . BASE_URL . 'html/home.php?login=requerido');
        exit;
    }
}

function sesion_usuario_id(): ?int { return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null; }
function sesion_nombre(): string { return $_SESSION['nombre'] ?? ''; }
function sesion_email(): string { return $_SESSION['email'] ?? ''; }
function sesion_flash(string $clave): string { if (isset($_SESSION[$clave])) { $msg = $_SESSION[$clave]; unset($_SESSION[$clave]); return htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); } return ''; }