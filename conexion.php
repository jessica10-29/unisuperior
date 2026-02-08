<?php
// conexion.php - Conexion a Base de Datos y funciones globales

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || php_sapi_name() === 'cli';
$httpsActivo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$forzarHttps = getenv('FORCE_HTTPS') === '1';

// Configuracion de errores (muestra en local, oculta en produccion)
ini_set('display_errors', $isLocal ? 1 : 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Asegurar carpeta de logs y registrar en archivo
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php-error.log');

// === Cabeceras de seguridad ===
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

// Forzar HTTPS solo si hay certificado activo o se habilita por variable de entorno
if (!$isLocal && ($httpsActivo || $forzarHttps)) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    if (!$httpsActivo) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
}

// Cookies de sesion seguras
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $httpsActivo,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Cargar credenciales externas
$configPath = __DIR__ . '/secure/config.php';
if (!file_exists($configPath)) {
    exit('Falta el archivo secure/config.php con las credenciales de la base de datos.');
}
$config = require $configPath;

// Preferir variables de entorno para mayor seguridad
$host = getenv('DB_HOST') ?: ($config['DB_HOST'] ?? 'localhost');
$user = getenv('DB_USER') ?: ($config['DB_USER'] ?? 'root');
$pass = getenv('DB_PASS') ?: ($config['DB_PASS'] ?? '');
$db   = getenv('DB_NAME') ?: ($config['DB_NAME'] ?? 'universidad');

// Fallback automatico en entorno local
if ($isLocal) {
    $host = getenv('DB_HOST_LOCAL') ?: 'localhost';
    $user = getenv('DB_USER_LOCAL') ?: 'root';
    $pass = getenv('DB_PASS_LOCAL') ?: '';
    $db   = getenv('DB_NAME_LOCAL') ?: 'universidad';
}

// Evitar excepciones fatales de mysqli y manejar manualmente
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_errno) {
    error_log("Error de conexion a BD ({$host}): " . $conn->connect_error);
    http_response_code(503);
    $mensaje = $isLocal
        ? 'Error de conexion a la base de datos: ' . $conn->connect_error
        : 'Estamos realizando ajustes tecnicos. Intenta nuevamente en unos minutos.';
    exit($mensaje);
}

$conn->set_charset('utf8mb4');

// Iniciar sesion solo si no esta iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar funciones academicas (Periodos, Auditoria)
require_once __DIR__ . '/funciones_academicas.php';

// Recuperar periodo actual si el usuario ya esta autenticado
if (isset($_SESSION['usuario_id'])) {
    obtener_periodo_actual();
}

// === Funciones de ayuda ===

function verificar_sesion()
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

function verificar_rol($rol_requerido)
{
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $rol_requerido) {
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'estudiante') {
            header('Location: dashboard_estudiante.php');
        } elseif (isset($_SESSION['rol']) && $_SESSION['rol'] === 'profesor') {
            header('Location: dashboard_profesor.php');
        } else {
            header('Location: login.php');
        }
        exit();
    }
}

function limpiar_dato($dato)
{
    global $conn;
    return $conn->real_escape_string(trim($dato));
}

function obtener_nombre_usuario()
{
    return isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
}

function obtener_foto_usuario($foto = null)
{
    if ($foto && file_exists(__DIR__ . '/uploads/fotos/' . $foto)) {
        return 'uploads/fotos/' . $foto;
    }
    return 'https://ui-avatars.com/api/?name=User&background=6366f1&color=fff';
}

// === CSRF ===

function generar_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificar_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
