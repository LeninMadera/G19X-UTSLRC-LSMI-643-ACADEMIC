<?php
/**
 * Funciones auxiliares reutilizables del proyecto.
 */
require_once __DIR__ . '/../config/database.php';

/**
 * Devuelve una respuesta JSON con codigo HTTP opcional y detiene la ejecucion.
 */
function respondJson(bool $success, $data = null, string $message = '', int $statusCode = 200): void
{
    http_response_code($success ? $statusCode : ($statusCode === 200 ? 400 : $statusCode));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'message' => $message,
    ]);
    exit;
}

/**
 * Obtiene un valor del superglobal $_POST con validacion de longitud.
 */
function input(string $key, $default = null, int $maxLen = 2000)
{
    if (!isset($_POST[$key])) {
        return $default;
    }
    $value = trim((string)$_POST[$key]);
    if ($value === '') {
        return $default;
    }
    return mb_substr($value, 0, $maxLen);
}

/**
 * Lee el cuerpo de la peticion y devuelve un array.
 * Soporta JSON (para Postman) y Form-Data (para el frontend).
 */
function jsonInput(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) {
        return $data;
    }
    return $_POST;
}

/**
 * Obtiene un valor de $_GET.
 */
function query(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * Escapa texto para mostrarlo de forma segura en HTML (anti-XSS).
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Inicia la sesion de forma segura (httponly, cookie de sesion).
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('GDIA_SESSION');
    session_start();
}

/**
 * Inicia sesion para un usuario (despues de validar credenciales).
 */
function loginUser(int $userId, array $user): void
{
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_name']  = $user['nombre'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['rol']        = $user['rol'];
}

/**
 * Devuelve el rol del usuario logueado o null.
 */
function currentRole(): ?string
{
    startSession();
    return $_SESSION['rol'] ?? null;
}

/**
 * Devuelve los datos basicos del usuario logueado o null.
 */
function currentUser(): ?array
{
    startSession();
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'rol'   => $_SESSION['rol'] ?? '',
    ];
}

/**
 * Responde 401 si no hay sesion activa.
 */
function requireAuth(): void
{
    if (currentUser() === null) {
        respondJson(false, null, 'No autorizado. Inicia sesion primero.');
    }
}

/**
 * Responde 403 si el rol no esta en la lista permitida.
 */
function requireRole(array $allowedRoles): void
{
    requireAuth();
    $rol = currentRole();
    if (!in_array($rol, $allowedRoles, true)) {
        respondJson(false, null, 'No tienes permisos para esta accion.');
    }
}

/**
 * Registra una entrada en la bitacora de actividad.
 */
function logActivity(string $accion, $detalle = null): void
{
    $user = currentUser();
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs (user_id, accion, detalle, ip) VALUES (:user_id, :accion, :detalle, :ip)'
        );
        $stmt->execute([
            'user_id' => $user['id'] ?? null,
            'accion'  => $accion,
            'detalle' => $detalle !== null ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'desconocida',
        ]);
    } catch (PDOException $e) {
        // La bitacora nunca debe romper la operacion principal
    }
}