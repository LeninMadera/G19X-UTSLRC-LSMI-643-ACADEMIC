<?php
// Vista exclusiva de administradores
$pageTitle = 'Usuarios';
require_once __DIR__ . '/../includes/functions.php';

/*
 * La autorizacion se comprueba ANTES de incluir header.php. Si se hiciera
 * despues, PHP ya habria enviado las cabeceras al emitir el HTML de la
 * cabecera y el header('Location') fallaria en silencio, dejando al usuario
 * una pagina parcial en lugar de la redireccion.
 */
if (currentUser() === null) {
    header('Location: ' . APP_URL . '/views/login.php');
    exit;
}
if (currentRole() !== 'admin') {
    header('Location: ' . APP_URL . '/views/dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="toolbar">
        <h2>Usuarios del sistema</h2>
        <button id="btn-nuevo" class="btn btn-primary">Nuevo usuario</button>
    </div>

    <div id="msg" class="alert hidden" role="status"></div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                <tr><td colspan="7" class="muted center">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal de formulario (crear / editar) -->
<div id="modal-backdrop" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <h3 id="modal-title">Nuevo usuario</h3>
        <form id="user-form" autocomplete="off">
            <input type="hidden" id="user-id" name="id" value="0">
            <div class="field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="80" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="email">Correo electronico</label>
                <input type="email" id="email" name="email" maxlength="120" autocomplete="off" required>
            </div>
            <div class="field">
                <label for="password">Contrasena</label>
                <input type="password" id="password" name="password" autocomplete="new-password">
                <span class="field-hint">Minimo 8 caracteres. Dejarla vacia no cambia la contrasena actual.</span>
            </div>
            <div class="field">
                <label for="rol_id">Rol</label>
                <select id="rol_id" name="rol_id" required></select>
            </div>
            <div class="checkbox-field">
                <input type="checkbox" id="activo" name="activo" value="1" checked>
                <label for="activo">Usuario activo</label>
            </div>
            <div id="form-error" class="alert alert-error hidden" role="alert"></div>
            <div class="actions">
                <button type="button" id="btn-cancelar" class="btn btn-ghost" data-close="modal-backdrop">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScript = 'users.js';
require_once __DIR__ . '/includes/footer.php'; ?>