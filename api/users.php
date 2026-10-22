<?php
/**
 * API de gestion de usuarios. Solo administradores.
 *
 *   GET  api/users.php?action=list
 *   GET  api/users.php?action=get&id=N
 *   POST api/users.php?action=create
 *   POST api/users.php?action=update
 *   POST api/users.php?action=delete
 */
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

$action = query('action', '');

requireCsrfIfPost();

/** Devuelve true si el correo ya existe en otro usuario. */
function emailYaExiste(string $email, int $exceptoId = 0): bool
{
    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
    $stmt->execute(['email' => $email, 'id' => $exceptoId]);
    return $stmt->fetch() !== false;
}

/** Valida y devuelve los roles disponibles (para el select del form). */
function obtenerRoles(): array
{
    return db()->query('SELECT id, nombre FROM roles ORDER BY id')->fetchAll();
}

/** Devuelve el id del rol administrador. */
function adminRoleId(): int
{
    $stmt = db()->prepare("SELECT id FROM roles WHERE nombre = 'admin' LIMIT 1");
    $stmt->execute();
    return (int)($stmt->fetchColumn() ?: 0);
}

/** Cuenta los administradores activos, opcionalmente excluyendo a uno. */
function countarAdminsActivos(int $excluirId = 0): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM users WHERE rol_id = :rol AND activo = 1 AND id <> :excluir'
    );
    $stmt->execute(['rol' => adminRoleId(), 'excluir' => $excluirId]);
    return (int)$stmt->fetchColumn();
}

