<?php
/**
 * Conexion unica a MySQL mediante PDO.
 * Todos los accesos a datos del proyecto pasan por esta funcion.
 *
 * Importante: el modo de errores es EXCEPTION y los datos se
 * devuelven como arrays asociativos.
 */
require_once __DIR__ . '/app.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // En desarrollo conviene ver el error; en produccion se ocultaria
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexion con la base de datos.',
            ]);
            exit;
        }
    }

    return $pdo;
}