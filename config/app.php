<?php
/**
 * Carga las variables del archivo .env al entorno del proyecto.
 * Codigo simple y compatible con PHP 8: solo lee linea por linea
 * asignando variables de entorno segun el formato CLAVE=valor.
 *
 * Uso: $env = loadEnv(__DIR__ . '/../.env');
 */
function loadEnv(string $file): array
{
    $vars = [];
    if (!file_exists($file)) {
        return $vars;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Se ignoran comentarios y lineas vacias
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        $vars[$key] = $value;
    }
    return $vars;
}

// Se carga una sola vez y se guarda como constante global
$env = loadEnv(__DIR__ . '/../.env');

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_PORT', $env['DB_PORT'] ?? '3306');
define('DB_NAME', $env['DB_NAME'] ?? 'gobierno_datos_ia');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');

// Configuracion de IA (OpenAI)
define('AI_API_KEY', $env['AI_API_KEY'] ?? '');
define('AI_MODEL',   $env['AI_MODEL']   ?? 'gpt-3.5-turbo');

// URL base y clave de sesion
define('APP_URL', $env['APP_URL'] ?? 'http://localhost/gobierno-datos-ia');
define('APP_KEY', $env['APP_KEY'] ?? '');

// Modo depuracion: 0 oculta errores en produccion, 1 solo en desarrollo
define('APP_DEBUG', ($env['APP_DEBUG'] ?? '0') === '1');

// Ruta raiz del proyecto (util para includes)
define('ROOT_PATH', dirname(__DIR__));

// Politica de errores: nunca mostrar trazas al usuario final
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// Cabeceras de seguridad basicas en todas las respuestas
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');