switch ($action) {

    case 'list':
        $users = db()->query(
            'SELECT u.id, u.nombre, u.email, u.activo, u.fecha_creacion, r.nombre AS rol
             FROM users u
             INNER JOIN roles r ON r.id = u.rol_id
             ORDER BY u.id'
        )->fetchAll();
        respondJson(true, ['users' => $users, 'roles' => obtenerRoles()]);
        break;

    case 'get':
        $id = (int) query('id', 0);
        $stmt = db()->prepare(
            'SELECT u.id, u.nombre, u.email, u.activo, r.id AS rol_id, r.nombre AS rol
             FROM users u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            respondJson(false, null, 'Usuario no encontrado.', 404);
        }
        respondJson(true, $user);
        break;

    case 'create':
        $data    = jsonInput();
        $nombre  = trim((string)($data['nombre'] ?? ''));
        $email   = strtolower(trim((string)($data['email'] ?? '')));
        $pass    = (string)($data['password'] ?? '');
        $rolId   = (int)($data['rol_id'] ?? 0);
        $activo  = isset($data['activo']) ? (int)$data['activo'] : 1;

        if ($nombre === '' || mb_strlen($nombre) > 80) {
            respondJson(false, null, 'Nombre obligatorio (max 80 caracteres).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respondJson(false, null, 'Correo electronico invalido.');
        }
        if (strlen($pass) < 8) {
            respondJson(false, null, 'La contrasena debe tener al menos 8 caracteres.');
        }
        if ($rolId <= 0 || !in_array($rolId, array_column(obtenerRoles(), 'id'), false)) {
            respondJson(false, null, 'Rol invalido.');
        }
        if (emailYaExiste($email)) {
            respondJson(false, null, 'Ya existe un usuario con ese correo.');
        }

        try {
            $stmt = db()->prepare(
                'INSERT INTO users (nombre, email, pass_hash, rol_id, activo)
                 VALUES (:nombre, :email, :pass_hash, :rol_id, :activo)'
            );
            $stmt->execute([
                'nombre'    => $nombre,
                'email'     => $email,
                'pass_hash' => password_hash($pass, PASSWORD_DEFAULT),
                'rol_id'    => $rolId,
                'activo'    => $activo,
            ]);
            // Capturar el id ANTES de escribir en la bitacora,
            // ya que lastInsertId() refleja el ultimo INSERT (activity_logs)
            $userId = (int)db()->lastInsertId();
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo crear el usuario.');
        }

        logActivity('usuarios:create', ['email' => $email, 'rol_id' => $rolId, 'user_id' => $userId]);
        respondJson(true, ['id' => $userId], 'Usuario creado.');
        break;

    case 'update':
        $data    = jsonInput();
        $id      = (int)($data['id'] ?? 0);
        $nombre  = trim((string)($data['nombre'] ?? ''));
        $email   = strtolower(trim((string)($data['email'] ?? '')));
        $pass    = (string)($data['password'] ?? '');
        $rolId   = (int)($data['rol_id'] ?? 0);
        $activo  = isset($data['activo']) ? (int)$data['activo'] : 1;

        if ($id <= 0) {
            respondJson(false, null, 'ID de usuario invalido.');
        }
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            respondJson(false, null, 'Nombre obligatorio (max 80 caracteres).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respondJson(false, null, 'Correo electronico invalido.');
        }
        if ($pass !== '' && strlen($pass) < 8) {
            respondJson(false, null, 'La contrasena debe tener al menos 8 caracteres.');
        }
        if ($rolId <= 0 || !in_array($rolId, array_column(obtenerRoles(), 'id'), false)) {
            respondJson(false, null, 'Rol invalido.');
        }
        if (emailYaExiste($email, $id)) {
            respondJson(false, null, 'Ya existe otro usuario con ese correo.');
        }

        $stmt = db()->prepare('SELECT id, rol_id, activo FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $actual = $stmt->fetch();
        if ($actual === false) {
            respondJson(false, null, 'Usuario no encontrado.', 404);
        }

        /*
         * Sin estas dos guardas un administrador puede dejar el sistema sin
         * ningun administrador activo y nadie podria volver a entrar a
         * administrarlo: la cuenta desactivada ya no puede autenticarse y el
         * resto de roles recibe 403 en este endpoint.
         */
        $rolAdmin    = adminRoleId();
        $activoActual = (int)$actual['activo'] === 1;
        $esAdmin     = (int)$actual['rol_id'] === $rolAdmin;
        $seDesactiva = $activoActual && $activo === 0;

        if ($seDesactiva && $id === (int)currentUser()['id']) {
            respondJson(false, null, 'No puedes desactivar tu propia cuenta.');
        }
        if ($seDesactiva && $esAdmin && countarAdminsActivos($id) === 0) {
            respondJson(false, null, 'No se puede desactivar: debe quedar al menos un administrador activo.', 409);
        }

        try {
            $sql = 'UPDATE users SET nombre = :nombre, email = :email, rol_id = :rol_id, activo = :activo';
            $params = [
                'nombre' => $nombre,
                'email'  => $email,
                'rol_id' => $rolId,
                'activo' => $activo,
                'id'     => $id,
            ];
            if ($pass !== '') {
                $sql .= ', pass_hash = :pass_hash';
                $params['pass_hash'] = password_hash($pass, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';

            $stmt = db()->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            respondJson(false, null, 'No se pudo actualizar el usuario.');
        }

        logActivity('usuarios:update', ['user_id' => $id]);
        respondJson(true, null, 'Usuario actualizado.');
        break;

    case 'delete':
        $data = jsonInput();
        $id   = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            respondJson(false, null, 'ID de usuario invalido.');
        }
        $me = currentUser();
        if ($id === (int)$me['id']) {
            respondJson(false, null, 'No puedes eliminar tu propia cuenta.');
        }

        try {
            $stmt = db()->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            // Error de integridad referencial (usuario es dueno de datasets, etc.)
            respondJson(false, null, 'No se puede eliminar: el usuario tiene datos asociados.');
        }

        if ($stmt->rowCount() === 0) {
            respondJson(false, null, 'Usuario no encontrado.', 404);
        }

        logActivity('usuarios:delete', ['user_id' => $id]);
        respondJson(true, null, 'Usuario eliminado.');
        break;

    default:
        respondJson(false, null, 'Accion no valida.');
}