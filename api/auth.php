<?php
/**
 * API de autenticacion: login, logout y estado de sesion.
 *
 * Endpoints:
 *   POST api/auth.php?action=login
 *   POST api/auth.php?action=logout
 *   GET  api/auth.php?action=session
 */
require_once __DIR__ . '/../includes/functions.php';

$action = query('action', '');

switch ($action) {

    case 'login':
        $data   = jsonInput();
        $email  = strtolower(trim((string)($data['email'] ?? '')));
        $pass   = (string)($data['password'] ?? '');

        if ($email === '' || $pass === '') {
            respondJson(false, null, 'Correo y contrasena son obligatorios.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respondJson(false, null, 'Formato de correo invalido.');
        }

        try {
            $stmt = db()->prepare(
                'SELECT u.id, u.nombre, u.email, u.pass_hash, u.activo, r.nombre AS rol
                 FROM users u
                 INNER JOIN roles r ON r.id = u.rol_id
                 WHERE u.email = :email
                 LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            respondJson(false, null, 'Error al consultar la base de datos.');
        }

        // Mensaje generico: no revelar si el correo existe o no
        if (!$user || (int)$user['activo'] !== 1 || !password_verify($pass, $user['pass_hash'])) {
            respondJson(false, null, 'Credenciales invalidas o usuario inactivo.');
        }

        loginUser((int)$user['id'], $user);
        logActivity('login', ['rol' => $user['rol']]);

        respondJson(true, [
            'id'    => (int)$user['id'],
            'name'  => $user['nombre'],
            'email' => $user['email'],
            'rol'   => $user['rol'],
        ], 'Sesion iniciada correctamente.');
        break;

    case 'logout':
        $user = currentUser();
        if ($user) {
            logActivity('logout', null);
        }
        startSession();
        $_SESSION = [];
        session_destroy();
        respondJson(true, null, 'Sesion cerrada.');
        break;

    case 'session':
        $user = currentUser();
        if ($user === null) {
            respondJson(false, null, 'Sin sesion activa.', 401);
        }
        respondJson(true, $user, '');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}