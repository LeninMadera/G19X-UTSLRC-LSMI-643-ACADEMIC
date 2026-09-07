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

// Ruta raiz del proyecto (util para includes)
define('ROOT_PATH', dirname(__DIR__));